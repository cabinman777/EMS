<?php

namespace App\Services\Import;

final class MpesaStatementParser extends CsvStatementParser
{
    public function source(): string
    {
        return 'mpesa';
    }

    public function parse(string $path): array
    {
        $parsed = [];

        foreach ($this->readRows($path) as [$rowNumber, $fields]) {
            $credit = $this->money($this->value($fields, ['paid in', 'cash in', 'credit']));
            $debit = $this->money($this->value($fields, ['withdrawn', 'cash out', 'debit']));
            $amount = $debit ?? $credit;
            $description = $this->value($fields, ['details', 'description', 'm-pesa code']);
            $date = $this->value($fields, ['completion time', 'transaction date', 'date']);

            $parsed[] = new ParsedStatementRow(
                sourceRowNumber: $rowNumber,
                sourceTransactionId: $this->value($fields, ['receipt no.', 'receipt', 'transaction id']) ?: null,
                occurredAt: $date ?: null,
                amount: $amount,
                direction: $debit !== null ? 'debit' : ($credit !== null ? 'credit' : null),
                description: $description ?: null,
                balance: $this->money($this->value($fields, ['balance', 'm-pesa balance'])),
                sourceFields: $fields,
                issues: $this->issues($date ?: null, $amount, $description ?: null),
            );
        }

        return $parsed;
    }
}
