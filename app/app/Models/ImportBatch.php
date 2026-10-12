<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ImportBatch extends Model { protected $fillable = ['statement_upload_id', 'parser_name', 'parser_version', 'status', 'total_rows', 'new_rows', 'duplicate_rows', 'invalid_rows', 'attention_rows', 'analysed_at', 'confirmed_at']; protected function casts(): array { return []; } }
