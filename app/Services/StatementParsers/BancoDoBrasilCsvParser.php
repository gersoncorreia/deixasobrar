<?php

namespace App\Services\StatementParsers;

use App\DTOs\ParsedTransactionDTO;
use App\Enums\BankInstitution;
use App\Enums\TransactionType;
use App\Services\StatementParsers\Contracts\BankStatementParserInterface;
use Carbon\Carbon;

class BancoDoBrasilCsvParser implements BankStatementParserInterface
{
    public function canParse(string $content, string $fileName = ''): bool
    {
        $firstLines = substr($content, 0, 500);
        
        // Check for specific BB header columns
        return (
            (stripos($firstLines, 'Lançamento') !== false || stripos($firstLines, 'Lan') !== false) &&
            stripos($firstLines, 'N') !== false &&
            stripos($firstLines, 'documento') !== false &&
            stripos($firstLines, 'Tipo') !== false
        );
    }

    public function parse(string $content): array
    {
        $transactions = [];
        $lines = preg_split('/\r\n|\r|\n/', trim($content));

        if (empty($lines)) {
            return $transactions;
        }

        // Header detection
        $header = str_getcsv(array_shift($lines));
        $header = array_map(fn($col) => trim(str_replace(['"', "'"], '', $col)), $header);

        $dateIndex = -1;
        $titleIndex = -1;
        $detailsIndex = -1;
        $docIndex = -1;
        $amountIndex = -1;
        $typeIndex = -1;

        foreach ($header as $i => $colName) {
            $normalized = mb_strtolower($colName, 'UTF-8');
            if (str_contains($normalized, 'tipo')) {
                $typeIndex = $i;
            } elseif (str_contains($normalized, 'data') || str_contains($normalized, 'dt')) {
                $dateIndex = $i;
            } elseif (str_contains($normalized, 'detalhe')) {
                $detailsIndex = $i;
            } elseif (str_contains($normalized, 'documento') || str_contains($normalized, 'doc')) {
                $docIndex = $i;
            } elseif (str_contains($normalized, 'valor')) {
                $amountIndex = $i;
            } elseif (str_contains($normalized, 'lan') || str_contains($normalized, 'hist')) {
                $titleIndex = $i;
            }
        }

        // Defaults if exact match wasn't found
        if ($dateIndex === -1) $dateIndex = 0;
        if ($titleIndex === -1) $titleIndex = 1;
        if ($detailsIndex === -1) $detailsIndex = 2;
        if ($docIndex === -1) $docIndex = 3;
        if ($amountIndex === -1) $amountIndex = 4;

        foreach ($lines as $line) {
            if (empty(trim($line))) continue;

            $row = str_getcsv($line);
            if (count($row) < 3) continue;

            $title = isset($row[$titleIndex]) ? trim($row[$titleIndex]) : '';

            // Ignore header repeated, initial balance, or daily subtotal balances (e.g. "Saldo do dia", "S A L D O")
            if (empty($title) || StatementBalanceSanitizer::isBalanceRow($row) || StatementBalanceSanitizer::isBalanceRow($line)) {
                continue;
            }

            $dateRaw = trim($row[$dateIndex] ?? '');
            if (empty($dateRaw) || str_contains($dateRaw, '00/00/0000')) {
                continue;
            }

            try {
                $date = Carbon::createFromFormat('d/m/Y', $dateRaw)->format('Y-m-d');
            } catch (\Throwable) {
                try {
                    $date = Carbon::parse($dateRaw)->format('Y-m-d');
                } catch (\Throwable) {
                    continue; // Skip invalid row
                }
            }

            $details = isset($row[$detailsIndex]) ? trim($row[$detailsIndex]) : '';
            $docNumber = isset($row[$docIndex]) ? trim($row[$docIndex]) : null;
            $amountRaw = isset($row[$amountIndex]) ? trim($row[$amountIndex]) : '0';

            // Parse pt-BR money (e.g. "-46,98", "1.250,00")
            $cleanAmount = str_replace('.', '', $amountRaw);
            $cleanAmount = str_replace(',', '.', $cleanAmount);
            $amount = (float) $cleanAmount;

            if ($amount == 0.0) continue;

            // Check if there is an explicit Tipo column (Entrada / Saída / D / C)
            if ($typeIndex !== -1 && isset($row[$typeIndex])) {
                $typeCol = mb_strtolower(trim($row[$typeIndex]), 'UTF-8');
                if ((str_contains($typeCol, 'saíd') || str_contains($typeCol, 'said') || str_starts_with($typeCol, 'd')) && $amount > 0) {
                    $amount = -$amount;
                } elseif ((str_contains($typeCol, 'entrad') || str_starts_with($typeCol, 'c')) && $amount < 0) {
                    $amount = abs($amount);
                }
            }

            $type = $amount > 0 ? TransactionType::Income : TransactionType::Expense;

            // Combine title and details into descriptive text
            $description = $title;
            if (!empty($details) && $details !== $title) {
                $description .= ' - ' . $details;
            }

            $transactions[] = new ParsedTransactionDTO(
                date: $date,
                description: $description,
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
        return BankInstitution::BancoDoBrasil;
    }
}
