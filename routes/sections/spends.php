<?php

use App\Http\Controllers\SpendController;

Route::post('/spends', [SpendController::class, 'create'])
    ->middleware('api.authorization:create,spends');

Route::get('/spends', [SpendController::class, 'read'])
    ->middleware('api.authorization:read,spends');

Route::patch('/spends/{id}', [SpendController::class, 'update'])
    ->middleware('api.authorization:update,spends')->whereNumber('id');

Route::delete('/spends/{id}', [SpendController::class, 'delete'])
    ->middleware('api.authorization:delete,spends')->whereNumber('id');
