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
