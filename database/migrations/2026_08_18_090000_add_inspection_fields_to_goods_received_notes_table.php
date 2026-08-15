<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goods_received_notes', function (Blueprint $table) {
            $table->json('checklist')->nullable()->after('inspection_notes');
            $table->json('photos')->nullable()->after('checklist');
            $table->string('delivery_note_reference')->nullable()->after('photos');
            $table->string('invoice_reference')->nullable()->after('delivery_note_reference');
        });
    }

    public function down(): void
    {
        Schema::table('goods_received_notes', function (Blueprint $table) {
            $table->dropColumn(['checklist', 'photos', 'delivery_note_reference', 'invoice_reference']);
        });
    }
};
