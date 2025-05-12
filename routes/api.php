<?php

use App\Http\Controllers\Api\BillController;
use App\Http\Controllers\Api\ClauseController;
use App\Http\Controllers\Api\Clerk\CitizenController as ApiClerkCitizenController;
use App\Http\Controllers\Api\Clerk\LegislatorController as ApiClerkLegislatorController;
use App\Http\Controllers\Api\GeoDivisionController;
use App\Http\Controllers\Api\Legislator\BillController as ApiLegislatorBillController;
use App\Http\Controllers\Api\Legislator\HighlightController as ApiLegislatorHighlightController;
use App\Http\Controllers\Api\SubmissionController;
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
