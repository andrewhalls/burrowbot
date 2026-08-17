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
        Schema::table('standard_giveaways', function (Blueprint $table) {
            $table->string('banner_image_path')->nullable()->after('image_path');
            $table->string('claim_link')->nullable()->after('duration_minutes');
            $table->unsignedSmallInteger('claim_deadline_hours')->nullable()->after('claim_link');
            $table->text('congrats_message_template')->nullable()->after('claim_deadline_hours');
        });

        Schema::table('standard_giveaway_occurrences', function (Blueprint $table) {
            $table->string('banner_image_path')->nullable()->after('image_path');
            $table->string('claim_link')->nullable()->after('duration_minutes');
            $table->unsignedSmallInteger('claim_deadline_hours')->nullable()->after('claim_link');
            $table->text('congrats_message_template')->nullable()->after('claim_deadline_hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('standard_giveaways', function (Blueprint $table) {
            $table->dropColumn(['banner_image_path', 'claim_link', 'claim_deadline_hours', 'congrats_message_template']);
        });

        Schema::table('standard_giveaway_occurrences', function (Blueprint $table) {
            $table->dropColumn(['banner_image_path', 'claim_link', 'claim_deadline_hours', 'congrats_message_template']);
        });
    }
};
