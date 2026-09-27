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
        Schema::table('quizzes', function (Blueprint $table) {
            $table->unsignedTinyInteger('passing_score')->nullable()->after('time_limit_minutes');
            $table->unsignedTinyInteger('weight')->default(1)->after('passing_score');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->unsignedTinyInteger('passing_grade')->default(70)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn(['passing_score', 'weight']);
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('passing_grade');
        });
    }
};
