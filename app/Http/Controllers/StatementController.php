<?php

namespace App\Http\Controllers;

use App\Actions\Financial\ProcessUniversalStatementAction;
use App\Models\Account;
use App\Models\User;
use App\Models\StatementImport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class StatementController extends Controller
{
    public function __construct(
        protected ProcessUniversalStatementAction $processStatement,
        protected \App\Services\PlanQuotaService $quotaService
    ) {}

    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = Auth::user() ?? User::first();

        $accounts = $user->accounts()->get();
        $imports = $user->statementImports()
            ->with('account')
            ->orderBy('id', 'desc')
            ->take(20)
            ->get();

        $quota = $this->quotaService->checkStatementImportQuota($user);

        return Inertia::render('Statements/Index', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'accounts' => $accounts,
            'imports' => $imports,
            'quota' => $quota,
        ]);
    }

    public function upload(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'statement_files' => ['nullable', 'array'],
            'statement_files.*' => ['file', 'max:10240'], // max 10MB per file
            'statement_file' => ['nullable', 'file', 'max:10240'],
            'account_id' => ['nullable', 'exists:accounts,id'],
        ]);

        /** @var User $user */
        $user = Auth::user() ?? User::first();

        // 1. Quota Check for Statement Imports
        $quota = $this->quotaService->checkStatementImportQuota($user);
        if (!$quota['allowed']) {
            $msg = "Você atingiu o limite mensal de {$quota['limit']} importações de extrato do Plano Gratuito. Faça upgrade para o Plano Pro para importar extratos de forma ilimitada!";
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $msg, 'quota_exceeded' => true, 'quota' => $quota], 403)
                : back()->with('error', $msg);
        }

        $account = $request->account_id 
            ? Account::findOrFail($request->account_id)
            : $user->accounts()->first();

        if (!$account) {
            $account = $user->accounts()->create([
                'name' => 'Conta Padrão',
                'type' => \App\Enums\AccountType::Checking,
                'current_balance' => 0.00,
            ]);
        }

        // Collect uploaded files from either array or single input
        /** @var UploadedFile[] $files */
        $files = [];
        if ($request->hasFile('statement_files')) {
            $files = $request->file('statement_files');
        } elseif ($request->hasFile('statement_file')) {
            $files = [$request->file('statement_file')];
        }

        if (empty($files)) {
            $msg = 'Nenhum arquivo de extrato enviado.';
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $msg], 422)
                : back()->with('error', $msg);
        }

        $totalFiles = count($files);
        $successfulFiles = 0;
        $failedFiles = 0;
        $totalRecords = 0;
        $totalImported = 0;
        $totalSkipped = 0;
        $filesDetail = [];

        foreach ($files as $file) {
            $fileName = $file->getClientOriginalName();
            try {
                $content = file_get_contents($file->getRealPath());
                $result = $this->processStatement->execute($user, $account, $content, $fileName);

                if ($result['success']) {
                    $successfulFiles++;
                    $totalRecords += ($result['total'] ?? 0);
                    $totalImported += ($result['imported'] ?? 0);
                    $totalSkipped += ($result['skipped'] ?? 0);

                    $filesDetail[] = [
                        'file_name' => $fileName,
                        'success' => true,
                        'detected_bank' => $result['detected_bank'] ?? 'Desconhecido',
                        'total' => $result['total'] ?? 0,
                        'imported' => $result['imported'] ?? 0,
                        'skipped' => $result['skipped'] ?? 0,
                        'message' => $result['message'],
                        'period_start' => $result['period_start'] ?? null,
                        'period_end' => $result['period_end'] ?? null,
                        'detected_balance' => $result['detected_balance'] ?? null,
                        'account_id' => $account->id,
                        'account_name' => $account->name,
                        'account_balance' => (float) $account->current_balance,
                    ];
                } else {
                    $failedFiles++;
                    $filesDetail[] = [
                        'file_name' => $fileName,
                        'success' => false,
                        'detected_bank' => $result['detected_bank'] ?? 'Nenhum',
                        'total' => 0,
                        'imported' => 0,
                        'skipped' => 0,
                        'message' => $result['message'],
                    ];
                }
            } catch (\Throwable $e) {
                $failedFiles++;
                $filesDetail[] = [
                    'file_name' => $fileName,
                    'success' => false,
                    'detected_bank' => 'Erro',
                    'total' => 0,
                    'imported' => 0,
                    'skipped' => 0,
                    'message' => 'Erro ao processar arquivo: ' . $e->getMessage(),
                ];
            }
        }

        $isBatch = $totalFiles > 1;
        $overallSuccess = $successfulFiles > 0;

        if ($isBatch) {
            $summaryMessage = $overallSuccess
                ? "Lote concluído! {$successfulFiles} de {$totalFiles} extratos processados com sucesso. {$totalImported} lançamentos importados e {$totalSkipped} duplicatas ignoradas."
                : "Falha ao processar os extratos enviados. Nenhum lançamento pôde ser lido.";
        } else {
            $summaryMessage = $filesDetail[0]['message'] ?? 'Extrato processado.';
        }

        $firstDetail = $filesDetail[0] ?? null;

        $responsePayload = [
            'success' => $overallSuccess,
            'is_batch' => $isBatch,
            'message' => $summaryMessage,
            'total_files' => $totalFiles,
            'successful_files' => $successfulFiles,
            'failed_files' => $failedFiles,
            'total' => $totalRecords,
            'imported' => $totalImported,
            'skipped' => $totalSkipped,
            'detected_bank' => $isBatch ? 'Múltiplos Bancos' : ($firstDetail['detected_bank'] ?? 'Universal'),
            'period_start' => $firstDetail['period_start'] ?? null,
            'period_end' => $firstDetail['period_end'] ?? null,
            'detected_balance' => $firstDetail['detected_balance'] ?? null,
            'account_id' => $account->id,
            'account_name' => $account->name,
            'account_balance' => (float) $account->current_balance,
            'files_detail' => $filesDetail,
        ];

        if ($request->wantsJson()) {
            return response()->json($responsePayload, $overallSuccess ? 200 : 422);
        }

        return back()->with(
            $overallSuccess ? 'success' : 'error',
            $summaryMessage
        )->with('batch_report', $responsePayload);
    }
}
