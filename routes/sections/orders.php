<?php

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/orders', [OrderController::class, 'read'])
    ->middleware('api.authorization:read,orders');

Route::post('/orders', [OrderController::class, 'create'])
    ->middleware('api.authorization:create,orders');

Route::patch('/orders/{id}', [OrderController::class, 'update'])
    ->middleware('api.authorization:update,orders')->whereNumber('id');

Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel'])
    ->middleware('api.authorization:update,orders')->whereNumber('id');

Route::post('/orders/{id}/confirm', [OrderController::class, 'confirm'])
    ->middleware('api.authorization:update,orders')->whereNumber('id');

Route::post('/orders/{id}/start-work', [OrderController::class, 'startWork'])
    ->middleware('api.authorization:update,orders')->whereNumber('id');

Route::post('/orders/{id}/complete', [OrderController::class, 'complete'])
    ->middleware('api.authorization:update,orders')->whereNumber('id');

Route::post('/orders/{id}/payment', [OrderController::class, 'payment'])
    ->middleware('api.authorization:update,orders')->whereNumber('id');

Route::delete('/orders/{id}', [OrderController::class, 'delete'])
    ->middleware('api.authorization:delete,orders')->whereNumber('id');
