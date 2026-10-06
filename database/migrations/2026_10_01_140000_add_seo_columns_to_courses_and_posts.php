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
        // Optional search-result texts; empty means the title and description are used.
        foreach (['courses', 'posts'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('seo_title', 70)->nullable();
                $table->string('seo_description', 300)->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['courses', 'posts'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn(['seo_title', 'seo_description']);
            });
        }
    }
};
