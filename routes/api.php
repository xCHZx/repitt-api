<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\StampCardController;
use App\Http\Controllers\VisitController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;





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
        Route::get('/{id}', [BusinessController::class, 'getById'])->name('business.getById');
        Route::get('/user/current', [BusinessController::class, 'getAllByCurrentUser'])->name('business.getAllByCurrentUser');
        Route::put('/user/current/{id}', [BusinessController::class, 'updateByCurrentUser'])->name('business.updateByCurrentUser');
        Route::get('user/current/visited', [BusinessController::class, 'getVisitedByCurrentUser'])->name('business.getVisitedByCurrentUser');

    });

    Route::prefix('stampcard')->group(function () {
        Route::post('/', [StampCardController::class, 'store'])->name('stampcard.store'); //Falta validar por negocio
        Route::get('/user/current', [StampCardController::class, 'getAllByCurrentUser'])->name('stampcard.getAllByCurrentUser');
        Route::get('/{id}', [StampCardController::class, 'getById'])->name('stampcard.getById');
    });

    Route::prefix('visit')->group(function () {
        Route::post('/', [VisitController::class, 'store'])->name('visit.store');
        Route::get('/stampcard/{id}', [VisitController::class, 'getAllByStampCard'])->name('visit.getAllByStampCard');
        Route::get('/user/current', [VisitController::class, 'getAllByCurrentUser'])->name('visit.getAllByCurrentUser');
        Route::get('/business/{id}', [VisitController::class, 'getByBusiness'])->name('visit.getByBusiness');
    });

    // Route::prefix('reward')->group(function () {
    //     Route::post('/', [RewardController::class, 'store'])->name('reward.store');
    //     Route::get('/user/current', [RewardController::class, 'getAllByCurrentUser'])->name('reward.getAllByCurrentUser');
    //     Route::get('/stampcard/{id}', [RewardController::class, 'getByStampCard'])->name('reward.getByStampCard');
    //     Route::get('/business/{id}', [RewardController::class, 'getByBusiness'])->name('reward.getByBusiness');
    // });

});


