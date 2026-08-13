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
        Schema::create('standard_giveaway_prize_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('standard_giveaway_id')->constrained()->cascadeOnDelete();
            $table->foreignId('collection_theme_item_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['standard_giveaway_id', 'collection_theme_item_id'], 'std_giveaway_prize_items_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('standard_giveaway_prize_items');
    }
};
