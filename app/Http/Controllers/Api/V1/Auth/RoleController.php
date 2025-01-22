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
use App\Http\Resources\RoleResource;
use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use App\Services\DataTransformService;
use App\Services\TenantService;
use App\Transformers\RoleTransformer;
use App\Transformers\UserTransformer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use League\Fractal\Pagination\Cursor;

/**
 * @group Roles
 *
 * APIs for managing the Roles for users
 */
class RoleController extends Controller implements HasMiddleware
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
     * List all records
     *
     * This endpoint allows you to list all roles of a tenant.
     * It's a really useful endpoint, and you should play around
     * with it for a bit.
     * <aside class='notice'>Supports json, xml, yaml, text 😎</aside>
     *
     * @queryParam cursor int Indicates where to start fetching results.
     * @queryParam previous int Indicates position of result to start fetching from.
     * @queryParam limit int Indicates how many records you prefer to fetch.
     * @response status=200 scenario='success'
     * @response status=403 scenario='forbidden' {"data": {"type": "user","error": "Forbidden","clientIp": "127.0.0.1","datetime": "2025-01-22 20:39:28"}}
     */
    public function index(Request $request)
    {
        $tenant = $this->tenantService->getTenant($request);

        $roleModel = new Role();

        $roleModel->setConnection($tenant->connection);

        $currentCursor = $request->query('cursor', null);

        $previousCursor = $request->query('previous', null);

        $limit = (int) $request->query('limit', '10');

        $roles = $roleModel->when($currentCursor, function ($query) use ($currentCursor) {
            return $query->where('id', '>', $currentCursor);
        })->take($limit)->get();

        $cursor = null;

        if (!$roles->isEmpty()) {
            $newCursor = $roles->last()->id;

            $cursor = new Cursor($currentCursor, $previousCursor, $newCursor, $roles->count());
        }

        $paginator = $roleModel->paginate();

        $roleCollection = $paginator->getCollection();

        Log::info(json_encode(RoleResource::collection($roleCollection)));

        $response = new Response($this->dataTransformer->collection('roles', $roleCollection, new RoleTransformer(), 'json', $paginator, $cursor), 200);

        return $response;

    }

    /**
     * Fetch details of one record
     *
     * This endpoint allows you to fetch a single role of a tenant.
     * It's a really useful endpoint, and you should play around
     * with it for a bit.
     * <aside class='notice'>Supports text 😎</aside>
     *
     * @urlParam id int
     * @response status=200 scenario='success'
     * @response status=403 scenario='forbidden' {"data": {"type": "user","error": "Forbidden","clientIp": "127.0.0.1","datetime": "2025-01-22 20:39:28"}}
     */
    public function show(Request $request)
    {

        $tenant = $this->tenantService->getTenant($request);

        $roleModel = new Role();

        $roleModel->setConnection($tenant->connection);

        $role = $roleModel->find(last($request->segments()));

        // Check if the role is null
        if (is_null($role)) {
            $array = [
                'data' => [
                    'type' => 'role',
                    'error' => 'Role not found',
                    'clientIp' => $request->getClientIp(),
                    'url' => $request->path(),
                    'datetime' => now(env('APP_TIMEZONE'))->toDateTimeString(),
                ]
            ];
            $response = json_encode($array, JSON_UNESCAPED_SLASHES);
            Log::error($response);

            return response(
                $response,
                404
            )->header('Content-Type', 'application/json');
        }

        Log::info(json_encode(new RoleResource($role->first())));

        return new Response($this->dataTransformer->item('role', $role->first(), new RoleTransformer()), 200);

    }

    /**
     * Save a single record
     *
     * This endpoint allows you to save a single record for a single tenant.
     * It's a really useful endpoint, and you should play around
     * with it for a bit.
     * <aside class='notice'>Supports json, xml, yaml 😎</aside>
     *
     * @bodyParam name string
     * @bodyParam enabled boolean
     * @response status=200 scenario='success'
     * @response status=403 scenario='forbidden' {"data": {"type": "user","error": "Forbidden","clientIp": "127.0.0.1","datetime": "2025-01-22 20:39:28"}}
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:roles'
        ]);

        // Check validation failure
        if ($validator->fails()) {
            $array = [
                'data' => [
                    'type' => 'role',
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

        $roleModel = new Role();

        $roleModel->setConnection($tenant->connection);

        $role = $roleModel->create($request->all());

        Log::info(json_encode(new RoleResource($role)));

        return new Response($this->dataTransformer->item('role', $role, new RoleTransformer()), 200);
    }

    /**
     * Update a single record
     *
     * This endpoint allows you to update a single record for a single tenant.
     * It's a really useful endpoint, and you should play around
     * with it for a bit.
     * <aside class='notice'>Supports xml 😎</aside>
     *
     * @urlParam id number
     * @bodyParam name string
     * @bodyParam enabled boolean
     * @response status=200 scenario='success'
     * @response status=403 scenario='forbidden' {"data": {"type": "user","error": "Forbidden","clientIp": "127.0.0.1","datetime": "2025-01-22 20:39:28"}}
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:roles'
        ]);

        // Check validation failure
        if ($validator->fails()) {
            $array = [
                'data' => [
                    'type' => 'role',
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

        $roleModel = new Role();

        $roleModel->setConnection($tenant->connection);

        $roleModel->update(['id' => last($request->segments())], [
            'name' => $request->name,
        ]);

        $role = $roleModel->find(last($request->segments()));

        Log::info(json_encode(new RoleResource($role)));

        return new Response($this->dataTransformer->item('role', $role->first(), new RoleTransformer()), 200);

    }

    /**
     * Assign a role to a user
     *
     * This endpoint allows you to assign a role to existing user for a single tenant.
     * It's a really useful endpoint, and you should play around
     * with it for a bit.
     * <aside class='notice'>Supports json, xml, yaml 😎</aside>
     *
     * @urlParam user string
     * @bodyParam role string
     * @response status=200 scenario='success'
     * @response status=403 scenario='forbidden' {"data": {"type": "user","error": "Forbidden","clientIp": "127.0.0.1","datetime": "2025-01-22 20:39:28"}}
     */
    public function assign(Request $request)
    {
        $tenant = $this->tenantService->getTenant($request);

        // Confirm user exists
        $userModel = new User();

        $userModel->setConnection($tenant->connection);

        $user = $userModel->find(last($request->segments()));

        Log::info(json_encode(new UserResource($user->first())));

        // Confirm role exists
        $roleModel = new Role();

        $roleModel->setConnection($tenant->connection);

        $role = $roleModel->where('name', $request->role)->first();

        $user->roles()->attach($role->id);

        // Load roles for the user after assigning
        $userWithRoles = $user->with('roles')->first();

        Log::info(json_encode(new UserResource($userWithRoles)));

        return new Response($this->dataTransformer->item('user', $userWithRoles, new UserTransformer()), 200);
    }
}
