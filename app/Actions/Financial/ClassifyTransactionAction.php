<?php

namespace App\Actions\Financial;

use App\DTOs\ParsedTransactionDTO;
use App\Enums\CategoryGroupType;
use App\Models\Category;

class ClassifyTransactionAction
{
    /**
     * Keywords that flag financial leaks (Raio-X de Vazamentos)
     */
    protected array $leakKeywords = [
        'bet', 'koreabet', 'blaze', 'sportingbet', 'betano', 'bet365', 'apostas', 'tigrinho',
        'tarifa', 'iof', 'anuidade', 'encargos', 'juros de mora',
        'fast gateway', 'ajc gateway', 'cash', 'pagseguro internet'
    ];

    /**
     * Category heuristic rules
     */
    protected array $rules = [
        'Supermercado & Feira' => ['mercale', 'supermercado', 'mercado', 'atacadao', 'assai', 'carrefour', 'hortifruti', 'padaria'],
        'Combustível & Mobilidade' => ['posto', 'combustivel', 'ipiranga', 'shell', 'petrobras', 'uber', '99app', 'auto posto'],
        'Delivery & Restaurantes' => ['ifood', 'ze delivery', 'aiqfome', 'rappi', 'restaurante', 'lanchonete', 'burger', 'mcdonalds', 'pizza', 'bar'],
        'Energia Elétrica & Água' => ['enel', 'sabesp', 'energisa', 'neoenergia', 'copel', 'sanepar', 'aguas', 'luz'],
        'Internet & Celular' => ['claro', 'vivo', 'tim', 'oi', 'telecom', 'fibra', 'internet'],
        'Moradia (Aluguel/Condomínio)' => ['aluguel', 'condominio', 'imobiliaria', 'locacao'],
        'Saúde & Convênios' => ['unimed', 'farmacia', 'drogaria', 'drogasil', 'pague menos', 'hospital', 'clinica', 'medico'],
        'Assinaturas & Streaming' => ['netflix', 'spotify', 'prime video', 'amazon prime', 'disney', 'hbo', 'globo play', 'youtube'],
        'Fatura Cartão de Crédito' => ['fatura', 'pagamento fatura', 'cartao credito', 'nubank pagamento'],
        'Carnês & Financiamentos' => ['bemol', 'gazin', 'casas bahia', 'magalu', 'carne', 'consorcio', 'financiamento'],
        'Salário / Pró-labore' => ['salario', 'pro labore', 'proventos', 'folha pagto', 'rendimento', 'ted recebida'],
    ];

    public function execute(ParsedTransactionDTO &$dto, ?int $userId = null): void
    {
        $descLower = mb_strtolower($dto->description, 'UTF-8');

        // 1. Check for Financial Leaks
        foreach ($this->leakKeywords as $leak) {
            if (str_contains($descLower, $leak)) {
                $dto->isLeak = true;
                break;
            }
        }

        // Small repetitive Pix saídas can also be flagged as leak candidates if under R$ 35
        if (!$dto->isLeak && $dto->amount < 0 && abs($dto->amount) <= 35.00 && str_contains($descLower, 'pix - enviado')) {
            $dto->isLeak = true;
        }

        // 2. Identify Suggested Category
        foreach ($this->rules as $categoryName => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($descLower, $kw)) {
                    $dto->suggestedCategory = $categoryName;
                    return;
                }
            }
        }
    }

    /**
     * Resolves category ID by suggestion name or default group
     */
    public function resolveCategoryId(?string $categoryName, ?int $userId = null): ?int
    {
        if (empty($categoryName)) {
            return null;
        }

        $category = Category::where('name', $categoryName)
            ->where(fn($q) => $q->whereNull('user_id')->orWhere('user_id', $userId))
            ->first();

        return $category?->id;
    }
}
