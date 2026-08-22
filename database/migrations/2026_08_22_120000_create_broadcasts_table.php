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
        Schema::create('broadcasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guild_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('message_template');
            $table->string('channel_id');
            $table->string('status')->default('active'); // active | paused | cancelled
            $table->timestamp('archived_at')->nullable();
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
        Schema::dropIfExists('broadcasts');
    }
};
