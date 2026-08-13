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
        Schema::create('giveaway_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('giveaway_id')->constrained()->cascadeOnDelete();
            $table->foreignId('discord_member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('collection_theme_item_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('fulfilled_at')->nullable();
            $table->foreignId('fulfilled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            // Enforces one entry per member per giveaway at the database
            // level, so concurrent join clicks can never double-enter.
            $table->unique(['giveaway_id', 'discord_member_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('giveaway_entries');
    }
};
