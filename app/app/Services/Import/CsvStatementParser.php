<?php

namespace App\Services\Import;

use RuntimeException;

abstract class CsvStatementParser implements StatementParser
{
    /** @return list<array{0:int,1:array<string,string>}> */
    protected function readRows(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('The statement file could not be read.');
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            throw new RuntimeException('The statement must contain a header row.');
        }

        $header = array_map(fn ($value) => mb_strtolower(trim((string) $value)), $header);
        $rows = [];
        $rowNumber = 1;

        while (($values = fgetcsv($handle)) !== false) {
            $rowNumber++;
            $values = array_pad($values, count($header), '');
            $fields = array_combine($header, $values);

            if ($fields !== false && array_filter($fields, fn ($value) => trim((string) $value) !== '') !== []) {
                $rows[] = [$rowNumber, $fields];
            }
        }

        fclose($handle);

        return $rows;
    }

    protected function value(array $fields, array $aliases): string
    {
        foreach ($aliases as $alias) {
            if (isset($fields[$alias]) && trim($fields[$alias]) !== '') {
                return trim($fields[$alias]);
            }
        }

        return '';
    }

    protected function money(string $value): ?float
    {
        $normalised = str_ireplace(['KES', 'KSh', ',', ' '], '', $value);

        return $normalised === '' ? null : (float) $normalised;
    }

    /** @return list<string> */
    protected function issues(?string $date, ?float $amount, ?string $description): array
    {
        $issues = [];
        if (!$date) $issues[] = 'Missing transaction date.';
        if ($amount === null || $amount <= 0) $issues[] = 'Missing or non-positive amount.';
        if (!$description) $issues[] = 'Missing transaction description.';

        return $issues;
    }
}
