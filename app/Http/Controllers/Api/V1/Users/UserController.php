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

namespace App\Http\Controllers\Api\V1\Users;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\DataTransformService;
use App\Services\TenantService;
use App\Transformers\UserTransformer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use League\Fractal\Pagination\Cursor;
use Rap2hpoutre\FastExcel\FastExcel;

/**
 * @group Users
 *
 * APIs for managing the User accounts
 */
class UserController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('text', except: ['excel']),
            new Middleware('yaml', except: ['excel']),
            new Middleware('yaml', except: ['excel']),
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
     * This endpoint allows you to list all users of a tenant.
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

        $userModel = new User();

        $userModel->setConnection($tenant->connection);

        $currentCursor = $request->query('cursor', null);

        $previousCursor = $request->query('previous', null);

        $limit = (int) $request->query('limit', '10');

        $users = $userModel->when($currentCursor, function ($query) use ($currentCursor) {
            return $query->where('id', '>', $currentCursor);
        })->take($limit)->get();

        $cursor = null;

        if (!$users->isEmpty()) {
            $newCursor = $users->last()->id;

            $cursor = new Cursor($currentCursor, $previousCursor, $newCursor, $users->count());
        }

        $paginator = $userModel->paginate();

        $userCollection = $paginator->getCollection();

        Log::info(json_encode(UserResource::collection($userCollection)));

        $response = new Response($this->dataTransformer->collection('users', $userCollection, new UserTransformer(), 'json', $paginator, $cursor), 200);

        return $response;

    }

    /**
     * Fetch details of one record
     *
     * This endpoint allows you to fetch a single user of a tenant.
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

        $userModel = new User();

        $userModel->setConnection($tenant->connection);

        $user = $userModel->find(last($request->segments()));

        // Check if the user is null
        if (is_null($user)) {
            $array = [
                'data' => [
                    'type' => 'user',
                    'error' => 'User not found',
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

        Log::info(json_encode(new UserResource($user->first())));

        return new Response($this->dataTransformer->item('user', $user->first(), new UserTransformer()), 200);

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

    /**
     * Update a single record
     *
     * This endpoint allows you to update a single record for a single tenant.
     * It's a really useful endpoint, and you should play around
     * with it for a bit.
     * <aside class='notice'>Supports xml 😎</aside>
     *
     * @urlParam id int
     * @bodyParam name string
     * @bodyParam email string
     * @bodyParam phone string
     * @bodyParam password string
     * @bodyParam password_confirmation string
     * @bodyParam enabled boolean
     * @response status=200 scenario='success'
     * @response status=403 scenario='forbidden' {"data": {"type": "user","error": "Forbidden","clientIp": "127.0.0.1","datetime": "2025-01-22 20:39:28"}}
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'email' => 'string|email|max:255|unique:users',
            'phone' => 'phone:KE',
            'password' => 'string|min:8|confirmed',
            'enabled' => 'boolean',
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

        $userModel = $userModel->find(last($request->segments()));

        // Update the user's attributes
        $updateModel = (bool) $userModel->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'active' => $request->active
        ]);

        // Check if updating model fails
        if (!$updateModel) {
            $array = [
                'data' => [
                    'type' => 'user',
                    'error' => 'The provided parameter values are incorrect.',
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

        Log::info((string) $updateModel);

        $user = $userModel->find(last($request->segments()));

        Log::info(json_encode(new UserResource($user)));

        return new Response($this->dataTransformer->item('user', $user->first(), new UserTransformer()), 200);

    }

    /**
     * Download a CSV dump of records
     *
     * This endpoint allows you to download a csv dump of user records for a single tenant.
     * It's a really useful endpoint, and you should play around
     * with it for a bit.
     * <aside class='notice'>Supports csv 😎</aside>
     *
     * @response status=200 scenario='success'
     * @response status=403 scenario='forbidden' {"data": {"type": "user","error": "Forbidden","clientIp": "127.0.0.1","datetime": "2025-01-22 20:39:28"}}
     */
    public function csv(Request $request)
    {

        $tenant = $this->tenantService->getTenant($request);

        $userModel = new User();

        $userModel->setConnection($tenant->connection);

        $user = $userModel->get();

        Log::info(json_encode(new UserResource($user)));

        $csvExporter = new \Laracsv\Export();

        $csvExporter->build($user, ['id', 'name', 'email', 'phone', 'created_at', 'updated_at']);

        return $csvExporter->download();

    }

    /**
     * Download a Excel dump of records
     *
     * This endpoint allows you to download a excel dump of user records for a single tenant.
     * It's a really useful endpoint, and you should play around
     * with it for a bit.
     * <aside class='notice'>Supports xlsx 😎</aside>
     *
     * @response status=200 scenario='success'
     * @response status=403 scenario='forbidden' {"data": {"type": "user","error": "Forbidden","clientIp": "127.0.0.1","datetime": "2025-01-22 20:39:28"}}
     */
    public function excel(Request $request)
    {

        $tenant = $this->tenantService->getTenant($request);

        $userModel = new User();

        $userModel->setConnection($tenant->connection);

        $user = $userModel->get();

        Log::info(json_encode(new UserResource($user)));

        $fileName = 'users_' . now(env('APP_TIMEZONE'))->format('Y-m-d_H-i-s') . '.xlsx';
        $directoryPath = storage_path('app/public/');
        $filePath = $directoryPath . $fileName;

        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0755, true);
            Log::info('Created directory: {$directoryPath}');
        }

        if (!file_exists($filePath)) {
            touch($filePath);
            Log::info('Created empty file at: {$filePath}');
        }

        (new FastExcel($user))->export($filePath, function ($user) {
            return [
                'Id' => $user->id,
                'Email' => $user->email,
                'Full Name' => $user->name,
                'Phone' => $user->phone,
                'Created At' => $user->created_at,
                'Updated At' => $user->updated_at
            ];
        });

        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];

        return response()->download($filePath, $fileName, $headers)->deleteFileAfterSend(true);

    }

    /**
     * Download a PDF dump of records
     *
     * This endpoint allows you to download a pdf dump of user records for a single tenant.
     * It's a really useful endpoint, and you should play around
     * with it for a bit.
     * <aside class='notice'>Supports pdf 😎</aside>
     *
     * @response status=200 scenario='success'
     * @response status=403 scenario='forbidden' {"data": {"type": "user","error": "Forbidden","clientIp": "127.0.0.1","datetime": "2025-01-22 20:39:28"}}
     */
    public function pdf(Request $request)
    {

        $tenant = $this->tenantService->getTenant($request);

        $userModel = new User();

        $userModel->setConnection($tenant->connection);

        $users = $userModel->get();

        Log::info(json_encode(new UserCollection($users)));

        $fileName = 'users_' . now(env('APP_TIMEZONE'))->toDateTimeString() . '.pdf';

        $pdf = Pdf::loadView('pdf.users', compact('users'))
            ->setPaper('a4', 'portrait');

        return $pdf->download($fileName);

    }
}
