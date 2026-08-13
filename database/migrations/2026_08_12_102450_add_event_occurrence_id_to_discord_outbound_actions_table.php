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
        Schema::table('discord_outbound_actions', function (Blueprint $table) {
            $table->foreignId('giveaway_id')->nullable()->change();
            $table->foreignId('event_occurrence_id')->nullable()->after('giveaway_id')->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discord_outbound_actions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('event_occurrence_id');
            $table->foreignId('giveaway_id')->nullable(false)->change();
        });
    }
};
