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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guild_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_role_set_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('channel_id');
            $table->string('posting_mode')->default('message'); // thread | message
            $table->string('status')->default('active'); // active | paused | cancelled
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
        Schema::dropIfExists('events');
    }
};
