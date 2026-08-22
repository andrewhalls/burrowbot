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
        Schema::table('giveaways', function (Blueprint $table) {
            $table->string('winner_message_channel_id')->nullable()->after('discord_message_id');
            $table->text('winner_message_template')->nullable()->after('winner_message_channel_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('giveaways', function (Blueprint $table) {
            $table->dropColumn(['winner_message_channel_id', 'winner_message_template']);
        });
    }
};
