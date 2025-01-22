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
use App\Http\Resources\PermissionResource;
use App\Http\Resources\RoleResource;
use App\Models\Permission;
use App\Models\Role;
use App\Services\DataTransformService;
use App\Services\TenantService;
use App\Transformers\PermissionTransformer;
use App\Transformers\RoleTransformer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use League\Fractal\Pagination\Cursor;

/**
 * @group Permissions
 *
 * APIs for managing the Permissions for users
 */
class PermissionController extends Controller implements HasMiddleware
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
     * This endpoint allows you to list all permissions of a tenant.
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

        $permissionModel = new Permission();

        $permissionModel->setConnection($tenant->connection);

        $currentCursor = $request->query('cursor', null);

        $previousCursor = $request->query('previous', null);

        $limit = (int) $request->query('limit', '10');

        $permissions = $permissionModel->when($currentCursor, function ($query) use ($currentCursor) {
            return $query->where('id', '>', $currentCursor);
        })->take($limit)->get();

        $cursor = null;

        if (!$permissions->isEmpty()) {
            $newCursor = $permissions->last()->id;

            $cursor = new Cursor($currentCursor, $previousCursor, $newCursor, $permissions->count());
        }

        $paginator = $permissionModel->paginate();

        $permissionCollection = $paginator->getCollection();

        Log::info(json_encode(RoleResource::collection($permissionCollection)));

        $response = new Response($this->dataTransformer->collection('permission', $permissionCollection, new PermissionTransformer(), 'json', $paginator, $cursor), 200);

        return $response;

    }

    /**
     * Fetch details of one record
     *
     * This endpoint allows you to fetch a single permission of a tenant.
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

        $permissionModel = new Permission();

        $permissionModel->setConnection($tenant->connection);

        $permission = $permissionModel->find(last($request->segments()));

        // Check if the permission is null
        if (is_null($permission)) {
            $array = [
                'data' => [
                    'type' => 'permission',
                    'error' => 'Permission not found',
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

        Log::info(json_encode(new PermissionResource($permission->first())));

        return new Response($this->dataTransformer->item('permission', $permission->first(), new PermissionTransformer()), 200);

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
            'name' => 'required|unique:permissions'
        ]);

        // Check validation failure
        if ($validator->fails()) {
            $array = [
                'data' => [
                    'type' => 'permission',
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

        $permissionModel = new Permission();

        $permissionModel->setConnection($tenant->connection);

        $permission = $permissionModel->create($request->all());

        Log::info(json_encode(new PermissionResource($permission)));

        return new Response($this->dataTransformer->item('permission', $permission, new PermissionTransformer()), 200);
    }

    /**
     * Assign a permission to a role
     *
     * This endpoint allows you to assign a permission to existing role for a single tenant.
     * It's a really useful endpoint, and you should play around
     * with it for a bit.
     * <aside class='notice'>Supports json, xml, yaml 😎</aside>
     *
     * @urlParam role int
     * @bodyParam permission string
     * @response status=200 scenario='success'
     * @response status=403 scenario='forbidden' {"data": {"type": "user","error": "Forbidden","clientIp": "127.0.0.1","datetime": "2025-01-22 20:39:28"}}
     */
    public function assign(Request $request)
    {
        $tenant = $this->tenantService->getTenant($request);

        // Confirm role exists
        $roleModel = new Role();

        $roleModel->setConnection($tenant->connection);

        $role = $roleModel->find(last($request->segments()));

        Log::info(json_encode(new RoleResource($role->first())));

        // Confirm permission exists
        $permissionModel = new Permission();

        $permissionModel->setConnection($tenant->connection);

        $permission = $permissionModel->where('name', $request->permission)->first();

        $permission->roles()->attach($role->id);

        // Load roles for the permission after assigning
        $roleWithPermissions = $role->with('permissions')->first();

        Log::info(json_encode(new RoleResource($roleWithPermissions)));

        return new Response($this->dataTransformer->item('role', $roleWithPermissions, new RoleTransformer()), 200);

    }
}
