<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_question_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_question_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('correct_count');
            $table->unsignedInteger('points')->default(0);
            $table->timestamps();

            $table->unique(['quiz_question_id', 'correct_count']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_question_scores');
    }
};
