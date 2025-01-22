<?php

declare(strict_types=1);

/*
 * This file is part of the laravel-api-boilerplate project.
 *
 * (c) Joseph Godwin Kimani <josephgodwinkimani@gmx.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
            DatabaseCheck::new()->connectionName('hq')->name('hq'),
            DatabaseCheck::new()->connectionName('branch1')->name('branch1'),
            DatabaseConnectionCountCheck::new()
                ->warnWhenMoreConnectionsThan(1000)
                ->failWhenMoreConnectionsThan(9999),
            ScheduleCheck::new(),
            QueueCheck::new(),
            OptimizedAppCheck::new(),
            CacheCheck::new(),
            EnvironmentCheck::new(),
            PingCheck::new()->url(env('PING_CLIENT', 'https://x.com'))->retryTimes(3)->name('Dashboard'),
        ]);
    }
}
