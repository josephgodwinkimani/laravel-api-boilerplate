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

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $permission): Response
    {
        $user = $request->user();

        if ($user && !$user->hasPermission($permission)) {
            $array = [
                'data' => [
                    'type' => 'user',
                    'error' => 'Forbidden',
                    'clientIp' => $request->getClientIp(),
                    'datetime' => now(env('APP_TIMEZONE'))->toDateTimeString(),
                ]
            ];
            $response = json_encode($array, JSON_UNESCAPED_SLASHES);

            return response(
                $response,
                403
            )->header('Content-Type', 'application/json');
        }

        return $next($request);
    }
}
