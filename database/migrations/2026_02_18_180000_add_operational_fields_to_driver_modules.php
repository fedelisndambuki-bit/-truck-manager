<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('driver_pods', function (Blueprint $table) {
            $table->string('receiver_name')->nullable();
            $table->string('receiver_signature')->nullable();
            $table->text('pickup_details')->nullable();
            $table->text('dropoff_details')->nullable();
            $table->longText('document_ocr_text')->nullable();
        });

        Schema::table('driver_company_chat_messages', function (Blueprint $table) {
            $table->string('voice_note_path')->nullable();
        });

        Schema::table('driver_incidents', function (Blueprint $table) {
            $table->string('severity')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
        });

        Schema::create('driver_pre_trip_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trip_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('brakes')->default(false);
            $table->boolean('lights')->default(false);
            $table->boolean('tires')->default(false);
            $table->boolean('load_secured')->default(false);
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['driver_id', 'submitted_at']);
        });

        Schema::create('driver_emergency_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trip_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('sos');
            $table->string('status')->default('open');
            $table->text('message')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamp('triggered_at')->nullable();
            $table->timestamps();

            $table->index(['driver_id', 'triggered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_emergency_alerts');
        Schema::dropIfExists('driver_pre_trip_inspections');

        Schema::table('driver_incidents', function (Blueprint $table) {
            $table->dropColumn(['severity', 'latitude', 'longitude']);
        });

        Schema::table('driver_company_chat_messages', function (Blueprint $table) {
            $table->dropColumn('voice_note_path');
        });

        Schema::table('driver_pods', function (Blueprint $table) {
            $table->dropColumn([
                'receiver_name',
                'receiver_signature',
                'pickup_details',
                'dropoff_details',
                'document_ocr_text',
            ]);
        });
    }
};
