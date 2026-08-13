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
        Schema::create('standard_giveaway_required_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('standard_giveaway_id')->constrained()->cascadeOnDelete();
            $table->string('discord_role_id');
            $table->timestamps();

            $table->unique(['standard_giveaway_id', 'discord_role_id'], 'std_giveaway_required_roles_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('standard_giveaway_required_roles');
    }
};
