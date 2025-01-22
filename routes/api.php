<?php

/*
 * This file is part of the laravel-api-boilerplate project.
 *
 * (c) Joseph Godwin Kimani <josephgodwinkimani@gmx.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use App\Http\Controllers\Api\V1\Auth\PermissionController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\RoleController;
use App\Http\Controllers\Api\V1\Users\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Health\Http\Controllers\HealthCheckJsonResultsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the 'api' middleware group. Make something great!
|
*/

Route::get('health', HealthCheckJsonResultsController::class);

Route::post('/register', [RegisterController::class, 'store'])->name('store');

Route::get('/login', function (Request $request) {
    $array = [
        'data' => [
            'type' => 'user',
            'error' => 'Unauthorised',
            'clientIp' => $request->getClientIp(),
            'datetime' => now(env('APP_TIMEZONE'))->toDateTimeString(),
        ]
    ];
    $response = json_encode($array, JSON_UNESCAPED_SLASHES);

    return response(
        $response,
        401
    )->header('Content-Type', 'application/json');
})->name('login');

Route::post('/login', [LoginController::class, 'store'])->name('store');

Route::prefix('v1')->group(
    function () {

        Route::group(
            [
                'middleware' => ['auth:sanctum'],
            ],
            function () {

                // User Routes
                Route::prefix('users')->group(
                    function () {
                        Route::get('/', [UserController::class, 'index'])->name('users.index')->middleware('check.permission:user:read');
                        Route::post('/', [UserController::class, 'store'])->name('users.store')->middleware('check.permission:user:create'); // Store a new user
                        Route::get('/{id}', [UserController::class, 'show'])->name('users.show')->middleware('check.permission:user:read'); // Show a single user
                        Route::patch('/{id}', [UserController::class, 'update'])->name('users.update')->middleware('check.permission:user:update'); // Update a user
                        // Export
                        Route::get('/export/csv', [UserController::class, 'csv'])->name('users.csv')->middleware('check.permission:user:read');
                        Route::get('/export/excel', [UserController::class, 'excel'])->name('users.excel')->middleware('check.permission:user:read');
                        Route::get('/export/pdf', [UserController::class, 'pdf'])->name('users.pdf')->middleware('check.permission:user:read');
                    }
                );

                // Role Routes
                Route::prefix('roles')->group(function () {
                    Route::get('/', [RoleController::class, 'index'])->name('roles.index')->middleware('check.permission:role:read');
                    Route::post('/', [RoleController::class, 'store'])->name('roles.store')->middleware('check.permission:role:create'); // Store a new role
                    Route::get('/{id}', [RoleController::class, 'show'])->name('roles.show')->middleware('check.permission:role:read'); // Show a single role
                    Route::patch('/{id}', [RoleController::class, 'update'])->name('roles.update')->middleware('check.permission:role:update'); // Update a user
                    Route::post('/assign/{user}', [RoleController::class, 'assign'])->name('roles.assign')->middleware('check.permission:role:read'); // Assign roles to a user
                });

                // Permission Routes
                Route::prefix('permissions')->group(function () {
                    Route::get('/', [PermissionController::class, 'index'])->name('permissions.index')->middleware('check.permission:permission:read');
                    Route::post('/', [PermissionController::class, 'store'])->name('permissions.store')->middleware('check.permission:permission:create'); // Store a new permission
                    Route::get('/{id}', [PermissionController::class, 'show'])->name('permissions.show')->middleware('check.permission:permission:read'); // Show a single permission
                    Route::put('/{permission}', [PermissionController::class, 'update'])->name('permissions.update')->middleware('check.permission:permission:update'); // Update a permission
                    // Assign Permission to Role
                    Route::post('/assign/{permission}', [PermissionController::class, 'assign'])->name('permissions.assign')->middleware('check.permission:permission:create'); // Assign permission to a role
                });

            }
        );

    }
);
