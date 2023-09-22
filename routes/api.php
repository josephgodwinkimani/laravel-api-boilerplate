<?php

/*
 * This file is part of the laravel-api-boilerplate project.
 *
 * (c) Joseph Godwin Kimani <josephgodwinkimani@gmx.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use App\Http\Controllers\Api\V1\Auth\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware("auth:sanctum")->get("/user", function (Request $request) {
    return $request->user();
});

Route::get("/users", [UserController::class, "index"])->name("index");

Route::get("/users/{id}", [UserController::class, "show"])->name("show");

Route::get("/users/export/csv", [UserController::class, "csv"])->name("csv");

Route::get("/users/export/excel", [UserController::class, "excel"])->name("excel");

Route::get("/users/export/pdf", [UserController::class, "pdf"])->name("pdf");