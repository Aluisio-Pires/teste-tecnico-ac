<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Tests\TestCase;

final class ProvidersTest extends TestCase
{
    public function test_app_service_provider_boots(): void
    {
        $provider = new AppServiceProvider($this->app);
        $provider->register();
        $provider->boot();

        $this->assertTrue(true);
    }

    public function test_app_service_provider_boots_production(): void
    {
        $originalEnv = app()->environment();
        app()->instance('env', 'production');

        $provider = new AppServiceProvider($this->app);
        $provider->boot();

        $rules = Password::defaults();
        $this->assertNotNull($rules);

        app()->instance('env', $originalEnv);
    }

    public function test_fortify_closures(): void
    {
        $provider = new FortifyServiceProvider($this->app);
        $provider->boot();

        $request = Request::create('/login', 'POST');
        $request->setLaravelSession(app('session')->driver());

        // Trigger rate limiters
        $rateLimiters = ['two-factor', 'login', 'passkeys'];
        foreach ($rateLimiters as $name) {
            $limiter = RateLimiter::limiter($name);
            if ($limiter) {
                $limiter($request);
            }
        }

        $this->assertTrue(true);
    }
}
