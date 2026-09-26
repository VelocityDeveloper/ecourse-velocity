<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Quizzes are their own entity now, so no lesson may claim that type.
     */
    public function up(): void
    {
        DB::table('lessons')->where('content_type', 'quiz')->update(['content_type' => 'article']);
    }

    public function down(): void
    {
        // The original type cannot be recovered once merged into articles.
    }
};
