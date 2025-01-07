<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;

Route::post('/', [Controller::class, 'verify']);

Route::get('/', function () {
    return 'Hello World';
});
