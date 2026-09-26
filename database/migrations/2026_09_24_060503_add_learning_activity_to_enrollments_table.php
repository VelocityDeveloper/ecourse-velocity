<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreignId('last_lesson_id')->nullable()->after('cancellation_reason')->constrained('lessons')->nullOnDelete();
            $table->timestamp('last_accessed_at')->nullable()->after('last_lesson_id');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('last_lesson_id');
            $table->dropColumn('last_accessed_at');
        });
    }
};
