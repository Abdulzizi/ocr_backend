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
        Schema::create('ocr_jobs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('filename');
            $table->string('status')->default('pending'); //! pending, processing, done, failed
            $table->text('ocr_text')->nullable();
            $table->string('result_path')->nullable();
            $table->integer('credit_used')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ocr_jobs');
    }
};
