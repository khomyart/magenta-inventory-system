<?php
use App\Http\Controllers\ItemController;

Route::post('/items', [ItemController::class,'create'])
->middleware('api.authorization:create,items');

Route::get('/items', [ItemController::class,'read'])
->middleware('api.authorization:read,items');

Route::post('/items/{id}', [ItemController::class,'update'])
->middleware('api.authorization:update,items')->whereNumber('id');

Route::get('/items/prepared', [ItemController::class,'getItemsWithPreparedData'])
->middleware('api.authorization:read,items');

Route::delete('/items/{id}', [ItemController::class,'delete'])
->middleware('api.authorization:delete,items')->whereNumber('id');

Route::post('/items/income', [ItemController::class,'setIncome'])
->middleware('api.authorization:income,items');

Route::post('/items/outcome', [ItemController::class,'setOutcome'])
->middleware('api.authorization:income,items');
