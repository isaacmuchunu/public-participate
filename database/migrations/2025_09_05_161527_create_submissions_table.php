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
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_id', 12)->unique(); // Unique tracking ID for citizens
            $table->foreignId('bill_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('submitter_name')->nullable(); // For anonymous submissions
            $table->string('submitter_phone')->nullable();
            $table->string('submitter_email')->nullable();
            $table->string('submitter_county')->nullable();
            $table->enum('submission_type', ['support', 'oppose', 'amend', 'neutral']);
            $table->text('content');
            $table->enum('channel', ['web', 'sms', 'ussd', 'ivr'])->default('web');
            $table->enum('language', ['en', 'sw', 'other'])->default('en');
            $table->enum('status', ['pending', 'reviewed', 'included', 'rejected'])->default('pending');
            $table->json('metadata')->nullable(); // For storing additional data like sentiment analysis
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
