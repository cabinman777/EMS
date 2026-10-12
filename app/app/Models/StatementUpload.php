<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class StatementUpload extends Model { protected $fillable = ['account_id', 'source', 'original_filename', 'storage_key', 'sha256', 'statement_from', 'statement_to']; protected function casts(): array { return []; } }
