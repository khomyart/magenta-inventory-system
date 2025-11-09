<?php

use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;

Route::get('/services', [ServiceController::class, 'read'])
    ->middleware('api.authorization:read,services');

Route::post('/services', [ServiceController::class, 'create'])
    ->middleware('api.authorization:create,services');

Route::patch('/services/{id}', [ServiceController::class, 'update'])
    ->middleware('api.authorization:update,services')->whereNumber('id');

Route::delete('/services/{id}', [ServiceController::class, 'delete'])
    ->middleware('api.authorization:delete,services')->whereNumber('id');
