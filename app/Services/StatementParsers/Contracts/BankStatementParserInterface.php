<?php

namespace App\Services\StatementParsers\Contracts;

use App\DTOs\ParsedTransactionDTO;
use App\Enums\BankInstitution;

interface BankStatementParserInterface
{
    /**
     * Checks if this parser can handle the provided file contents.
     */
    public function canParse(string $content, string $fileName = ''): bool;

    /**
     * Parses the content into an array of ParsedTransactionDTO.
     *
     * @return array<ParsedTransactionDTO>
     */
    public function parse(string $content): array;

    /**
     * Identifies the bank institution.
     */
    public function getBankIdentifier(): BankInstitution;
}
