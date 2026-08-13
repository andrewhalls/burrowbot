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
        Schema::create('event_occurrences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();

            // Snapshotted from the parent event at generation time, so a
            // later edit to the event only affects occurrences generated
            // afterward (openspec specs/events).
            $table->string('title');
            $table->text('description');
            $table->string('channel_id');
            $table->string('posting_mode'); // thread | message
            $table->foreignId('event_role_set_id')->constrained()->restrictOnDelete();

            $table->timestamp('scheduled_start_at');
            $table->string('status')->default('scheduled'); // scheduled | posted | completed | cancelled
            $table->string('discord_thread_id')->nullable();
            $table->string('discord_message_id')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'scheduled_start_at']);
            $table->index(['status', 'scheduled_start_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_occurrences');
    }
};
