<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RawTransaction extends Model { protected $fillable = ['import_batch_id', 'account_id', 'source_row_number', 'source_transaction_id', 'source_fields', 'canonical_payload', 'fingerprint', 'validation_issues']; protected function casts(): array { return ['source_fields'=>'array','canonical_payload'=>'array','validation_issues'=>'array']; } }
