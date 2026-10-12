<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('name')->unique();
            $table->string('provider');
            $table->string('account_identifier')->nullable();
            $table->boolean('active')->default(true);
        });
        Schema::table('statement_uploads', function (Blueprint $table) {
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->string('source');
            $table->string('original_filename');
            $table->string('storage_key')->unique();
            $table->string('sha256', 64);
            $table->date('statement_from')->nullable();
            $table->date('statement_to')->nullable();
        });
        Schema::table('import_batches', function (Blueprint $table) {
            $table->foreignId('statement_upload_id')->constrained()->cascadeOnDelete();
            $table->string('parser_name');
            $table->string('parser_version');
            $table->string('status')->default('uploaded');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('new_rows')->default(0);
            $table->unsignedInteger('duplicate_rows')->default(0);
            $table->unsignedInteger('invalid_rows')->default(0);
            $table->unsignedInteger('attention_rows')->default(0);
            $table->timestampTz('analysed_at')->nullable();
            $table->timestampTz('confirmed_at')->nullable();
        });
        Schema::table('raw_transactions', function (Blueprint $table) {
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('source_row_number');
            $table->string('source_transaction_id')->nullable();
            $table->jsonb('source_fields');
            $table->jsonb('canonical_payload');
            $table->string('fingerprint', 64);
            $table->jsonb('validation_issues')->default('[]');
            $table->unique(['account_id', 'fingerprint']);
            $table->unique(['account_id', 'source_transaction_id']);
        });
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('raw_transaction_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->timestampTz('occurred_at');
            $table->decimal('amount', 16, 2);
            $table->string('direction');
            $table->string('transaction_type')->default('unknown');
            $table->text('description');
            $table->string('reference')->nullable();
            $table->string('counterparty')->nullable();
            $table->decimal('balance', 16, 2)->nullable();
            $table->index(['account_id', 'occurred_at', 'transaction_type']);
        });
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->unique(['parent_id', 'name']);
        });
        Schema::table('transaction_classifications', function (Blueprint $table) {
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->text('note')->nullable();
            $table->string('project')->nullable();
            $table->jsonb('tags')->default('[]');
            $table->string('classification_status')->default('unreviewed');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->index(['transaction_id', 'created_at']);
        });
        Schema::table('transfers', function (Blueprint $table) {
            $table->foreignId('outgoing_transaction_id')->nullable()->unique()->constrained('transactions')->nullOnDelete();
            $table->foreignId('incoming_transaction_id')->nullable()->unique()->constrained('transactions')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->text('note')->nullable();
        });
    }
    public function down(): void {
        throw new RuntimeException('This additive migration requires a reviewed rollback.');
    }
};
