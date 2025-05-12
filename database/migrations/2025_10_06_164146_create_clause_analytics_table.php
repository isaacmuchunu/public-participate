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
        Schema::create('clause_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clause_id')->constrained('bill_clauses')->cascadeOnDelete();
            $table->integer('submissions_count')->default(0);
            $table->integer('support_count')->default(0);
            $table->integer('oppose_count')->default(0);
            $table->integer('neutral_count')->default(0);
            $table->json('sentiment_scores')->nullable(); // detailed sentiment breakdown
            $table->json('top_keywords')->nullable(); // most common words in feedback
            $table->timestamp('last_analyzed_at')->nullable();
            $table->timestamps();

            $table->unique('clause_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clause_analytics');
    }
};
