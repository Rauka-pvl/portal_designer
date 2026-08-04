<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\Project;
use App\Models\Supplier_orders;
use App\Policies\ClientPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\SupplierOrderPolicy;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Gate;
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
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Supplier_orders::class, SupplierOrderPolicy::class);
        Gate::policy(Client::class, ClientPolicy::class);

        RateLimiter::for('login', function (Request $request) {
            $email = strtolower((string) $request->input('email', ''));

            return Limit::perMinute(5)->by(($email !== '' ? $email.'|' : '').$request->ip());
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('password-email', function (Request $request) {
            $email = strtolower((string) $request->input('email', ''));

            return Limit::perMinute(5)->by(($email !== '' ? $email.'|' : '').$request->ip());
        });

        RateLimiter::for('api-business', function (Request $request) {
            return Limit::perMinute(120)->by((string) ($request->user()?->id ?: $request->ip()));
        });

        if (class_exists(Scramble::class)) {
            Scramble::configure()
                ->routes(fn (Route $route) => Str::startsWith($route->uri, 'api/'))
                ->withDocumentTransformers(function (OpenApi $openApi) {
                    if (class_exists(SecurityScheme::class)) {
                        $openApi->secure(SecurityScheme::http('bearer'));
                    }
                });
        }
    }
}
