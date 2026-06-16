<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_answers', function (Blueprint $table) {
            $table->dropUnique('user_answers_test_attempt_id_question_id_unique');

            $table->unique([
                'test_attempt_id',
                'question_id',
                'option_id',
            ], 'user_answers_attempt_question_option_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_answers', function (Blueprint $table) {
            $table->dropUnique('user_answers_attempt_question_option_unique');

            $table->unique([
                'test_attempt_id',
                'question_id',
            ]);
        });
    }
};
