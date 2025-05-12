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
        Schema::create('bill_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->constrained()->onDelete('cascade');
            $table->text('simplified_summary_en'); // English summary
            $table->text('simplified_summary_sw')->nullable(); // Kiswahili summary
            $table->json('key_clauses'); // Array of key clauses with impact
            $table->string('audio_path_en')->nullable(); // Path to English audio
            $table->string('audio_path_sw')->nullable(); // Path to Kiswahili audio
            $table->enum('generation_method', ['ai', 'manual'])->default('manual');
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_summaries');
    }
};
