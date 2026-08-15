<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete(); // null = platform-seeded template, visible to every tenant
            $table->string('name');
            $table->string('internal_key')->nullable();
            $table->text('description')->nullable();
            $table->string('module')->default('Core'); // Construction, Fleet, HR, Finance, Quarry, Core, ...
            $table->string('entity_type')->nullable(); // FQCN of the model this workflow applies to
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('industry')->nullable(); // null = all industries
            $table->string('status')->default('Draft'); // Draft, Published, Archived
            $table->unsignedInteger('version')->default(1);
            $table->date('effective_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('definition')->nullable(); // full Drawflow-exported graph: nodes + edges + trigger config
            $table->json('node_types_used')->nullable(); // denormalized, recomputed on save — e.g. ["approval_step","condition"]
            $table->timestamps();

            $table->index(['module', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_definitions');
    }
};
