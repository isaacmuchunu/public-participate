<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Performance indexes for submissions table (most queried)
        Schema::table('submissions', function (Blueprint $table) {
            $table->index(['bill_id', 'status', 'created_at'], 'idx_submissions_bill_status_date');
            $table->index(['user_id', 'bill_id'], 'idx_submissions_user_bill');
            $table->index(['submission_type', 'language'], 'idx_submissions_type_lang');
            $table->index('clause_id', 'idx_submissions_clause');
            $table->index(['created_at', 'status'], 'idx_submissions_timeline');
        });

        // Performance indexes for bills table
        Schema::table('bills', function (Blueprint $table) {
            $table->index(['status', 'participation_end_date'], 'idx_bills_status_end_date');
            $table->index('created_by', 'idx_bills_created_by');
            $table->index(['house', 'status'], 'idx_bills_house_status');

            // Full-text search index for PostgreSQL
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('CREATE INDEX idx_bills_fulltext ON bills USING gin(to_tsvector(\'english\', title || \' \' || description))');
            }
        });

        // Performance indexes for bill_clauses table
        Schema::table('bill_clauses', function (Blueprint $table) {
            $table->index(['bill_id', 'parent_clause_id', 'display_order'], 'idx_clauses_bill_parent_order');
            $table->index('parent_clause_id', 'idx_clauses_parent');
        });

        // Performance indexes for citizen_engagements table
        Schema::table('citizen_engagements', function (Blueprint $table) {
            $table->index(['recipient_id', 'sent_at'], 'idx_engagements_recipient_date');
            $table->index(['sender_id', 'bill_id'], 'idx_engagements_sender_bill');
        });

        // Performance indexes for legislator_highlights table
        Schema::table('legislator_highlights', function (Blueprint $table) {
            $table->index(['user_id', 'bill_id'], 'idx_highlights_user_bill');
        });

        // Performance indexes for users table
        Schema::table('users', function (Blueprint $table) {
            $table->index('role', 'idx_users_role');
            $table->index(['county_id', 'constituency_id'], 'idx_users_geography');
        });

        // Performance indexes for clause_analytics table
        Schema::table('clause_analytics', function (Blueprint $table) {
            $table->index(['bill_clause_id', 'updated_at'], 'idx_analytics_clause_updated');
        });

        // Performance indexes for notifications table
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'idx_notifications_unread');
        });

        // Performance indexes for system_alerts table
        Schema::table('system_alerts', function (Blueprint $table) {
            $table->index(['is_active', 'severity', 'start_date'], 'idx_alerts_active');
        });

        // Performance indexes for submission_drafts table
        Schema::table('submission_drafts', function (Blueprint $table) {
            $table->index(['user_id', 'bill_id', 'updated_at'], 'idx_drafts_user_bill');
        });

        // Geographic data indexes
        Schema::table('constituencies', function (Blueprint $table) {
            $table->index('county_id', 'idx_constituencies_county');
        });

        Schema::table('wards', function (Blueprint $table) {
            $table->index('constituency_id', 'idx_wards_constituency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop full-text search index for PostgreSQL
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS idx_bills_fulltext');
        }

        // Drop submissions indexes
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropIndex('idx_submissions_bill_status_date');
            $table->dropIndex('idx_submissions_user_bill');
            $table->dropIndex('idx_submissions_type_lang');
            $table->dropIndex('idx_submissions_clause');
            $table->dropIndex('idx_submissions_timeline');
        });

        // Drop bills indexes
        Schema::table('bills', function (Blueprint $table) {
            $table->dropIndex('idx_bills_status_end_date');
            $table->dropIndex('idx_bills_created_by');
            $table->dropIndex('idx_bills_house_status');
        });

        // Drop bill_clauses indexes
        Schema::table('bill_clauses', function (Blueprint $table) {
            $table->dropIndex('idx_clauses_bill_parent_order');
            $table->dropIndex('idx_clauses_parent');
        });

        // Drop citizen_engagements indexes
        Schema::table('citizen_engagements', function (Blueprint $table) {
            $table->dropIndex('idx_engagements_recipient_date');
            $table->dropIndex('idx_engagements_sender_bill');
        });

        // Drop legislator_highlights indexes
        Schema::table('legislator_highlights', function (Blueprint $table) {
            $table->dropIndex('idx_highlights_user_bill');
        });

        // Drop users indexes
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_role');
            $table->dropIndex('idx_users_geography');
        });

        // Drop clause_analytics indexes
        Schema::table('clause_analytics', function (Blueprint $table) {
            $table->dropIndex('idx_analytics_clause_updated');
        });

        // Drop notifications indexes
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('idx_notifications_unread');
        });

        // Drop system_alerts indexes
        Schema::table('system_alerts', function (Blueprint $table) {
            $table->dropIndex('idx_alerts_active');
        });

        // Drop submission_drafts indexes
        Schema::table('submission_drafts', function (Blueprint $table) {
            $table->dropIndex('idx_drafts_user_bill');
        });

        // Drop geographic indexes
        Schema::table('constituencies', function (Blueprint $table) {
            $table->dropIndex('idx_constituencies_county');
        });

        Schema::table('wards', function (Blueprint $table) {
            $table->dropIndex('idx_wards_constituency');
        });
    }
};
