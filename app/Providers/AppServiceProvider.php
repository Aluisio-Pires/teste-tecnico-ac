<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

final class AppServiceProvider extends ServiceProvider
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
        $this->configureDefaults();
        $this->registerEventListeners();
        $this->configureLogging();
        $this->configureTelescope();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        Model::shouldBeStrict(!app()->isProduction());

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Configure Laravel Telescope authorization.
     */
    private function configureTelescope(): void
    {
        Gate::define('viewTelescope', fn (User $user) => true);
    }

    /**
     * Register application event listeners.
     */
    private function registerEventListeners(): void
    {
        // Event discovery will handle this
    }

    /**
     * Configure structured logging with global context.
     */
    private function configureLogging(): void
    {
        Log::shareContext([
            'environment' => app()->environment(),
            'user_id' => auth()->id(),
        ]);
    }
}
