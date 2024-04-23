<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\StampCardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VisitController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;





//Auth Routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout')->middleware('can:auth.logout');
    });
});

//Company Profiles Routes
Route::prefix('company')->group(function () {

    Route::middleware(['auth:sanctum'])->group(function () {

        //Negocio
        Route::prefix('business')->group(function () {
            Route::post('/', [BusinessController::class, 'storeAsCompany'])->name('business.storeAsCompany'); //Falta validar por company profile
            Route::get('/logged-user', [BusinessController::class, 'getAllByCurrentCompany'])->name('business.getAllByCurrentCompany');
            Route::get('/{id}/logged-user', [BusinessController::class, 'getByIdByCurrentCompany'])->name('business.getByIdByCurrentCompany');
            // Route::put('/{id}/logged-user', [BusinessController::class, 'updateByCurrentCompany'])->name('business.updateByCurrentCompany');
        });

        //Tarjetas de sellos
        Route::prefix('stampcard')->group(function () {
            Route::post('/', [StampCardController::class, 'storeAsCompany'])->name('stampcard.storeAsCompany');
            Route::get('/logged-user', [StampCardController::class, 'getAllByCurrentCompany'])->name('stampcard.getAllByCurrentCompany');
        });

        //Visitas
        Route::prefix('visit')->group(function () {
            Route::post('/', [VisitController::class, 'storeAsCompany'])->name('visit.storeAsCompany');
            // Route::get('{id}/logged-user', [StampCardController::class, 'getByIdByCurrentCompany'])->name('stampcard.getByIdByCurrentCompany');
            // Route::get('/business/{id}', [VisitController::class, 'getAllByBusinessAsCompany'])->name('visit.getAllByBusinessAsCompany');
            // Route::get('/stampcard/{id}/logged-user', [VisitController::class, 'getAllByStampCardAsCompany'])->name('visit.getAllByStampCardAsCompany');
        });
    });



});

//Visitor Profiles Routes
Route::prefix('visitor')->group(function () {

    Route::middleware(['auth:sanctum'])->group(function () {
        //Negocios
        Route::prefix('business')->group(function () {
            // Route::get('/visited/logged-in', [BusinessController::class, 'getVisitedByCurrentVisitor'])->name('business.getVisitedByCurrentVisitor');
        });

        //Tarjetas de sellos
        Route::prefix('stampcard')->group(function () {
            Route::get('/logged-user', [StampCardController::class, 'getAllByCurrentVisitor'])->name('stampcard.getAllByCurrentVisitor');
            Route::get('/{id}', [StampCardController::class, 'getByIdAsVisitor'])->name('business.getByIdAsVisitor');
        });

        Route::prefix('visit')->group(function () {
        Route::get('/logged-user', [VisitController::class, 'getAllByCurrentVisitor'])->name('visit.getAllByCurrentVisitor');
        });

        Route::prefix('user')->group(function () {
            Route::get('/logged-user', [UserController::class, 'getCurrentVisitorData'])->name('user.getCurrentVisitorData');
        });

    });

});




