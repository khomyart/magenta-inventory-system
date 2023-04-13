<?php
use App\Http\Controllers\ItemController;

Route::post('/items', [ItemController::class,'create'])
->middleware('api.authorization:create,items');

Route::get('/items', [ItemController::class,'read'])
->middleware('api.authorization:read,items');

Route::patch('/items/{id}', [ItemController::class,'update'])
->middleware('api.authorization:update,items')->whereNumber('id');

Route::delete('/items/{id}', [ItemController::class,'delete'])
->middleware('api.authorization:delete,items')->whereNumber('id');
