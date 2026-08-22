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
        Schema::table('guilds', function (Blueprint $table) {
            $table->boolean('popup_giveaway_winner_messages_enabled')->default(true)->after('is_active');
        });

        Schema::table('standard_giveaways', function (Blueprint $table) {
            $table->string('per_winner_message_channel_id')->nullable()->after('congrats_message_template');
            $table->text('per_winner_message_template')->nullable()->after('per_winner_message_channel_id');
        });

        // Snapshotted onto each occurrence at generation time, same pattern
        // as congrats_message_template/claim_link/claim_deadline_hours.
        Schema::table('standard_giveaway_occurrences', function (Blueprint $table) {
            $table->string('per_winner_message_channel_id')->nullable()->after('congrats_message_template');
            $table->text('per_winner_message_template')->nullable()->after('per_winner_message_channel_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guilds', function (Blueprint $table) {
            $table->dropColumn('popup_giveaway_winner_messages_enabled');
        });

        Schema::table('standard_giveaways', function (Blueprint $table) {
            $table->dropColumn(['per_winner_message_channel_id', 'per_winner_message_template']);
        });

        Schema::table('standard_giveaway_occurrences', function (Blueprint $table) {
            $table->dropColumn(['per_winner_message_channel_id', 'per_winner_message_template']);
        });
    }
};
