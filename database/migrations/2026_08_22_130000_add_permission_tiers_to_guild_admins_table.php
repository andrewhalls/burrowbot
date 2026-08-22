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
        // user_id needs to become nullable (a pending grant for someone who
        // hasn't logged in yet has no users row to reference until their
        // first login backfills it) - dropped and re-added rather than
        // ->change()'d since this project doesn't install doctrine/dbal.
        Schema::table('guild_admins', function (Blueprint $table) {
            $table->dropUnique('guild_admins_guild_id_user_id_unique');
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('guild_admins', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('guild_id')->constrained()->cascadeOnDelete();
            $table->string('source')->default('discord_sync')->after('role'); // discord_sync | granted
            $table->json('sections')->nullable()->after('source');
            $table->string('discord_user_id')->nullable()->after('user_id');

            $table->unique(['guild_id', 'user_id']);
            $table->unique(['guild_id', 'discord_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guild_admins', function (Blueprint $table) {
            $table->dropUnique(['guild_id', 'discord_user_id']);
            $table->dropUnique(['guild_id', 'user_id']);
            $table->dropColumn(['discord_user_id', 'sections', 'source']);
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('guild_admins', function (Blueprint $table) {
            $table->foreignId('user_id')->after('guild_id')->constrained()->cascadeOnDelete();
            $table->unique(['guild_id', 'user_id']);
        });
    }
};
