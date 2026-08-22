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
        Schema::create('broadcast_occurrences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('broadcast_id')->constrained()->cascadeOnDelete();

            // Snapshotted from the parent broadcast at generation time, so a
            // later edit to the broadcast only affects occurrences
            // generated afterward (openspec specs/broadcasts).
            $table->text('message_template');
            $table->string('channel_id');

            $table->timestamp('scheduled_post_at');
            $table->string('status')->default('scheduled'); // scheduled | posted
            $table->timestamp('posted_at')->nullable();
            $table->string('discord_message_id')->nullable();
            $table->timestamps();

            $table->unique(['broadcast_id', 'scheduled_post_at']);
            $table->index(['status', 'scheduled_post_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('broadcast_occurrences');
    }
};
