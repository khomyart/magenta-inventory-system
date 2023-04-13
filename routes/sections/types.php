<?php
use App\Http\Controllers\TypeController;

Route::post('/types', [TypeController::class,'create'])
->middleware('api.authorization:create,types');

Route::get('/types', [TypeController::class,'read'])
->middleware('api.authorization:read,types');

Route::get('/types/simple', [TypeController::class,'simpleRead'])
->middleware('api.authorization:read,types');

Route::patch('/types/{id}', [TypeController::class,'update'])
->middleware('api.authorization:update,types')->whereNumber('id');

Route::patch('/types/move/{id}', [TypeController::class,'moveInRow'])
->middleware('api.authorization:update,types')->whereNumber('id');

Route::delete('/types/{id}', [TypeController::class,'delete'])
->middleware('api.authorization:delete,types')->whereNumber('id');
