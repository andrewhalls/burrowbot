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
        // The composite unique(guild_id, user_id) index we're about to drop
        // is also the only index with guild_id as its leftmost column -
        // InnoDB requires the FK'd column of every foreign key to be the
        // leftmost column of *some* index at all times, so MySQL refuses to
        // drop it out from under the guild_id -> guilds foreign key. This
        // temporary single-column index stands in for it until the
        // composite unique is recreated below (which itself covers
        // guild_id again), at which point it's dropped as redundant.
        Schema::table('guild_admins', function (Blueprint $table) {
            $table->index('guild_id', 'guild_admins_guild_id_temp_index');
        });

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

        Schema::table('guild_admins', function (Blueprint $table) {
            $table->dropIndex('guild_admins_guild_id_temp_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Same InnoDB constraint as up() - keep a guild_id-leftmost index
        // alive across the drop/recreate of the composite unique indexes.
        Schema::table('guild_admins', function (Blueprint $table) {
            $table->index('guild_id', 'guild_admins_guild_id_temp_index');
        });

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

        Schema::table('guild_admins', function (Blueprint $table) {
            $table->dropIndex('guild_admins_guild_id_temp_index');
        });
    }
};
