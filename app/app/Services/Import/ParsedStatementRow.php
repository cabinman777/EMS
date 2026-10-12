<?php

namespace App\Services\Import;

final readonly class ParsedStatementRow
{
    public function __construct(
        public int $sourceRowNumber,
        public ?string $sourceTransactionId,
        public ?string $occurredAt,
        public ?float $amount,
        public ?string $direction,
        public ?string $description,
        public ?float $balance,
        public array $sourceFields,
        public array $issues = [],
    ) {}

    public function fingerprint(string $accountId): string
    {
        return hash('sha256', implode('|', [
            $accountId,
            $this->occurredAt ?? '',
            number_format($this->amount ?? 0, 2, '.', ''),
            $this->direction ?? '',
            mb_strtolower(preg_replace('/\s+/', ' ', trim($this->description ?? ''))),
            $this->sourceTransactionId ?? '',
        ]));
    }

    public function requiresAttention(): bool
    {
        return $this->issues !== [];
    }
}
