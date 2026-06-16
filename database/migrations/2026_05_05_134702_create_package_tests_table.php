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
        Schema::create('package_test', function (Blueprint $table) {
            $table->id();

            $table->foreignId('package_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('test_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->integer('sort_order')->default(0);

            $table->timestamps();

            $table->unique([
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
        Schema::dropIfExists('package_tests');
    }
};
