<?php

namespace App\Services\StatementParsers;

class StatementBalanceSanitizer
{
    /**
     * Patterns that clearly identify a balance/subtotal summary line rather than a real transaction.
     */
    protected static array $balancePatterns = [
        '/\bsaldo\b/i',
        '/\bs\s*a\s*l\s*d\s*o\b/i',
        '/saldo\s+(do\s+dia|anterior|final|atual|bloqueado|dispon[ií]vel|parcial|projetado)/i',
        '/subtotal/i',
        '/total\s+do\s+dia/i',
        '/posi[cç][aã]o\s+consolidada/i',
    ];

    /**
     * Checks if a given text or array of row fields represents a balance/subtotal row.
     */
    public static function isBalanceRow(string|array $row): bool
    {
        $textToAnalyze = is_array($row) ? implode(' ', $row) : $row;

        // Normalize spaces and multiple spaces
        $normalized = trim(preg_replace('/\s+/', ' ', $textToAnalyze));

        foreach (self::$balancePatterns as $pattern) {
            if (preg_match($pattern, $normalized)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Attempts to extract a numeric balance value from a balance summary line.
     * Useful for extracting the final closing balance of the statement.
     */
    public static function extractBalanceValue(string|array $row): ?float
    {
        if (!self::isBalanceRow($row)) {
            return null;
        }

        $fields = is_array($row) ? $row : str_getcsv($row);

        foreach (array_reverse($fields) as $field) {
            $clean = preg_replace('/[^\d,\.\-]/', '', trim($field));
            if (empty($clean) || !preg_match('/\d/', $clean)) {
                continue;
            }

            // Brazilian format: 1.250,50
            if (str_contains($clean, ',') && str_contains($clean, '.')) {
                $clean = str_replace('.', '', $clean);
                $clean = str_replace(',', '.', $clean);
            } elseif (str_contains($clean, ',')) {
                $clean = str_replace(',', '.', $clean);
            }

            if (is_numeric($clean)) {
                return (float) $clean;
            }
        }

        return null;
    }
}
