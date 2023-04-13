<?php
use App\Http\Controllers\SizeController;

Route::post('/sizes', [SizeController::class,'create'])
->middleware('api.authorization:create,sizes');

Route::get('/sizes', [SizeController::class,'read'])
->middleware('api.authorization:read,sizes');

Route::get('/sizes/simple', [SizeController::class,'simpleRead'])
->middleware('api.authorization:read,sizes');

Route::patch('/sizes/{id}', [SizeController::class,'update'])
->middleware('api.authorization:update,sizes')->whereNumber('id');

Route::patch('/sizes/move/{id}', [SizeController::class,'moveInRow'])
->middleware('api.authorization:update,sizes')->whereNumber('id');

Route::delete('/sizes/{id}', [SizeController::class,'delete'])
->middleware('api.authorization:delete,sizes')->whereNumber('id');

