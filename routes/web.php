<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CrudController;

Route::post('/', [CrudController::class, 'verify']);

Route::get('/', function () {
    return 'Hello World';
});

Route::get('/companies', [CrudController::class, 'getCompanies']);
Route::post('/companies', [CrudController::class, 'createCompany']);
Route::patch('/companies/{id}', [CrudController::class, 'editCompany']);
Route::delete('/companies/{id}', [CrudController::class, 'deleteCompany']);
Route::get('/contacts', [CrudController::class, 'getContacts']);
