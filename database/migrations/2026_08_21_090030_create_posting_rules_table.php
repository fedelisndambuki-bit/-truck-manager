<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_posting_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('event_key')->index();
            $table->integer('debit_account_code')->nullable();
            $table->integer('credit_account_code')->nullable();
            $table->string('debit_wallet_type')->nullable();
            $table->string('credit_wallet_type')->nullable();
            $table->string('resolver_class')->nullable();
            $table->string('description_template')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_posting_rules');
    }
};
