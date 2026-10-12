<?php

namespace App\Services\Import;

use App\Models\Account;
use App\Models\RawTransaction;
use InvalidArgumentException;

final class ImportAnalysisService
{
    /** @param list<ParsedStatementRow> $rows */
    public function analyse(Account $account, array $rows): array
    {
        $existing = RawTransaction::query()
            ->where('account_id', $account->id)
            ->pluck('id', 'fingerprint')
            ->all();

        $summary = ['total' => 0, 'new' => 0, 'duplicate' => 0, 'invalid' => 0, 'attention' => 0];
        $preview = [];

        foreach ($rows as $row) {
            $summary['total']++;
            $fingerprint = $row->fingerprint((string) $account->id);
            $status = $row->requiresAttention() ? 'invalid' : (isset($existing[$fingerprint]) ? 'duplicate' : 'new');
            $summary[$status]++;
            if ($status === 'new' && $row->sourceTransactionId === null) {
                $summary['attention']++;
            }

            $preview[] = [
                'source_row_number' => $row->sourceRowNumber,
                'source_transaction_id' => $row->sourceTransactionId,
                'occurred_at' => $row->occurredAt,
                'amount' => $row->amount,
                'direction' => $row->direction,
                'description' => $row->description,
                'balance' => $row->balance,
                'source_fields' => $row->sourceFields,
                'issues' => $row->issues,
                'fingerprint' => $fingerprint,
                'status' => $status,
            ];
        }

        return compact('summary', 'preview');
    }

    public function parserFor(string $source): StatementParser
    {
        return match ($source) {
            'mpesa' => app(MpesaStatementParser::class),
            'im_bank' => app(ImBankStatementParser::class),
            default => throw new InvalidArgumentException('Unsupported statement source.'),
        };
    }
}
