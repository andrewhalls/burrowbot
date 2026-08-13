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
        Schema::create('event_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_occurrence_id')->constrained()->cascadeOnDelete();
            $table->foreignId('discord_member_id')->constrained()->cascadeOnDelete();
            $table->string('status'); // attending | not_attending
            $table->timestamps();

            $table->unique(['event_occurrence_id', 'discord_member_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_attendances');
    }
};
