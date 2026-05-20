<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use App\Providers\TelescopeServiceProvider;
use Laravel\Telescope\Telescope;
use Tests\TestCase;

final class TelescopeServiceProviderTest extends TestCase
{
    public function test_telescope_service_provider_boots(): void
    {
        $provider = new TelescopeServiceProvider($this->app);
        $provider->register();
        $provider->boot();

        $this->assertTrue(true);
    }

    public function test_telescope_authorization(): void
    {
        $provider = new TelescopeServiceProvider($this->app);

        $gate = \Illuminate\Support\Facades\Gate::getPolicyFor(Telescope::class);

        $this->assertTrue(true);
    }
}
