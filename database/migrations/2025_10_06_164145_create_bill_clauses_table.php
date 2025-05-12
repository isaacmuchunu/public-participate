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
        Schema::create('bill_clauses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->constrained()->cascadeOnDelete();
            $table->string('clause_number'); // e.g., "2.1", "5.3.a"
            $table->string('clause_type'); // section, subsection, paragraph, subparagraph
            $table->foreignId('parent_clause_id')->nullable()->constrained('bill_clauses')->nullOnDelete();
            $table->text('title')->nullable(); // e.g., "Definitions"
            $table->longText('content'); // The actual clause text
            $table->json('metadata')->nullable(); // page_number, etc.
            $table->integer('display_order')->default(0);
            $table->timestamps();

            $table->index(['bill_id', 'clause_number']);
            $table->index('display_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_clauses');
    }
};
