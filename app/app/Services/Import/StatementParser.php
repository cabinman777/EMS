<?php

namespace App\Services\Import;

interface StatementParser
{
    public function source(): string;

    /** @return list<ParsedStatementRow> */
    public function parse(string $path): array;
}
