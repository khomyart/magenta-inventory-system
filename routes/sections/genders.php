<?php
use App\Http\Controllers\GenderController;

Route::post('/genders', [GenderController::class,'create'])
->middleware('api.authorization:create,genders');

Route::get('/genders', [GenderController::class,'read'])
->middleware('api.authorization:read,genders');

Route::get('/genders/simple', [GenderController::class,'simpleRead'])
->middleware('api.authorization:read,genders');

Route::patch('/genders/{id}', [GenderController::class,'update'])
->middleware('api.authorization:update,genders')->whereNumber('id');

Route::patch('/genders/move/{id}', [GenderController::class,'moveInRow'])
->middleware('api.authorization:update,genders')->whereNumber('id');

Route::delete('/genders/{id}', [GenderController::class,'delete'])
->middleware('api.authorization:delete,genders')->whereNumber('id');
