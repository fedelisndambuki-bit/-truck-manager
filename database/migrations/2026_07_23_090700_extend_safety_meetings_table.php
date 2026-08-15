<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('safety_meetings', function (Blueprint $table) {
            $table->text('agenda')->nullable()->after('title');
            $table->text('minutes')->nullable()->after('notes');
            $table->json('attachments')->nullable()->after('minutes');
        });

        Schema::create('safety_meeting_attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('safety_meeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('attendee_name')->nullable();
            $table->timestamps();
        });

        Schema::create('safety_meeting_action_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('safety_meeting_id')->constrained()->cascadeOnDelete();
            $table->text('description');
            $table->foreignId('responsible_staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->string('status')->default('Open'); // Open, In Progress, Done
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safety_meeting_action_items');
        Schema::dropIfExists('safety_meeting_attendees');
        Schema::table('safety_meetings', function (Blueprint $table) {
            $table->dropColumn(['agenda', 'minutes', 'attachments']);
        });
    }
};
