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

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Class TenantService.
 */
class TenantService
{
    /**
     * Summary of getTenant
     * 
     * @param \Illuminate\Http\Request $request
     * @return Tenant|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model
     */
    public function getTenant(Request $request)
    {
        $subdomain = $request->query('subdomain');
        $tenant = Tenant::where('subdomain', $subdomain)->firstOrFail();
        Log::error($tenant);
        return $tenant;
    }
}