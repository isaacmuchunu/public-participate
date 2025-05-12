<?php

namespace App\Providers;

use App\Models\Bill;
use App\Models\Submission;
use App\Observers\BillObserver;
use App\Observers\SubmissionObserver;
use App\Policies\BillPolicy;
use App\Policies\SubmissionPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Bill::class, BillPolicy::class);
        Gate::policy(Submission::class, SubmissionPolicy::class);

        Bill::observe(BillObserver::class);
        Submission::observe(SubmissionObserver::class);
    }
}
