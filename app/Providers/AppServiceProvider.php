<?php

namespace App\Providers;

use App\Models\Bill;
use App\Models\Submission;
use App\Observers\BillObserver;
use App\Observers\SubmissionObserver;
use App\Policies\BillPolicy;
use App\Policies\SubmissionPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
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

        // Query performance monitoring
        $this->configureQueryMonitoring();

        // Prevent N+1 queries in production
        $this->configureLazyLoadingPrevention();
    }

    /**
     * Configure query performance monitoring
     */
    private function configureQueryMonitoring(): void
    {
        if (app()->environment('local')) {
            DB::listen(function ($query) {
                // Log slow queries (>1 second)
                if ($query->time > 1000) {
                    Log::warning('Slow query detected', [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $query->time . 'ms',
                        'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5),
                    ]);
                }

                // Log very slow queries (>5 seconds) even in production
                if ($query->time > 5000) {
                    Log::error('Very slow query detected', [
                        'sql' => $query->sql,
                        'time' => $query->time . 'ms',
                        'environment' => app()->environment(),
                    ]);
                }
            });

            // Count queries per request in local
            DB::listen(function () {
                if (!app()->has('query_count')) {
                    app()->instance('query_count', 0);
                }

                app()->instance('query_count', app('query_count') + 1);
            });
        }
    }

    /**
     * Configure lazy loading prevention
     */
    private function configureLazyLoadingPrevention(): void
    {
        if (app()->environment('production')) {
            Model::preventLazyLoading();
        }

        // Prevent silently discarding attributes in all environments
        Model::preventSilentlyDiscardingAttributes();

        // Prevent accessing missing attributes in all environments
        Model::preventAccessingMissingAttributes();
    }
}
