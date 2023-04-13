<?php
use App\Http\Controllers\UnitController;

Route::post('/units', [UnitController::class,'create'])
->middleware('api.authorization:create,units');

Route::get('/units', [UnitController::class,'read'])
->middleware('api.authorization:read,units');

Route::get('/units/simple', [UnitController::class,'simpleRead'])
->middleware('api.authorization:read,units');

Route::patch('/units/{id}', [UnitController::class,'update'])
->middleware('api.authorization:update,units')->whereNumber('id');

Route::delete('/units/{id}', [UnitController::class,'delete'])
->middleware('api.authorization:delete,units')->whereNumber('id');
