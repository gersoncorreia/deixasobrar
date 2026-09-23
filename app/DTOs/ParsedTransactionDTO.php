<?php

namespace App\DTOs;

use App\Enums\TransactionType;

class ParsedTransactionDTO
{
    public function __construct(
        public string $date, // Y-m-d format
        public string $description,
        public float $amount, // negative for expense, positive for income
        public TransactionType $type,
        public ?string $documentNumber = null,
        public ?string $rawStatementText = null,
        public bool $isLeak = false,
        public ?string $suggestedCategory = null,
        public ?string $leakReason = null,
    ) {}

    public function toArray(): array
    {
        return [
            'date' => $this->date,
            'description' => $this->description,
            'amount' => $this->amount,
            'type' => $this->type->value,
            'document_number' => $this->documentNumber,
            'raw_statement_text' => $this->rawStatementText,
            'is_leak' => $this->isLeak,
            'suggested_category' => $this->suggestedCategory,
            'leak_reason' => $this->leakReason,
        ];
    }
}
