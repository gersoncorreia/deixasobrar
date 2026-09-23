<?php

namespace App\Services\StatementParsers;

use App\DTOs\ParsedTransactionDTO;
use App\Enums\BankInstitution;
use App\Enums\TransactionType;
use App\Services\StatementParsers\Contracts\BankStatementParserInterface;
use Carbon\Carbon;

class GenericFuzzyCsvParser implements BankStatementParserInterface
{
    public function canParse(string $content, string $fileName = ''): bool
    {
        // Universal fallback for any text/csv with multiple lines
        $lines = preg_split('/\r\n|\r|\n/', trim($content));
        return count($lines) >= 2;
    }

    public function parse(string $content): array
    {
        $transactions = [];
        $lines = preg_split('/\r\n|\r|\n/', trim($content));

        if (count($lines) < 2) return $transactions;

        // Auto-detect delimiter
        $sample = $lines[0] . ($lines[1] ?? '');
        $delimiters = [',', ';', "\t"];
        $delimiter = ',';
        $maxCount = 0;
        foreach ($delimiters as $d) {
            $count = substr_count($sample, $d);
            if ($count > $maxCount) {
                $maxCount = $count;
                $delimiter = $d;
            }
        }

        // Parse header
        $headerRaw = str_getcsv(array_shift($lines), $delimiter);
        $header = array_map(function ($col) {
            $str = trim(str_replace(['"', "'"], '', $col));
            $str = mb_strtolower($str, 'UTF-8');
            return preg_replace('/[^\w\s]/u', '', $str);
        }, $headerRaw);

        $dateIdx = -1;
        $descIdx = -1;
        $amountIdx = -1;
        $docIdx = -1;
        $typeIdx = -1;

        foreach ($header as $idx => $name) {
            if ($dateIdx === -1 && (str_contains($name, 'data') || str_contains($name, 'date') || str_contains($name, 'dt'))) {
                $dateIdx = $idx;
            } elseif ($descIdx === -1 && (str_contains($name, 'histor') || str_contains($name, 'descr') || str_contains($name, 'lanc') || str_contains($name, 'detalh') || str_contains($name, 'estabelec') || str_contains($name, 'memo'))) {
                $descIdx = $idx;
            } elseif ($amountIdx === -1 && (str_contains($name, 'valor') || str_contains($name, 'quant') || str_contains($name, 'amount') || str_contains($name, 'val'))) {
                $amountIdx = $idx;
            } elseif ($docIdx === -1 && (str_contains($name, 'doc') || str_contains($name, 'ident') || str_contains($name, 'num') || str_contains($name, 'id'))) {
                $docIdx = $idx;
            } elseif ($typeIdx === -1 && (str_contains($name, 'tipo') || str_contains($name, 'dc') || str_contains($name, 'natureza'))) {
                $typeIdx = $idx;
            }
        }

        // Positional defaults if heuristics didn't match
        if ($dateIdx === -1) $dateIdx = 0;
        if ($descIdx === -1) $descIdx = min(1, count($header) - 1);
        if ($amountIdx === -1) $amountIdx = count($header) > 2 ? 2 : 1;

        foreach ($lines as $line) {
            if (empty(trim($line))) continue;

            $row = str_getcsv($line, $delimiter);
            if (count($row) <= max($dateIdx, $amountIdx)) continue;

            $desc = isset($row[$descIdx]) ? trim($row[$descIdx]) : '';
            if (empty($desc) || StatementBalanceSanitizer::isBalanceRow($row) || StatementBalanceSanitizer::isBalanceRow($line)) {
                continue;
            }

            // Parse Date
            $rawDate = trim($row[$dateIdx] ?? '');
            if (empty($rawDate) || str_contains($rawDate, '00/00/0000')) continue;

            try {
                if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $rawDate)) {
                    $date = Carbon::createFromFormat('d/m/Y', $rawDate)->format('Y-m-d');
                } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDate)) {
                    $date = Carbon::createFromFormat('Y-m-d', $rawDate)->format('Y-m-d');
                } else {
                    $date = Carbon::parse($rawDate)->format('Y-m-d');
                }
            } catch (\Throwable) {
                continue; // Skip invalid row
            }

            // Parse Amount
            $rawAmount = trim($row[$amountIdx] ?? '0');
            $cleanAmount = preg_replace('/[^\d,\.\-]/', '', $rawAmount);

            // Handle Brazilian format (1.234,56 or 1234,56)
            if (str_contains($cleanAmount, ',') && str_contains($cleanAmount, '.')) {
                $cleanAmount = str_replace('.', '', $cleanAmount);
                $cleanAmount = str_replace(',', '.', $cleanAmount);
            } elseif (str_contains($cleanAmount, ',')) {
                $cleanAmount = str_replace(',', '.', $cleanAmount);
            }

            $amount = (float) $cleanAmount;
            if ($amount == 0.0) continue;

            // Check if there is an explicit D/C column (Débito/Crédito)
            if ($typeIdx !== -1 && isset($row[$typeIdx])) {
                $typeVal = mb_strtolower(trim($row[$typeIdx]));
                if ((str_starts_with($typeVal, 'd') || str_contains($typeVal, 'saida') || str_contains($typeVal, 'débito')) && $amount > 0) {
                    $amount = -$amount;
                }
            }

            $type = $amount > 0 ? TransactionType::Income : TransactionType::Expense;
            $docNumber = $docIdx !== -1 && isset($row[$docIdx]) ? trim($row[$docIdx]) : null;

            $transactions[] = new ParsedTransactionDTO(
                date: $date,
                description: !empty($desc) ? $desc : 'Lançamento bancário',
                amount: $amount,
                type: $type,
                documentNumber: $docNumber,
                rawStatementText: $line,
            );
        }

        return $transactions;
    }

    public function getBankIdentifier(): BankInstitution
    {
        return BankInstitution::Generic;
    }
}
