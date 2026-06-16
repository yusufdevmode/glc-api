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
        Schema::create('test_attempts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('package_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('test_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamp('start_time')->nullable();

            $table->timestamp('end_time')->nullable();

            $table->integer('score')->nullable();

            $table->enum('status', [
                'not_started',
                'in_progress',
                'finished',
                'expired'
            ])->default('not_started');

            $table->timestamps();

            $table->unique([
                'user_id',
                'package_id',
                'test_id'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_attempts');
    }
};
