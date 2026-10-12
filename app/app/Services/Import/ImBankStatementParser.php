<?php

namespace App\Services\Import;

final class ImBankStatementParser extends CsvStatementParser
{
    public function source(): string
    {
        return 'im_bank';
    }

    public function parse(string $path): array
    {
        $parsed = [];

        foreach ($this->readRows($path) as [$rowNumber, $fields]) {
            $credit = $this->money($this->value($fields, ['credit', 'cash in']));
            $debit = $this->money($this->value($fields, ['debit', 'cash out']));
            $amount = $debit ?? $credit;
            $description = $this->value($fields, ['i&m description', 'description']);
            $date = $this->value($fields, ['transaction date', 'date']);
            $reference = $this->value($fields, ['i&m ref', 'reference', 'transaction id']);

            $issues = $this->issues($date ?: null, $amount, $description ?: null);
            if ($reference === '') {
                $issues[] = 'Missing I&M reference; fallback fingerprint will be used.';
            }

            $parsed[] = new ParsedStatementRow(
                sourceRowNumber: $rowNumber,
                sourceTransactionId: $reference ?: null,
                occurredAt: $date ?: null,
                amount: $amount,
                direction: $debit !== null ? 'debit' : ($credit !== null ? 'credit' : null),
                description: $description ?: null,
                balance: $this->money($this->value($fields, ['balance'])),
                sourceFields: $fields,
                issues: $issues,
            );
        }

        return $parsed;
    }
}
