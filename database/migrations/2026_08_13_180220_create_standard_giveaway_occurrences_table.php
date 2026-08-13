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
        Schema::create('standard_giveaway_occurrences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('standard_giveaway_id')->constrained()->cascadeOnDelete();

            // Snapshotted from the parent giveaway at generation time, so a
            // later edit to the giveaway only affects occurrences generated
            // afterward (openspec specs/standard-giveaways; design.md
            // Decision 2 and Risks).
            $table->string('title');
            $table->text('description');
            $table->string('channel_id');
            $table->string('posting_mode'); // thread | message
            $table->boolean('requires_booster');
            $table->unsignedInteger('winner_count');
            $table->unsignedInteger('duration_minutes');
            $table->json('prize_item_ids');
            $table->json('required_role_ids');

            $table->timestamp('scheduled_post_at');
            $table->string('status')->default('scheduled'); // scheduled | posted | closed
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('discord_thread_id')->nullable();
            $table->string('discord_message_id')->nullable();
            $table->timestamps();

            $table->unique(['standard_giveaway_id', 'scheduled_post_at'], 'std_giveaway_occurrences_unique');
            $table->index(['status', 'ends_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('standard_giveaway_occurrences');
    }
};
