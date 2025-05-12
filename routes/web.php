<?php

use App\Http\Controllers\Admin\SystemAlertController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\CitizenEngagementController;
use App\Http\Controllers\Clerk\CitizenController as ClerkCitizenController;
use App\Http\Controllers\Clerk\LegislatorController as ClerkLegislatorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Legislator\BillController as LegislatorBillController;
use App\Http\Controllers\Legislator\HighlightController as LegislatorHighlightController;
use App\Http\Controllers\Legislator\ReportController as LegislatorReportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\SubmissionDraftController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('clerk')
        ->name('clerk.')
        ->middleware('role:clerk,admin')
        ->group(function () {
            Route::prefix('legislators')
                ->name('legislators.')
                ->group(function () {
                    Route::get('/', [ClerkLegislatorController::class, 'index'])->name('index');
                    Route::post('/', [ClerkLegislatorController::class, 'store'])->name('store');
                    Route::patch('{legislator}', [ClerkLegislatorController::class, 'update'])->name('update');
                    Route::delete('{legislator}', [ClerkLegislatorController::class, 'destroy'])->name('destroy');
                    Route::patch('{legislator}/restore', [ClerkLegislatorController::class, 'restore'])->name('restore');
                    Route::post('{legislator}/resend-invitation', [ClerkLegislatorController::class, 'resendInvitation'])->name('resend');
                });

            Route::prefix('citizens')
                ->name('citizens.')
                ->group(function () {
                    Route::get('/', [ClerkCitizenController::class, 'index'])->name('index');
                    Route::patch('{citizen}', [ClerkCitizenController::class, 'update'])->name('update');
                });
        });

    Route::prefix('legislator')
        ->name('legislator.')
        ->middleware('role:mp,senator,admin')
        ->group(function () {
            Route::prefix('bills')
                ->name('bills.')
                ->group(function () {
                    Route::get('/', [LegislatorBillController::class, 'index'])->name('index');
                    Route::get('{bill}', [LegislatorBillController::class, 'show'])->name('show');
                    Route::post('{bill}/highlights', [LegislatorHighlightController::class, 'store'])->name('highlights.store');
                    Route::get('{bill}/report', LegislatorReportController::class)->name('report');
                });

            Route::delete('highlights/{highlight}', [LegislatorHighlightController::class, 'destroy'])
                ->name('highlights.destroy');
        });

    Route::prefix('notifications')
        ->name('notifications.')
        ->group(function () {
            Route::get('/', [NotificationController::class, 'index'])->name('index');
            Route::post('read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
            Route::post('{notification}/read', [NotificationController::class, 'markAsRead'])->name('read');
        });
});

Route::prefix('bills')
    ->name('bills.')
    ->group(function () {
        Route::get('/', [BillController::class, 'index'])->name('index');
        Route::get('participate', [BillController::class, 'participate'])->name('participate');

        Route::middleware(['auth', 'verified'])->group(function () {
            Route::middleware('role:clerk,admin')->group(function () {
                Route::get('create', [BillController::class, 'create'])->name('create');
                Route::post('/', [BillController::class, 'store'])->name('store');
                Route::get('{bill}/edit', [BillController::class, 'edit'])->name('edit');
                Route::put('{bill}', [BillController::class, 'update'])->name('update');
                Route::delete('{bill}', [BillController::class, 'destroy'])->name('destroy');
                Route::post('{bill}/summary', [BillController::class, 'generateSummary'])->name('summary');
            });
        });

        Route::get('{bill}', [BillController::class, 'show'])->name('show');
    });

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'verified', 'role:admin'])
    ->group(function () {
        Route::get('/', function () {
            return to_route('dashboard');
        })->name('dashboard');

        Route::post('system-alerts', [SystemAlertController::class, 'store'])
            ->name('system-alerts.store');
        Route::delete('system-alerts/{systemAlert}', [SystemAlertController::class, 'destroy'])
            ->name('system-alerts.destroy');
    });

Route::prefix('submissions')
    ->name('submissions.')
    ->group(function () {
        Route::get('track', [SubmissionController::class, 'trackForm'])->name('track.form');
        Route::post('track', [SubmissionController::class, 'track'])->name('track');

        Route::middleware(['auth', 'verified'])->group(function () {
            Route::middleware('role:citizen')->group(function () {
                Route::get('create', [SubmissionController::class, 'create'])->name('create');
                Route::post('/', [SubmissionController::class, 'store'])->name('store');
                Route::post('drafts', [SubmissionDraftController::class, 'store'])->name('drafts.store');
                Route::patch('drafts/{submissionDraft}', [SubmissionDraftController::class, 'update'])->name('drafts.update');
                Route::delete('drafts/{submissionDraft}', [SubmissionDraftController::class, 'destroy'])->name('drafts.destroy');
            });

            Route::middleware('role:citizen,legislator,clerk,admin')->group(function () {
                Route::get('/', [SubmissionController::class, 'index'])->name('index');
                Route::get('{submission}', [SubmissionController::class, 'show'])->name('show');
            });

            Route::middleware('role:clerk,admin')->group(function () {
                Route::patch('{submission}', [SubmissionController::class, 'update'])->name('update');
            });

            Route::middleware('role:legislator,admin')->group(function () {
                Route::post('{submission}/engagements', [CitizenEngagementController::class, 'store'])
                    ->name('engagements.store');
            });
        });
    });

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
