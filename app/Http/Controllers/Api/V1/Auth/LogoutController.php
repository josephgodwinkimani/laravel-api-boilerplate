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

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\DataTransformService;
use App\Services\TenantService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class LogoutController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            'text',
            'yaml',
            'xml'
        ];
    }

    /**
     * @var
     */
    protected $tenantService;

    /**
     * @var
     */
    protected $dataTransformer;

    /**
     * @param \App\Services\TenantService $tenantService
     */
    public function __construct(TenantService $tenantService)
    {
        $this->tenantService = $tenantService;
        $this->dataTransformer = new DataTransformService();
    }

    /**
     * Log out an Existing User on Single Device
     *
     * This endpoint allows you to revoke the token that was used to authenticate the current request for a single tenant.
     *
     * <aside class='notice'>Supports json, xml, yaml 😎</aside>
     *
     * @bodyParam token string
     * @response status=200 scenario='success'
     * @response status=400 scenario='bad request' {"data": {"type": "user","error": "The provided credentials are incorrect.","clientIp": "127.0.0.1","datetime": "2025-01-22 20:39:28"}}
     * @response status=403 scenario='forbidden' {"data": {"type": "user","error": "Forbidden","clientIp": "127.0.0.1","datetime": "2025-01-22 20:39:28"}}
     */
    public function single(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string|max:255',
        ]);

        // Check validation failure
        if ($validator->fails()) {
            $array = [
                'data' => [
                    'type' => 'user',
                    'error' => $validator->errors()->all(),
                    'clientIp' => $request->getClientIp(),
                    'url' => $request->path(),
                    'datetime' => now(env('APP_TIMEZONE'))->toDateTimeString(),
                ]
            ];
            $response = json_encode($array, JSON_UNESCAPED_SLASHES);
            Log::error($response);

            return response(
                $response,
                400
            )->header('Content-Type', 'application/json');
        }

        $tenant = $this->tenantService->getTenant($request);

        $userModel = new User();

        $userModel->setConnection($tenant->connection);

        $user = $userModel->find(last($request->segments()));

        // Check if user exists
        if (!$user) {
            $array = [
                'data' => [
                    'type' => 'user',
                    'error' => 'The provided credentials are incorrect.',
                    'clientIp' => $request->getClientIp(),
                    'url' => $request->path(),
                    'datetime' => now(env('APP_TIMEZONE'))->toDateTimeString(),
                ]
            ];
            $response = json_encode($array, JSON_UNESCAPED_SLASHES);
            Log::error($response);

            return response(
                $response,
                400
            )->header('Content-Type', 'application/json');
        }

        Log::info(json_encode(new UserResource($user)));

        // Revoke a specific token
        $user->tokens()->where('id', $request->token)->delete();

        $array = [
            'data' => [
                'type' => 'user',
                'error' => 'You have been successfully logged out from current session.',
                'clientIp' => $request->getClientIp(),
                'url' => $request->path(),
                'datetime' => now(env('APP_TIMEZONE'))->toDateTimeString(),
            ]
        ];
        $response = json_encode($array, JSON_UNESCAPED_SLASHES);
        Log::info($response);

        return response(
            $response,
            200
        )->header('Content-Type', 'application/json');

    }

    /**
     * Log out an Existing User on All Devices
     *
     * This endpoint allows you to revoke all existing tokens of a user for a single tenant.
     *
     * <aside class='notice'>Supports json, xml, yaml 😎</aside>
     *
     * @response status=200 scenario='success'
     * @response status=400 scenario='bad request' {"data": {"type": "user","error": "The provided credentials are incorrect.","clientIp": "127.0.0.1","datetime": "2025-01-22 20:39:28"}}
     * @response status=403 scenario='forbidden' {"data": {"type": "user","error": "Forbidden","clientIp": "127.0.0.1","datetime": "2025-01-22 20:39:28"}}
     */
    public function all(Request $request)
    {
        $tenant = $this->tenantService->getTenant($request);

        $userModel = new User();

        $userModel->setConnection($tenant->connection);

        $user = $userModel->find(last($request->segments()));

        // Check if user exists
        if (!$user) {
            $array = [
                'data' => [
                    'type' => 'user',
                    'error' => 'The provided credentials are incorrect.',
                    'clientIp' => $request->getClientIp(),
                    'url' => $request->path(),
                    'datetime' => now(env('APP_TIMEZONE'))->toDateTimeString(),
                ]
            ];
            $response = json_encode($array, JSON_UNESCAPED_SLASHES);
            Log::error($response);

            return response(
                $response,
                400
            )->header('Content-Type', 'application/json');
        }

        Log::info(json_encode(new UserResource($user)));

        // Revoke all tokens
        $user->tokens()->delete();

        $array = [
            'data' => [
                'type' => 'user',
                'error' => 'You have been successfully logged out on all devices.',
                'clientIp' => $request->getClientIp(),
                'url' => $request->path(),
                'datetime' => now(env('APP_TIMEZONE'))->toDateTimeString(),
            ]
        ];
        $response = json_encode($array, JSON_UNESCAPED_SLASHES);
        Log::info($response);

        return response(
            $response,
            200
        )->header('Content-Type', 'application/json');

    }
}
