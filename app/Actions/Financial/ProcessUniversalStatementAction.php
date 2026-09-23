<?php

namespace App\Actions\Financial;

use App\Models\Account;
use App\Models\StatementImport;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProcessUniversalStatementAction
{
    public function __construct(
        protected DetectBankStatementAction $detector,
        protected ClassifyTransactionAction $classifier,
        protected \App\Services\AiStatementEnricherService $aiEnricher
    ) {}

    /**
     * Processes any bank statement file for a user and account.
     *
     * @param User $user
     * @param Account $account
     * @param string $content File content (CSV or OFX)
     * @param string $fileName Original file name
     * @return array
     */
    public function execute(User $user, Account $account, string $content, string $fileName): array
    {
        // 0. Guaranteed UTF-8 normalization (handles ISO-8859-1, Windows-1252, BOM, etc.)
        $content = \App\Services\StatementEncodingNormalizer::normalize($content);
        $fileName = \App\Services\StatementEncodingNormalizer::sanitizeString($fileName);

        // 1. Detect appropriate parser
        $parser = $this->detector->execute($content, $fileName);
        $bankInstitution = $parser->getBankIdentifier();

        // 2. Parse into DTO collection
        $dtos = $parser->parse($content);

        // Extract period from transactions
        $dates = array_map(fn($d) => $d->date, $dtos);
        sort($dates);
        $periodStart = !empty($dates) ? $dates[0] : null;
        $periodEnd = !empty($dates) ? end($dates) : null;

        // Try extracting final statement balance for reconciliation reference
        $detectedClosingBalance = null;
        $lines = preg_split('/\r\n|\r|\n/', trim($content));
        foreach (array_reverse($lines) as $rawLine) {
            $extracted = \App\Services\StatementParsers\StatementBalanceSanitizer::extractBalanceValue($rawLine);
            if ($extracted !== null) {
                $detectedClosingBalance = $extracted;
                break;
            }
        }

        if (empty($dtos)) {
            return [
                'success' => false,
                'message' => 'Nenhuma transação válida encontrada no arquivo fornecido.',
                'detected_bank' => $bankInstitution->label(),
                'total' => 0,
                'imported' => 0,
                'skipped' => 0,
                'period_start' => null,
                'period_end' => null,
                'detected_balance' => $detectedClosingBalance,
            ];
        }

        $totalRecords = count($dtos);
        $importedCount = 0;
        $skippedCount = 0;
        $netBalanceChange = 0.00;

        return DB::transaction(function () use (
            $user,
            $account,
            $dtos,
            $fileName,
            $bankInstitution,
            $totalRecords,
            &$importedCount,
            &$skippedCount,
            &$netBalanceChange,
            $periodStart,
            $periodEnd,
            $detectedClosingBalance
        ) {
            $statementImport = StatementImport::create([
                'user_id' => $user->id,
                'account_id' => $account->id,
                'file_name' => $fileName,
                'detected_bank' => $bankInstitution,
                'total_records' => $totalRecords,
                'imported_records' => 0,
                'skipped_records' => 0,
                'status' => 'processing',
            ]);

            // Step 1: Run local heuristic classification on all DTOs first (Zero cost)
            foreach ($dtos as $dto) {
                $this->classifier->execute($dto, $user->id);
            }

            // Step 2: Enrich remaining ambiguous/unclassified items via AI in a single batch (with caching & quota)
            $this->aiEnricher->enrichBatch($dtos, $user);

            foreach ($dtos as $dto) {
                // Ensure DTO fields are cleanly UTF-8 sanitized
                $safeDescription = \App\Services\StatementEncodingNormalizer::sanitizeString($dto->description);
                $safeRawText = \App\Services\StatementEncodingNormalizer::sanitizeString($dto->rawStatementText);
                $safeDocNumber = $dto->documentNumber ? \App\Services\StatementEncodingNormalizer::sanitizeString($dto->documentNumber) : null;

                // Deduplication check
                $exists = Transaction::where('user_id', $user->id)
                    ->where('account_id', $account->id)
                    ->where('transaction_date', $dto->date)
                    ->where('amount', $dto->amount)
                    ->when($safeDocNumber, function ($q, $doc) {
                        return $q->where('document_number', $doc);
                    }, function ($q) use ($safeDescription) {
                        return $q->where('description', $safeDescription);
                    })
                    ->exists();

                if ($exists) {
                    $skippedCount++;
                    continue;
                }

                $categoryId = $this->classifier->resolveCategoryId($dto->suggestedCategory, $user->id);

                Transaction::create([
                    'user_id' => $user->id,
                    'account_id' => $account->id,
                    'category_id' => $categoryId,
                    'statement_import_id' => $statementImport->id,
                    'transaction_date' => $dto->date,
                    'description' => $safeDescription,
                    'raw_statement_text' => $safeRawText,
                    'document_number' => $safeDocNumber,
                    'amount' => $dto->amount,
                    'type' => $dto->type,
                    'status' => 'confirmed',
                    'is_leak' => $dto->isLeak,
                    'leak_reason' => $dto->leakReason,
                ]);

                $netBalanceChange += $dto->amount;
                $importedCount++;
            }

            // Update account balance
            $account->increment('current_balance', $netBalanceChange);

            // Update import batch record
            $statementImport->update([
                'imported_records' => $importedCount,
                'skipped_records' => $skippedCount,
                'status' => 'completed',
            ]);

            return [
                'success' => true,
                'message' => "Importação concluída com sucesso! {$importedCount} lançamentos importados e {$skippedCount} duplicatas ignoradas.",
                'detected_bank' => $bankInstitution->label(),
                'total' => $totalRecords,
                'imported' => $importedCount,
                'skipped' => $skippedCount,
                'import_id' => $statementImport->id,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'detected_balance' => $detectedClosingBalance,
                'account_balance' => (float) $account->current_balance,
            ];
        });
    }
}
