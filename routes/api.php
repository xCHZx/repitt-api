<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BusinessController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('user')->group(function () {

});




//Public URLs
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

});

//Private URLs
Route::middleware(['auth:sanctum'])->group(function () {
    //Auth
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
    });

});

Route::prefix('business')->group(function () {
    Route::get('/', [BusinessController::class, 'getAll'])->name('business.getAll');
    Route::get('/{hashedId}', [BusinessController::class, 'getById'])->name('business.getById');
    Route::get('/user/{id}', [BusinessController::class, 'getAllByUser'])->name('business.getAllByUser');
    Route::post('/', [BusinessController::class, 'store'])->name('business.store');
    Route::put('/{hashedId}', [BusinessController::class, 'update'])->name('business.update');
    Route::delete('/{hashedId}', [BusinessController::class, 'delete'])->name('business.delete');
});
