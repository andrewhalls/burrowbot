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
        Schema::create('discord_outbound_actions', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // post_giveaway_message | close_giveaway_message
            $table->foreignId('giveaway_id')->constrained()->cascadeOnDelete();
            $table->json('payload');
            $table->string('status')->default('pending'); // pending | acked | failed
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_failure_reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discord_outbound_actions');
    }
};
