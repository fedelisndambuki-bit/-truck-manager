<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subcontractors', function (Blueprint $table) {
            $table->string('subcontractor_code')->nullable()->unique()->after('id');
            $table->string('registration_number')->nullable()->after('company_name');
            $table->string('tax_pin')->nullable()->after('registration_number');
            $table->string('business_license')->nullable()->after('tax_pin');
            $table->string('category')->nullable()->after('business_license'); // General Trade, Specialist, Nominated, Domestic
            $table->foreignId('specialization_id')->nullable()->after('category')->constrained('subcontractor_specializations')->nullOnDelete();
            $table->text('office_address')->nullable()->after('specialization_id');
            $table->string('country')->nullable()->after('office_address');
            $table->string('county')->nullable()->after('country');
            $table->string('city')->nullable()->after('county');
            $table->string('website')->nullable()->after('email');
            $table->string('bank_name')->nullable()->after('website');
            $table->string('bank_account_number')->nullable()->after('bank_name');
            $table->string('bank_branch')->nullable()->after('bank_account_number');
            $table->string('insurance_provider')->nullable()->after('bank_branch');
            $table->string('insurance_policy_number')->nullable()->after('insurance_provider');
            $table->date('insurance_expiry_date')->nullable()->after('insurance_policy_number');
            $table->date('business_license_expiry_date')->nullable()->after('insurance_expiry_date');
            $table->string('nca_registration_number')->nullable()->after('business_license_expiry_date');
            $table->date('nca_expiry_date')->nullable()->after('nca_registration_number');
            $table->string('tax_compliance_status')->default('Unknown')->after('nca_expiry_date'); // Compliant, Non-Compliant, Unknown
            $table->string('safety_compliance_status')->default('Unknown')->after('tax_compliance_status');
            $table->string('quality_compliance_status')->default('Unknown')->after('safety_compliance_status');
            $table->unsignedSmallInteger('years_in_business')->nullable()->after('quality_compliance_status');
            $table->unsignedInteger('number_of_employees')->nullable()->after('years_in_business');
            $table->text('equipment_owned')->nullable()->after('number_of_employees');
        });
    }

    public function down(): void
    {
        Schema::table('subcontractors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('specialization_id');
            $table->dropColumn([
                'subcontractor_code', 'registration_number', 'tax_pin', 'business_license', 'category',
                'office_address', 'country', 'county', 'city', 'website',
                'bank_name', 'bank_account_number', 'bank_branch',
                'insurance_provider', 'insurance_policy_number', 'insurance_expiry_date',
                'business_license_expiry_date', 'nca_registration_number', 'nca_expiry_date',
                'tax_compliance_status', 'safety_compliance_status', 'quality_compliance_status',
                'years_in_business', 'number_of_employees', 'equipment_owned',
            ]);
        });
    }
};
