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
use App\Http\Resources\UserCollection;
use App\Models\User;
use App\Services\DataTransformService;
use App\Services\TenantService;
use App\Transformers\UserTransformer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use League\Fractal\Pagination\Cursor;
use Rap2hpoutre\FastExcel\FastExcel;

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
     * List all Users.
     *
     * This endpoint allows you to list all users of a tenant.
     * It's a really useful endpoint, and you should play around
     * with it for a bit.
     * <aside class='notice'>Supports json, xml, yaml, text 😎</aside>
     *
     * @queryParam cursor string Indicates where to start fetching results.
     * @queryParam previous string Indicates position of result to start fetching from.
     * @queryParam limit number Indicates how many records you prefer to fetch.
     * @response status=200 scenario='success'
     */
    public function index(Request $request)
    {
        $tenant = $this->tenantService->getTenant($request);

        $someModel = new User();

        $someModel->setConnection($tenant->connection);

        $currentCursor = $request->query('cursor', null);

        $previousCursor = $request->query('previous', null);

        $limit = (int) $request->query('limit', '10');

        $users = $someModel->when($currentCursor, function ($query) use ($currentCursor) {
            return $query->where('id', '>', $currentCursor);
        })->take($limit)->get();

        $cursor = null;

        if (!$users->isEmpty()) {
            $newCursor = $users->last()->id;

            $cursor = new Cursor($currentCursor, $previousCursor, $newCursor, $users->count());
        }

        $paginator = $someModel->paginate();

        $something = $paginator->getCollection();

        Log::error($something);

        $response = new Response($this->dataTransformer->collection('users', $something, new UserTransformer(), 'json', $paginator, $cursor), 200);

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
     * @urlParam id number
     * @response status=200 scenario='success'
     */
    public function show(Request $request)
    {

        $tenant = $this->tenantService->getTenant($request);

        $someModel = new User();

        $someModel->setConnection($tenant->connection);

        $something = $someModel->find(last($request->segments()));

        Log::error($something);

        $response = new Response($this->dataTransformer->item('user', $something, new UserTransformer(), 'text'), 200);

        return $response;

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
     * @bodyParam enabled boolean
     * @response status=200 scenario='success'
     */
    public function store(Request $request)
    {
        Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|string',
            'phone' => 'required|phone:KE',
            'enabled' => 'required|boolean',
        ]);

        $tenant = $this->tenantService->getTenant($request);

        $someModel = new User();

        $someModel->setConnection($tenant->connection);

        $something = $someModel->create([
            'name' => 'Jack',
            'email' => 'jack@gmail.com',
            'password' => '123'
        ]);

        $something = $someModel->where('email', 'jack@gmail.com')->first();

        Log::error($something);

        $response = new Response($this->dataTransformer->item('user', $something, new UserTransformer()), 200);

        return $response;

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
     * @bodyParam email string
     * @bodyParam password string
     * @bodyParam phone string
     * @bodyParam enabled boolean
     * @response status=200 scenario='success'
     */
    public function update(Request $request)
    {
        Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|string',
            'password' => 'string|min:8|confirmed',
            'phone' => 'required|phone:DE',
            'enabled' => 'required|boolean',
        ]);

        $tenant = $this->tenantService->getTenant($request);

        $someModel = new User();

        $someModel->setConnection($tenant->connection);

        $someModel->update(['id' => last($request->segments())], [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password)
        ]);

        $something = $someModel->find(last($request->segments()));

        Log::error($something);

        $response = new Response($this->dataTransformer->item('user', $something, new UserTransformer(), 'xml'), 200);

        return $response;

    }

    /**
     * Download a CSV dump of database table
     *
     * This endpoint allows you to download a csv dump of database table for a single tenant.
     * It's a really useful endpoint, and you should play around
     * with it for a bit.
     * <aside class='notice'>Supports csv 😎</aside>
     *
     * @response status=200 scenario='success'
     */
    public function csv(Request $request)
    {

        $tenant = $this->tenantService->getTenant($request);

        $someModel = new User();

        $someModel->setConnection($tenant->connection);

        $something = $someModel->get();

        Log::error($something);

        $csvExporter = new \Laracsv\Export();

        $csvExporter->build($something, ['id', 'name', 'email', 'phone', 'created_at', 'updated_at']);

        return $csvExporter->download();

    }

    /**
     * Download a Excel dump of database table
     *
     * This endpoint allows you to download a excel dump of database table for a single tenant.
     * It's a really useful endpoint, and you should play around
     * with it for a bit.
     * <aside class='notice'>Supports xlsx 😎</aside>
     *
     * @response status=200 scenario='success'
     */
    public function excel(Request $request)
    {

        $tenant = $this->tenantService->getTenant($request);

        $someModel = new User();

        $someModel->setConnection($tenant->connection);

        $something = $someModel->get();

        Log::error($something);

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

        (new FastExcel($something))->export($filePath, function ($user) {
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
     * Download a PDF dump of database table
     *
     * This endpoint allows you to download a pdf dump of database table for a single tenant.
     * It's a really useful endpoint, and you should play around
     * with it for a bit.
     * <aside class='notice'>Supports pdf 😎</aside>
     *
     * @response status=200 scenario='success'
     */
    public function pdf(Request $request)
    {

        $tenant = $this->tenantService->getTenant($request);

        $someModel = new User();

        $someModel->setConnection($tenant->connection);

        $users = $someModel->get();

        Log::error($users);

        $fileName = 'users_' . now(env('APP_TIMEZONE'))->toDateTimeString() . '.pdf';

        $pdf = Pdf::loadView('pdf.users', compact('users'))
            ->setPaper('a4', 'portrait');

        return $pdf->download($fileName);

    }
}
