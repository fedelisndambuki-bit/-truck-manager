<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('role_slot'); // Project Director, Project Manager, Site Engineer, ...
            $table->timestamps();

            $table->unique(['project_id', 'role_slot']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_team_members');
    }
};
