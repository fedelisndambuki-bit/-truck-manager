<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('driver_company_chat_messages', function (Blueprint $table) {
            $table->timestamp('edited_at')->nullable()->after('read_at');
            $table->timestamp('deleted_for_everyone_at')->nullable()->after('edited_at');
            $table->timestamp('deleted_for_driver_at')->nullable()->after('deleted_for_everyone_at');
            $table->timestamp('deleted_for_company_at')->nullable()->after('deleted_for_driver_at');
        });

        Schema::create('call_signals', function (Blueprint $table) {
            $table->id();
            $table->string('room', 128)->index();
            $table->string('sender', 64)->nullable()->index();
            $table->string('type', 32);
            $table->longText('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_signals');

        Schema::table('driver_company_chat_messages', function (Blueprint $table) {
            $table->dropColumn([
                'edited_at',
                'deleted_for_everyone_at',
                'deleted_for_driver_at',
                'deleted_for_company_at',
            ]);
        });
    }
};
