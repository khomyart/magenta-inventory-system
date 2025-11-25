<?php

use App\Http\Controllers\UserController;

Route::get('/users', [UserController::class, 'read'])
    ->middleware('api.authorization:read,users');

Route::get('/users/simple', [UserController::class, 'simpleRead'])
    ->middleware('api.authorization:read,users');
