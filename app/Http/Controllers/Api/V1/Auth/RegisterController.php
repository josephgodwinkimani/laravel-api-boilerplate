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
use App\Transformers\UserTransformer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller implements HasMiddleware
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
     * Register New User
     *
     * This endpoint allows you to register new user for a single tenant.
     *
     * <aside class='notice'>Supports json, xml, yaml 😎</aside>
     *
     * @bodyParam name string
     * @bodyParam email string
     * @bodyParam phone string
     * @bodyParam password string
     * @bodyParam password_confirmation string
     * @bodyParam enabled boolean
     * @response status=200 scenario='success'
     * @response status=403 scenario='forbidden' {"data": {"type": "user","error": "Forbidden","clientIp": "127.0.0.1","datetime": "2025-01-22 20:39:28"}}
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|phone:KE',
            'password' => 'required|string|min:8|confirmed',
            'enabled' => 'required|boolean',
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

        $user = $userModel->create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password)
        ]);

        Log::info(json_encode(new UserResource($user)));

        return new Response($this->dataTransformer->item('user', $user, new UserTransformer()), 200);

    }
}
