<?php

namespace App\Providers;

use App\Ai\Contracts\BookingAssistant;
use App\Ai\FakeBookingAssistant;
use App\Ai\LaravelAiBookingAssistant;
use App\Auth\UnscopedEloquentUserProvider;
use App\Jobs\SendAppointmentConfirmation;
use App\Models\PersonalAccessToken;
use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);

        Auth::provider('eloquent', function ($app, array $config): UnscopedEloquentUserProvider {
            return new UnscopedEloquentUserProvider($app['hash'], $config['model']);
        });

        $this->app->bind(BookingAssistant::class, function (): BookingAssistant {
            $driver = (string) config('booking.assistant_driver');
            $hasKey = filled(config('ai.providers.openai.key'));

            if ($driver === 'laravel' && $hasKey) {
                return $this->app->make(LaravelAiBookingAssistant::class);
            }

            return $this->app->make(FakeBookingAssistant::class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Cashier::useCustomerModel(Tenant::class);
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        Queue::route(SendAppointmentConfirmation::class, 'notifications');

        RateLimiter::for('api', function (Request $request): Limit {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        Gate::define('viewApiDocs', function (?User $user = null): bool {
            return app()->environment(['local', 'testing']);
        });
    }
}
