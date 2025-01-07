<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CrudController;

Route::post('/', [CrudController::class, 'verify']);

Route::get('/', function () {
    return 'Hello World';
});
