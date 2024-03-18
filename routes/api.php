<?php

use App\Http\Controllers\BusinessController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::prefix('business')->group(function () {
    Route::get('/all', [BusinessController::class, 'getAll'])->name('business.getAll');
});
