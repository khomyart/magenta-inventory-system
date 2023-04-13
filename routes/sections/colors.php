<?php
use App\Http\Controllers\ColorController;

Route::post('/colors', [ColorController::class,'create'])
->middleware('api.authorization:create,colors');

Route::get('/colors', [ColorController::class,'read'])
->middleware('api.authorization:read,colors');

Route::get('/colors/simple', [ColorController::class,'simpleRead'])
->middleware('api.authorization:read,colors');

Route::patch('/colors/{id}', [ColorController::class,'update'])
->middleware('api.authorization:update,colors')->whereNumber('id');

Route::delete('/colors/{id}', [ColorController::class,'delete'])
->middleware('api.authorization:delete,colors')->whereNumber('id');
