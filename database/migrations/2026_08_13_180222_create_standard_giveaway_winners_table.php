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
        Schema::create('standard_giveaway_winners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('standard_giveaway_occurrence_id')
                ->constrained(indexName: 'std_giveaway_winners_occurrence_foreign')
                ->cascadeOnDelete();
            $table->foreignId('standard_giveaway_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('collection_theme_item_id')->constrained()->restrictOnDelete();
            $table->timestamp('drawn_at');

            $table->unique(['standard_giveaway_occurrence_id', 'standard_giveaway_entry_id'], 'std_giveaway_winners_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('standard_giveaway_winners');
    }
};
