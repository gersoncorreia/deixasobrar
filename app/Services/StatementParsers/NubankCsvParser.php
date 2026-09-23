<?php

namespace App\Services\StatementParsers;

use App\DTOs\ParsedTransactionDTO;
use App\Enums\BankInstitution;
use App\Enums\TransactionType;
use App\Services\StatementParsers\Contracts\BankStatementParserInterface;
use Carbon\Carbon;

class NubankCsvParser implements BankStatementParserInterface
{
    public function canParse(string $content, string $fileName = ''): bool
    {
        $firstLines = substr($content, 0, 300);
        return (
            (stripos($firstLines, 'Identificador') !== false && stripos($firstLines, 'Valor') !== false) ||
            (stripos($firstLines, 'date') !== false && stripos($firstLines, 'amount') !== false && stripos($firstLines, 'category') !== false) ||
            stripos($fileName, 'nubank') !== false
        );
    }

    public function parse(string $content): array
    {
        $transactions = [];
        $lines = preg_split('/\r\n|\r|\n/', trim($content));

        if (empty($lines)) return $transactions;

        $delimiter = str_contains($lines[0], ';') ? ';' : ',';
        $header = str_getcsv(array_shift($lines), $delimiter);
        $header = array_map(fn($col) => trim(mb_strtolower($col, 'UTF-8')), $header);

        $dateIdx = array_search('data', $header) !== false ? array_search('data', $header) : array_search('date', $header);
        $amountIdx = array_search('valor', $header) !== false ? array_search('valor', $header) : array_search('amount', $header);
        $descIdx = array_search('descrição', $header) !== false ? array_search('descrição', $header) : (
            array_search('descricao', $header) !== false ? array_search('descricao', $header) : array_search('title', $header)
        );
        $idIdx = array_search('identificador', $header);

        foreach ($lines as $line) {
            if (empty(trim($line))) continue;

            $row = str_getcsv($line, $delimiter);
            if (count($row) < 2) continue;

            $rawDate = $row[$dateIdx] ?? '';
            try {
                // Nubank uses d/m/Y or Y-m-d
                $date = str_contains($rawDate, '/') 
                    ? Carbon::createFromFormat('d/m/Y', trim($rawDate))->format('Y-m-d')
                    : Carbon::parse(trim($rawDate))->format('Y-m-d');
            } catch (\Throwable) {
                continue;
            }

            $rawAmount = $row[$amountIdx] ?? '0';
            $cleanAmount = str_replace(['R$', ' '], '', $rawAmount);
            if (str_contains($cleanAmount, ',')) {
                $cleanAmount = str_replace('.', '', $cleanAmount);
                $cleanAmount = str_replace(',', '.', $cleanAmount);
            }
            $amount = (float) $cleanAmount;

            $description = $row[$descIdx] ?? 'Transação Nubank';
            $docId = $idIdx !== false && isset($row[$idIdx]) ? trim($row[$idIdx]) : null;

            if (empty($description) || StatementBalanceSanitizer::isBalanceRow($row) || StatementBalanceSanitizer::isBalanceRow($line)) {
                continue;
            }

            if ($amount == 0.0) continue;

            $type = $amount > 0 ? TransactionType::Income : TransactionType::Expense;

            $transactions[] = new ParsedTransactionDTO(
                date: $date,
                description: $description,
                amount: $amount,
                type: $type,
                documentNumber: $docId,
                rawStatementText: $line,
            );
        }

        return $transactions;
    }

    public function getBankIdentifier(): BankInstitution
    {
        return BankInstitution::Nubank;
    }
}
