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
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('bill_number')->unique();
            $table->text('description');
            $table->enum('type', ['public', 'private', 'money']);
            $table->enum('house', ['national_assembly', 'senate', 'both']);
            $table->enum('status', ['draft', 'gazetted', 'open_for_participation', 'closed', 'committee_review', 'passed', 'rejected'])->default('draft');
            $table->string('sponsor')->nullable();
            $table->string('committee')->nullable();
            $table->date('gazette_date')->nullable();
            $table->date('participation_start_date')->nullable();
            $table->date('participation_end_date')->nullable();
            $table->string('pdf_path')->nullable();
            $table->json('tags')->nullable(); // For categorization like 'health', 'agriculture', etc.
            $table->integer('views_count')->default(0);
            $table->integer('submissions_count')->default(0);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
