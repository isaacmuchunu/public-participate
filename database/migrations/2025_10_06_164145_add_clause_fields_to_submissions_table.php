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
        Schema::table('submissions', function (Blueprint $table) {
            $table->foreignId('clause_id')
                ->nullable()
                ->after('bill_id')
                ->constrained('bill_clauses')
                ->nullOnDelete();
            $table->enum('submission_scope', ['bill', 'clause'])
                ->default('bill')
                ->after('submission_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropForeign(['clause_id']);
            $table->dropColumn(['clause_id', 'submission_scope']);
        });
    }
};
