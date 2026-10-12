<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Transaction extends Model { protected $fillable = ['raw_transaction_id', 'account_id', 'occurred_at', 'amount', 'direction', 'transaction_type', 'description', 'reference', 'counterparty', 'balance']; protected function casts(): array { return []; } }
