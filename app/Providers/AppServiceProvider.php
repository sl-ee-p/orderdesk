<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
        // Fail loudly on N+1 queries during development and tests.
        Model::preventLazyLoading(! $this->app->isProduction());

        Blade::directive('money', fn (string $cents) => "<?php echo e(\\App\\Support\\Money::format({$cents})); ?>");

        // General API budget: 60 requests per minute per user (per IP when unauthenticated).
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));

        // Slow down password guessing: 5 attempts per minute per email and IP. The email is
        // normalised so "Demo@…" and "demo@…" (the same account on MySQL) share one budget.
        RateLimiter::for('login', function (Request $request): Limit {
            $email = $request->input('email');

            return Limit::perMinute(5)->by(Str::lower(trim(is_string($email) ? $email : '')).'|'.$request->ip());
        });
    }
}
