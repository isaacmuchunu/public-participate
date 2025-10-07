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
        Schema::create('comment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('bill_id')->constrained()->onDelete('cascade');
            $table->foreignId('clause_id')->constrained()->onDelete('cascade');
            $table->enum('submission_type', ['support', 'oppose', 'neutral', 'amendment', 'comment'])->default('comment');
            $table->integer('content_length');
            $table->string('ip_address');
            $table->timestamp('submitted_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comment_submissions');
    }
};
