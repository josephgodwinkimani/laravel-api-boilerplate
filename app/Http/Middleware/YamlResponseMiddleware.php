<?php

/*
 * This file is part of the laravel-api-boilerplate project.
 *
 * (c) Joseph Godwin Kimani <josephgodwinkimani@gmx.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class YamlResponseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->header("Access-Control-Allow-Origin", "*");
        $response->header(
            "Access-Control-Allow-Headers",
            "Origin, Content-Type, Content-Range, Content-Disposition, Content-Description, X-Auth-Token"
        );
        $response->header("Content-Type", "application/x-yaml");
        $response->header("Access-Control-Expose-Headers", "*");
        $response->header("Access-Control-Allow-Credentials", "true");

        return $response;
    }
}
