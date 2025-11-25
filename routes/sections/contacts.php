<?php

use App\Http\Controllers\ContactsController;

Route::post('/contacts', [ContactsController::class, 'create'])
    ->middleware('api.authorization:create,contacts');

Route::get('/contacts', [ContactsController::class, 'read'])
    ->middleware('api.authorization:read,contacts');

Route::get('/contacts/simple', [ContactsController::class, 'simpleRead'])
    ->middleware('api.authorization:read,contacts');

Route::patch('/contacts/{id}', [ContactsController::class, 'update'])
    ->middleware('api.authorization:update,contacts')->whereNumber('id');

Route::delete('/contacts/{id}', [ContactsController::class, 'delete'])
    ->middleware('api.authorization:delete,contacts')->whereNumber('id');
