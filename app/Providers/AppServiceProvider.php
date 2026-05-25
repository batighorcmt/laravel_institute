<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Route;

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
        // Dynamically add all school domains to Sanctum's stateful domains list
        try {
            $domains = \Illuminate\Support\Facades\Cache::rememberForever('school_domains', function () {
                if (\Illuminate\Support\Facades\Schema::hasTable('schools')) {
                    return \App\Models\School::whereNotNull('domain')->where('domain', '!=', '')->pluck('domain')->toArray();
                }
                return [];
            });

            if (!empty($domains)) {
                $stateful = config('sanctum.stateful', []);
                foreach ($domains as $domain) {
                    $stateful[] = $domain;
                    $stateful[] = $domain . ':8000'; // For local testing
                }
                config(['sanctum.stateful' => array_unique($stateful)]);
            }
        } catch (\Exception $e) {
            // Ignore if DB is not available yet
        }

        // Use Bootstrap pagination views to match AdminLTE 3
        \Illuminate\Pagination\Paginator::useBootstrap();

        // Register route middleware aliases for admission applicant flows
        $router = $this->app['router'];
        $router->aliasMiddleware('admission.applicant.guard', \App\Http\Middleware\AdmissionApplicantGuard::class);
        $router->aliasMiddleware('admission.applicant.exclusive', \App\Http\Middleware\AdmissionApplicantExclusive::class);

        // Scoped binding: ensure the `student` route parameter belongs to the `school` parameter
        Route::bind('student', function ($value, $route) {
            $schoolParam = $route->parameter('school');
            $schoolId = $schoolParam instanceof \App\Models\School ? $schoolParam->id : $schoolParam;

            return \App\Models\Student::where('id', $value)
                ->where('school_id', $schoolId)
                ->firstOrFail();
        });
    }
}
