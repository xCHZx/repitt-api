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
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout')->middleware('can:auth.logout');
    });

    //Business
    Route::prefix('business')->group(function () {
        Route::post('/', [BusinessController::class, 'store'])->name('business.store')->middleware('can:business.store');
        // Route::get('/', [BusinessController::class, 'getAll'])->name('business.getAll'); //Admin route
        Route::get('/{id}', [BusinessController::class, 'getById'])->name('business.getById');
        Route::get('/user/current', [BusinessController::class, 'getAllByCurrentUser'])->name('business.getAllByCurrentUser');
        // Route::put('/{id}', [BusinessController::class, 'update'])->name('business.update'); //Admin route
        Route::put('/user/current/{id}', [BusinessController::class, 'updateByCurrentUser'])->name('business.updateByCurrentUser');
        // Route::delete('/{id}', [BusinessController::class, 'delete'])->name('business.delete'); //Admin route (falta)
    });

});


