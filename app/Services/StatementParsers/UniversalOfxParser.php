<?php

namespace App\Services\StatementParsers;

use App\DTOs\ParsedTransactionDTO;
use App\Enums\BankInstitution;
use App\Enums\TransactionType;
use App\Services\StatementParsers\Contracts\BankStatementParserInterface;
use Carbon\Carbon;

class UniversalOfxParser implements BankStatementParserInterface
{
    public function canParse(string $content, string $fileName = ''): bool
    {
        $sample = substr($content, 0, 1000);
        return (
            stripos($sample, '<OFX>') !== false ||
            stripos($sample, '<STMTTRN>') !== false ||
            str_ends_with(strtolower($fileName), '.ofx') ||
            str_ends_with(strtolower($fileName), '.qfx')
        );
    }

    public function parse(string $content): array
    {
        $transactions = [];

        // Match all <STMTTRN>...</STMTTRN> or unclosed <STMTTRN> blocks (SGML OFX 1.0)
        preg_match_all('/<STMTTRN>(.*?)(?=(?:<STMTTRN>|<\/BANKTRANLIST>|\Z))/si', $content, $matches);

        if (empty($matches[1])) {
            return $transactions;
        }

        foreach ($matches[1] as $block) {
            // Extract TRNTYPE
            preg_match('/<TRNTYPE>([^<\r\n]+)/i', $block, $typeMatch);
            // Extract DTPOSTED
            preg_match('/<DTPOSTED>([0-9]{8})/i', $block, $dateMatch);
            // Extract TRNAMT
            preg_match('/<TRNAMT>([^<\r\n]+)/i', $block, $amountMatch);
            // Extract FITID (Doc number)
            preg_match('/<FITID>([^<\r\n]+)/i', $block, $fitidMatch);
            // Extract MEMO or NAME
            preg_match('/<MEMO>([^<\r\n]+)/i', $block, $memoMatch);
            preg_match('/<NAME>([^<\r\n]+)/i', $block, $nameMatch);

            if (empty($dateMatch[1]) || empty($amountMatch[1])) {
                continue;
            }

            try {
                $date = Carbon::createFromFormat('Ymd', $dateMatch[1])->format('Y-m-d');
            } catch (\Throwable) {
                continue;
            }

            $rawAmount = trim($amountMatch[1]);
            $amount = (float) str_replace(',', '.', $rawAmount);

            if ($amount == 0.0) continue;

            $memo = isset($memoMatch[1]) ? trim($memoMatch[1]) : '';
            $name = isset($nameMatch[1]) ? trim($nameMatch[1]) : '';
            $description = !empty($memo) ? $memo : (!empty($name) ? $name : 'Transação Bancária');

            if (StatementBalanceSanitizer::isBalanceRow($description) || StatementBalanceSanitizer::isBalanceRow($block)) {
                continue;
            }

            $docNumber = isset($fitidMatch[1]) ? trim($fitidMatch[1]) : null;
            $type = $amount > 0 ? TransactionType::Income : TransactionType::Expense;

            $transactions[] = new ParsedTransactionDTO(
                date: $date,
                description: $description,
                amount: $amount,
                type: $type,
                documentNumber: $docNumber,
                rawStatementText: trim(preg_replace('/\s+/', ' ', $block)),
            );
        }

        return $transactions;
    }

    public function getBankIdentifier(): BankInstitution
    {
        return BankInstitution::Generic;
    }
}
