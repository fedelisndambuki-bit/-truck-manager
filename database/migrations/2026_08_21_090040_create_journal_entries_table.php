<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('journal_number')->unique();
            $table->date('date');
            $table->string('reference')->nullable();
            $table->string('source_module')->default('manual');
            $table->text('description')->nullable();
            $table->string('currency', 3)->default('KES');
            $table->decimal('exchange_rate', 12, 6)->default(1);
            $table->enum('status', ['Draft', 'Posted', 'Reversed', 'Cancelled'])->default('Draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reversed_journal_id')->nullable()->constrained('fin_journal_entries')->nullOnDelete();
            $table->foreignId('financial_period_id')->nullable()->constrained('fin_periods')->nullOnDelete();
            $table->uuid('ledger_transaction_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_journal_entries');
    }
};
