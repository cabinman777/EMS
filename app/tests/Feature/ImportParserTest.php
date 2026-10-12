<?php

namespace Tests\Feature;

use App\Services\Import\ImBankStatementParser;
use App\Services\Import\MpesaStatementParser;
use Tests\TestCase;

class ImportParserTest extends TestCase
{
    public function test_mpesa_parser_preserves_receipt_and_debit(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'mpesa');
        file_put_contents($path, "Receipt No.,Completion Time,Details,Withdrawn,Balance\nRCP1,2026-10-05 18:45,Merchant Payment,1250.00,500.00\n");

        $row = (new MpesaStatementParser())->parse($path)[0];

        $this->assertSame('RCP1', $row->sourceTransactionId);
        $this->assertSame('debit', $row->direction);
        $this->assertSame(1250.0, $row->amount);
        $this->assertSame('Merchant Payment', $row->description);
    }

    public function test_im_bank_fee_rows_keep_shared_reference_as_separate_records(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'imbank');
        file_put_contents($path, "I&M Ref,Date,I&M Description,Debit,Balance\nS6163231,2026-09-14,MPESA Payment to 254702119146,48700,458645.95\nS6163231,2026-09-14,Charge/MPESA Payment to 254702119146,65,458580.95\n");

        $rows = (new ImBankStatementParser())->parse($path);

        $this->assertCount(2, $rows);
        $this->assertSame('S6163231', $rows[0]->sourceTransactionId);
        $this->assertSame('S6163231', $rows[1]->sourceTransactionId);
        $this->assertNotSame($rows[0]->fingerprint('1'), $rows[1]->fingerprint('1'));
    }
}
