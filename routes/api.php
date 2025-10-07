<?php

use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\BillController;
use App\Http\Controllers\Api\ClauseController;
use App\Http\Controllers\Api\Clerk\CitizenController as ApiClerkCitizenController;
use App\Http\Controllers\Api\Clerk\LegislatorController as ApiClerkLegislatorController;
use App\Http\Controllers\Api\EngagementController;
use App\Http\Controllers\Api\GeoDivisionController;
use App\Http\Controllers\Api\Legislator\BillController as ApiLegislatorBillController;
use App\Http\Controllers\Api\Legislator\HighlightController as ApiLegislatorHighlightController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SubmissionController;
use App\Http\Controllers\Api\SubmissionDraftController;
use Illuminate\Support\Facades\Route;

Route::get('geo/counties', [GeoDivisionController::class, 'counties'])->name('api.geo.counties');
Route::get('geo/counties/{county}/constituencies', [GeoDivisionController::class, 'constituencies'])->name('api.geo.constituencies');
Route::get('geo/constituencies/{constituency}/wards', [GeoDivisionController::class, 'wards'])->name('api.geo.wards');

Route::middleware(['auth', 'throttle:api'])
    ->prefix('v1')
    ->name('api.v1.')
    ->group(function () {
        Route::apiResource('bills', BillController::class);
        Route::apiResource('submissions', SubmissionController::class);

        // Submission Drafts
        Route::prefix('submissions')->name('submissions.')->group(function () {
            Route::post('drafts', [SubmissionDraftController::class, 'store'])->name('drafts.store');
            Route::patch('drafts/{draft}', [SubmissionDraftController::class, 'update'])->name('drafts.update');
            Route::delete('drafts/{draft}', [SubmissionDraftController::class, 'destroy'])->name('drafts.destroy');
            Route::post('drafts/{draft}/submit', [SubmissionDraftController::class, 'submit'])->name('drafts.submit');
        });

        // Citizen Engagements
        Route::prefix('engagements')->name('engagements.')->group(function () {
            Route::get('/', [EngagementController::class, 'index'])->name('index');
            Route::post('/', [EngagementController::class, 'store'])->name('store');
            Route::patch('{engagement}/read', [EngagementController::class, 'markAsRead'])->name('markAsRead');
        });

        // Analytics
        Route::prefix('analytics')->name('analytics.')->group(function () {
            Route::get('bills/{bill}', [AnalyticsController::class, 'billAnalytics'])->name('bills.show');
            Route::get('bills/{bill}/clauses/{clause}', [AnalyticsController::class, 'clauseAnalytics'])->name('clauses.show');
        });

        // Notifications
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [NotificationController::class, 'index'])->name('index');
            Route::patch('{notification}/read', [NotificationController::class, 'markAsRead'])->name('markAsRead');
            Route::patch('mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('markAllAsRead');
        });

        // Bill clauses
        Route::prefix('bills/{bill}/clauses')
            ->name('bills.clauses.')
            ->group(function () {
                Route::get('/', [ClauseController::class, 'index'])->name('index');
                Route::get('{clause}', [ClauseController::class, 'show'])->name('show');

                Route::middleware('role:clerk,admin')->group(function () {
                    Route::post('parse', [ClauseController::class, 'parse'])->name('parse');
                    Route::post('/', [ClauseController::class, 'store'])->name('store');
                    Route::patch('{clause}', [ClauseController::class, 'update'])->name('update');
                    Route::delete('{clause}', [ClauseController::class, 'destroy'])->name('destroy');
                });
            });

        Route::prefix('clerk')
            ->name('clerk.')
            ->middleware('role:clerk,admin')
            ->group(function () {
                Route::get('legislators', [ApiClerkLegislatorController::class, 'index'])->name('legislators.index');
                Route::post('legislators', [ApiClerkLegislatorController::class, 'store'])->name('legislators.store');
                Route::patch('legislators/{legislator}', [ApiClerkLegislatorController::class, 'update'])->name('legislators.update');
                Route::delete('legislators/{legislator}', [ApiClerkLegislatorController::class, 'destroy'])->name('legislators.destroy');
                Route::patch('legislators/{legislator}/restore', [ApiClerkLegislatorController::class, 'restore'])->name('legislators.restore');
                Route::post('legislators/{legislator}/resend-invitation', [ApiClerkLegislatorController::class, 'resend'])->name('legislators.resend');

                Route::get('citizens', [ApiClerkCitizenController::class, 'index'])->name('citizens.index');
                Route::patch('citizens/{citizen}', [ApiClerkCitizenController::class, 'update'])->name('citizens.update');
            });

        Route::prefix('legislator')
            ->name('legislator.')
            ->middleware('role:mp,senator,admin')
            ->group(function () {
                Route::get('bills', [ApiLegislatorBillController::class, 'index'])->name('bills.index');
                Route::get('bills/{bill}', [ApiLegislatorBillController::class, 'show'])->name('bills.show');

                Route::get('highlights', [ApiLegislatorHighlightController::class, 'index'])->name('highlights.index');
                Route::post('bills/{bill}/highlights', [ApiLegislatorHighlightController::class, 'store'])->name('highlights.store');
                Route::delete('highlights/{highlight}', [ApiLegislatorHighlightController::class, 'destroy'])->name('highlights.destroy');
            });
    });
