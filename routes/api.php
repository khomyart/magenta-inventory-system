<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TypeController;

//include "apiTest.php";
include "auth.php";

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::post('/types', [TypeController::class,'create'])
->middleware('api.authorization:create,types');

Route::get('/types', [TypeController::class,'read'])
->middleware('api.authorization:read,types');

Route::patch('/types/{id}', [TypeController::class,'update'])
->middleware('api.authorization:update,types')->whereNumber('id');

Route::delete('/types/{id}', [TypeController::class,'delete'])
->middleware('api.authorization:delete,types')->whereNumber('id');

