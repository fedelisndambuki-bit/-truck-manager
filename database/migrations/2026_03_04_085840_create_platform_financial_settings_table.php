<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('platform_financial_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->decimal('value', 15, 2);
            $table->string('type')->default('decimal'); // fixed_cost, variable_cost, revenue_target, etc.
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Seed with some initial values (Actual values you can edit later)
        DB::table('platform_financial_settings')->insert([
            ['key' => 'fixed_monthly_platform_cost', 'value' => 450000.00, 'type' => 'fixed_cost', 'description' => 'Rent, salaries, core infrastructure'],
            ['key' => 'variable_cloud_cost_per_vehicle', 'value' => 150.00, 'type' => 'variable_cost', 'description' => 'Estimated monthly AWS/Server cost per vehicle'],
            ['key' => 'variable_sms_cost_per_vehicle', 'value' => 50.00, 'type' => 'variable_cost', 'description' => 'Estimated monthly SMS cost per vehicle'],
            ['key' => 'acquisition_cost_per_company', 'value' => 2000.00, 'type' => 'cac', 'description' => 'Marketing spend to acquire one company'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_financial_settings');
    }
};
