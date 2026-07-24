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
                    // Remove 'www.' if it exists to get the base domain
                    $baseDomain = preg_replace('/^www\./', '', $domain);
                    
                    // Add base domain
                    $stateful[] = $baseDomain;
                    $stateful[] = $baseDomain . ':8000';
                    
                    // Add www. prefixed domain automatically
                    $stateful[] = 'www.' . $baseDomain;
                    $stateful[] = 'www.' . $baseDomain . ':8000';
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

        // Scoped binding: when a route also has a `{school}` parameter (e.g.
        // super-admin `schools/{school}/students/{student}` routes), require
        // the student to belong to that school. No route currently in the
        // app actually pairs {school} with {student}, so this constraint was
        // silently always failing — school_id was always null, matching no
        // student — and every `{student}` route (web and API alike) 404'd
        // unconditionally. Fall back to a plain lookup when there's no
        // sibling {school} param; per-route authorization already scopes
        // access separately (e.g. StudentDirectoryController::resolveSchoolId).
        Route::bind('student', function ($value, $route) {
            $schoolParam = $route->parameter('school');

            if ($schoolParam !== null) {
                $schoolId = $schoolParam instanceof \App\Models\School ? $schoolParam->id : $schoolParam;

                return \App\Models\Student::where('id', $value)
                    ->where('school_id', $schoolId)
                    ->firstOrFail();
            }

            return \App\Models\Student::findOrFail($value);
        });
    }
}
