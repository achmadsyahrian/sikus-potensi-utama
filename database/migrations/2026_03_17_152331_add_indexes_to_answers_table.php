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
        Schema::table('answers', function (Blueprint $table) {
            $table->index('questionnaire_id', 'idx_answers_questionnaire_id');
            $table->index('user_id', 'idx_answers_user_id');
            $table->index(['user_id', 'questionnaire_id'], 'idx_answers_user_questionnaire');
            $table->index(['questionnaire_id', 'question_id'], 'idx_answers_questionnaire_question');
        });
    }

    public function down(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            $table->dropIndex('idx_answers_questionnaire_id');
            $table->dropIndex('idx_answers_user_id');
            $table->dropIndex('idx_answers_user_questionnaire');
            $table->dropIndex('idx_answers_questionnaire_question');
        });
    }

};
