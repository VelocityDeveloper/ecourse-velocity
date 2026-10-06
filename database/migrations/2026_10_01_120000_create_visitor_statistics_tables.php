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
        // One row per day: page visits and distinct visitors, the footer's running totals.
        Schema::create('visitor_days', function (Blueprint $table) {
            $table->date('date')->primary();
            $table->unsignedInteger('visits')->default(0);
            $table->unsignedInteger('visitors')->default(0);
        });

        // Who was seen on which day (a hashed IP, never the IP itself) and when last, for "online".
        Schema::create('visitor_presences', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->char('visitor', 40);
            $table->dateTime('last_seen_at')->index();

            $table->unique(['date', 'visitor']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitor_presences');
        Schema::dropIfExists('visitor_days');
    }
};
