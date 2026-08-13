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
        Schema::create('standard_giveaway_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('standard_giveaway_occurrence_id')
                ->constrained(indexName: 'std_giveaway_entries_occurrence_foreign')
                ->cascadeOnDelete();
            $table->foreignId('discord_member_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['standard_giveaway_occurrence_id', 'discord_member_id'], 'std_giveaway_entries_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('standard_giveaway_entries');
    }
};
