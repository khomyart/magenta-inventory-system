<?php
use App\Http\Controllers\WarehouseController;

Route::post('/warehouses', [WarehouseController::class,'create'])
->middleware('api.authorization:create,warehouses');

Route::get('/warehouses', [WarehouseController::class,'read'])
->middleware('api.authorization:read,warehouses');

Route::get('/city/{id}/warehouses', [WarehouseController::class,'simpleRead'])
->middleware('api.authorization:read,warehouses')->whereNumber('id');

Route::patch('/warehouses/{id}', [WarehouseController::class,'update'])
->middleware('api.authorization:update,warehouses')->whereNumber('id');

Route::delete('/warehouses/{id}', [WarehouseController::class,'delete'])
->middleware('api.authorization:delete,warehouses')->whereNumber('id');

