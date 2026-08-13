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
        Schema::create('giveaways', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guild_id')->constrained()->cascadeOnDelete();
            $table->foreignId('collection_theme_id')->constrained()->restrictOnDelete();
            $table->string('channel_id');
            $table->unsignedInteger('duration_minutes');
            $table->string('status')->default('draft'); // draft | active | closed
            $table->string('discord_message_id')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['guild_id', 'status']);
            $table->index('ends_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('giveaways');
    }
};
