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
            $table->foreignId('standard_giveaway_occurrence_id')
                ->nullable()
                ->after('event_occurrence_id')
                ->constrained()
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discord_outbound_actions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('standard_giveaway_occurrence_id');
        });
    }
};
