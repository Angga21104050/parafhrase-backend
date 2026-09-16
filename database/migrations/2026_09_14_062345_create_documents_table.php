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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('penjoki_id')
                ->nullable()
                ->constrained('users');
                
            $table->string('original_filename');
            $table->string('original_path');

            $table->string('result_filename')->nullable();
            $table->string('result_path')->nullable();

            $table->text('customer_note')->nullable();

            $table->enum('status', [
                'PENDING',
                'IN_PROGRESS',
                'COMPLETED',
                'CANCELLED'
            ])->default('PENDING');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
