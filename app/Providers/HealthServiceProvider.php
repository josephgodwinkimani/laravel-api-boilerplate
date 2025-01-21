<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Spatie\Health\Checks\Checks\CacheCheck;
use Spatie\Health\Checks\Checks\EnvironmentCheck;
use Spatie\Health\Checks\Checks\OptimizedAppCheck;
use Spatie\Health\Checks\Checks\PingCheck;
use Spatie\Health\Checks\Checks\QueueCheck;
use Spatie\Health\Checks\Checks\ScheduleCheck;
use Spatie\Health\Facades\Health;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\DatabaseConnectionCountCheck;

//use Spatie\CpuLoadHealthCheck\CpuLoadCheck;

class HealthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Health::checks([
            //UsedDiskSpaceCheck::new(),
            //CpuLoadCheck::new()
            //    ->failWhenLoadIsHigherInTheLast5Minutes(200.0)
            //    ->failWhenLoadIsHigherInTheLast15Minutes(150.0),
            DatabaseCheck::new(),
            DatabaseConnectionCountCheck::new()
                ->warnWhenMoreConnectionsThan(1000)
                ->failWhenMoreConnectionsThan(9999),
            ScheduleCheck::new(),
            QueueCheck::new(),
            OptimizedAppCheck::new(),
            CacheCheck::new(),
            PingCheck::new()->url(env("PING_CLIENT", "https://x.com"))->retryTimes(3)->name("Dashboard"),
            PingCheck::new()->url(env("PING_CLIENT1", "https://hotjar.com/saas/client123"))->retryTimes(3)->name("Analytics"),
            PingCheck::new()->url(env("PING_CLIENT2", "https://server123.json"))->retryTimes(3)->name("AWS"),
            EnvironmentCheck::new(),

        ]);
    }
}
