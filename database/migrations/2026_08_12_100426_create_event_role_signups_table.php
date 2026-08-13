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
        Schema::create('event_role_signups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_occurrence_id')->constrained()->cascadeOnDelete();
            $table->foreignId('discord_member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_role_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_waitlisted')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['event_occurrence_id', 'discord_member_id', 'event_role_id'], 'event_role_signups_unique');
            $table->index(['event_occurrence_id', 'event_role_id', 'is_waitlisted'], 'event_role_signups_occurrence_role_waitlisted_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_role_signups');
    }
};
