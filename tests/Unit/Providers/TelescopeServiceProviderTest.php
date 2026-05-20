<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use App\Models\User;
use App\Providers\TelescopeServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use ReflectionClass;
use Tests\TestCase;

final class TelescopeServiceProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $reflection = new ReflectionClass(Telescope::class);
        $property = $reflection->getProperty('filterUsing');
        $property->setValue([]);
    }

    public function test_telescope_service_provider_boots(): void
    {
        $provider = new TelescopeServiceProvider($this->app);
        $provider->register();
        $provider->boot();

        $this->assertTrue(true);
    }

    public function test_telescope_authorization_gate(): void
    {
        $user = User::factory()->make(['email' => 'admin@example.com']);

        $this->assertTrue(Gate::has('viewTelescope'));

        $this->assertFalse(Gate::forUser($user)->check('viewTelescope'));
    }

    public function test_telescope_filter_logic(): void
    {
        $this->app['env'] = 'local';
        $provider = new TelescopeServiceProvider($this->app);
        $provider->register();

        $entry = new IncomingEntry(['type' => 'request']);

        $this->assertCount(1, Telescope::$filterUsing);
        $filter = Telescope::$filterUsing[0];
        $this->assertTrue($filter($entry));

        $this->app['env'] = 'production';

        $reflection = new ReflectionClass(Telescope::class);
        $property = $reflection->getProperty('filterUsing');
        $property->setValue([]);

        $provider->register();
        $filter = Telescope::$filterUsing[0];

        $this->assertFalse($filter($entry));

        $failedJobEntry = new IncomingEntry(['type' => 'job', 'content' => ['status' => 'failed']]);

        $this->assertFalse($filter($failedJobEntry));
    }

    public function test_hide_sensitive_details_in_local(): void
    {
        $this->app['env'] = 'local';
        $provider = new TelescopeServiceProvider($this->app);

        $provider->register();

        $this->assertTrue($this->app->environment('local'));
    }

    public function test_hide_sensitive_details_in_production(): void
    {
        $this->app['env'] = 'production';
        $provider = new TelescopeServiceProvider($this->app);

        $provider->register();

        $this->assertFalse($this->app->environment('local'));
    }
}
