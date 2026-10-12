<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\ImportBatch;
use App\Models\RawTransaction;
use App\Models\StatementUpload;
use App\Models\Transaction;
use App\Services\Import\ImportAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportController extends Controller
{
    public function create()
    {
        return view('imports.create', ['accounts' => Account::where('active', true)->orderBy('name')->get()]);
    }

    public function analyse(Request $request, ImportAnalysisService $analysis)
    {
        $data = $request->validate(['account_id' => ['required', 'exists:accounts,id'], 'statement' => ['required', 'file', 'mimes:csv,txt', 'max:10240']]);
        $account = Account::findOrFail($data['account_id']);
        $file = $request->file('statement');
        $path = $file->storeAs('statement-staging', Str::uuid().'.csv', 'local');
        $result = $analysis->analyse($account, $analysis->parserFor($account->provider)->parse(Storage::disk('local')->path($path)));
        session(['import_preview' => ['account_id' => $account->id, 'source' => $account->provider, 'path' => $path, 'filename' => $file->getClientOriginalName(), 'sha256' => hash_file('sha256', Storage::disk('local')->path($path)), 'result' => $result]]);
        return view('imports.preview', compact('account', 'result'));
    }

    public function confirm()
    {
        $preview = session('import_preview');
        abort_unless($preview, 422, 'No import preview is available.');
        $account = Account::findOrFail($preview['account_id']);
        DB::transaction(function () use ($account, $preview) {
            $upload = StatementUpload::create(['account_id'=>$account->id,'source'=>$preview['source'],'original_filename'=>$preview['filename'],'storage_key'=>$preview['path'],'sha256'=>$preview['sha256']]);
            $batch = ImportBatch::create(['statement_upload_id'=>$upload->id,'parser_name'=>$preview['source'],'parser_version'=>'1.0','status'=>'confirmed','total_rows'=>$preview['result']['summary']['total'],'new_rows'=>$preview['result']['summary']['new'],'duplicate_rows'=>$preview['result']['summary']['duplicate'],'invalid_rows'=>$preview['result']['summary']['invalid'],'attention_rows'=>$preview['result']['summary']['attention'],'analysed_at'=>now(),'confirmed_at'=>now()]);
            foreach ($preview['result']['preview'] as $row) {
                if ($row['status'] !== 'new') continue;
                $raw = RawTransaction::create(['import_batch_id'=>$batch->id,'account_id'=>$account->id,'source_row_number'=>$row['source_row_number'],'source_transaction_id'=>$row['source_transaction_id'],'source_fields'=>$row['source_fields'],'canonical_payload'=>$row,'fingerprint'=>$row['fingerprint'],'validation_issues'=>$row['issues']]);
                Transaction::create(['raw_transaction_id'=>$raw->id,'account_id'=>$account->id,'occurred_at'=>$row['occurred_at'],'amount'=>$row['amount'],'direction'=>$row['direction'],'transaction_type'=>$this->typeFor($row['description']),'description'=>$row['description'],'reference'=>$row['source_transaction_id'],'balance'=>$row['balance']]);
            }
        });
        session()->forget('import_preview');
        return redirect()->route('imports.create')->with('status', 'Import confirmed.');
    }

    private function typeFor(string $description): string
    {
        $value = strtolower($description);
        return str_contains($value, 'excise') ? 'tax' : (str_contains($value, 'charge') ? 'bank_fee' : (str_contains($value, 'mpesa payment') ? 'transfer' : 'unknown'));
    }
}
