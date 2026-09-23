<?php

namespace App\Services;

use App\DTOs\ParsedTransactionDTO;
use App\Models\Category;
use App\Models\LearnedClassification;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiStatementEnricherService
{
    public function __construct(
        protected PlanQuotaService $quotaService
    ) {}

    /**
     * Enriches an array of ParsedTransactionDTOs using Cascade Strategy:
     * 1. Check local learned cache (R$ 0).
     * 2. If unclassified and eligible for AI, batch calls Gemini Flash.
     * 3. Saves newly learned classifications to cache for future free lookup.
     *
     * @param ParsedTransactionDTO[] $dtos
     * @param User $user
     * @return void
     */
    public function enrichBatch(array &$dtos, User $user): void
    {
        // Step 1: Resolve from learned cache first (Zero Cost)
        $unresolvedIndices = [];

        foreach ($dtos as $idx => $dto) {
            $normKey = LearnedClassification::normalizeKey($dto->description);
            $learned = LearnedClassification::where('normalized_description', $normKey)
                ->where(function ($q) use ($user) {
                    $q->whereNull('user_id')->orWhere('user_id', $user->id);
                })
                ->orderByDesc('user_id') // Prioritizes user's own specific memory
                ->first();

            if ($learned) {
                if (empty($dto->suggestedCategory) && $learned->category_name) {
                    $dto->suggestedCategory = $learned->category_name;
                }
                if (!$dto->isLeak && $learned->is_leak) {
                    $dto->isLeak = true;
                    $dto->leakReason = $learned->leak_reason;
                }
            }

            // If still without a suggested category, mark for AI consideration
            if (empty($dto->suggestedCategory)) {
                $unresolvedIndices[] = $idx;
            }
        }

        // If all items were resolved locally or no items pending, stop here
        if (empty($unresolvedIndices)) {
            return;
        }

        // Step 2: Check AI Statement Quota & Settings
        $quota = $this->quotaService->checkAiStatementQuota($user);
        if (!$quota['allowed']) {
            Log::info("AI Statement Enrichment skipped for user {$user->id}: Quota limit reached or disabled.");
            return;
        }

        $apiKey = SystemSetting::get('system_gemini_api_key') 
            ?: (config('services.gemini.key') ?? env('GEMINI_API_KEY'));
        if (empty($apiKey)) {
            return;
        }

        $model = SystemSetting::get('system_gemini_model', 'gemini-2.5-flash');

        // Cap batch size to max 35 unresolved items to prevent excessive token costs
        $candidateIndices = array_slice($unresolvedIndices, 0, 35);
        $payloadItems = [];
        foreach ($candidateIndices as $idx) {
            $payloadItems[] = [
                'id' => $idx,
                'desc' => $dtos[$idx]->description,
                'val' => $dtos[$idx]->amount,
            ];
        }

        // Known standard categories to guide the model
        $availableCategories = Category::pluck('name')->toArray();
        $categoriesListStr = implode(', ', $availableCategories);

        $systemPrompt = <<<PROMPT
Você é o motor de Inteligência Artificial do DeixaSobrar, app brasileiro de gestão financeira e fim de vazamentos de dinheiro.
Analise a lista de transações bancárias e classifique cada item com precisão.

Categorias disponíveis:
{$categoriesListStr}, Outros

Critérios para identificar VAZAMENTO FINANCEIRO (is_leak = true):
1. Tarifas bancárias, anuidade, IOF diário, encargos, taxa de pacote de conta ou manutenção de conta.
2. Apostas, cassinos online, bets (ex: tigrinho, blaze, bet365, etc.).
3. Gastos com delivery frequente, compras impulsivas supérfluas, micropagamentos recorrentes em jogos.
4. Assinaturas de serviços digitais esquecidos ou duplicados.

Retorne ESTRITAMENTE um array JSON no seguinte formato:
[
  {
    "id": 0,
    "categoria": "Nome da Categoria Mais Apropriada",
    "is_leak": true_ou_false,
    "leak_reason": "Motivo curto do vazamento se is_leak=true, senão null"
  }
]
PROMPT;

        try {
            $httpClient = Http::timeout(25)
                ->withHeaders(['Content-Type' => 'application/json']);

            if (app()->environment('local', 'testing') || config('app.debug')) {
                $httpClient = $httpClient->withoutVerifying();
            }

            $userContent = json_encode($payloadItems, JSON_UNESCAPED_UNICODE);

            $response = $httpClient->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $systemPrompt . "\n\nItens para analisar:\n" . $userContent],
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'response_mime_type' => 'application/json',
                ]
            ]);

            if ($response->successful()) {
                $rawText = $response->json('candidates.0.content.parts.0.text');
                $results = json_decode($rawText, true);

                if (is_array($results)) {
                    foreach ($results as $item) {
                        $idx = $item['id'] ?? null;
                        if ($idx !== null && isset($dtos[$idx])) {
                            $catName = $item['categoria'] ?? null;
                            $isLeak = (bool) ($item['is_leak'] ?? false);
                            $reason = $item['leak_reason'] ?? null;

                            if ($catName) {
                                $dtos[$idx]->suggestedCategory = $catName;
                            }
                            if ($isLeak) {
                                $dtos[$idx]->isLeak = true;
                                $dtos[$idx]->leakReason = $reason;
                            }

                            // Cache in learned classifications so we NEVER query AI again for this description!
                            $normKey = LearnedClassification::normalizeKey($dtos[$idx]->description);
                            LearnedClassification::firstOrCreate(
                                [
                                    'user_id' => $user->id,
                                    'normalized_description' => $normKey,
                                ],
                                [
                                    'category_name' => $catName,
                                    'is_leak' => $isLeak,
                                    'leak_reason' => $reason,
                                    'source' => 'ai',
                                ]
                            );
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('AI Statement Enrichment failed gracefully: ' . $e->getMessage());
            // Graceful fallback: statement import will still succeed without AI enhancements
        }
    }
}
