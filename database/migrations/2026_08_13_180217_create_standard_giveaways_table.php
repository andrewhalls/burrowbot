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
        Schema::create('standard_giveaways', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guild_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('channel_id');
            $table->string('posting_mode')->default('message'); // thread | message
            $table->string('status')->default('active'); // active | paused | cancelled
            $table->unsignedInteger('winner_count')->default(1);
            $table->boolean('requires_booster')->default(false);
            $table->unsignedInteger('duration_minutes');
            $table->text('recurrence_rule')->nullable(); // RFC 5545 RRULE string; null = one-off
            $table->timestamp('recurrence_start_at')->nullable();
            $table->string('recurrence_timezone')->nullable();
            $table->timestamps();

            $table->index(['guild_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('standard_giveaways');
    }
};
