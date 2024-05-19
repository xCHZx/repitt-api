<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\MetricController;
use App\Http\Controllers\StampCardController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VisitController;
use App\Http\Controllers\SegmentController;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Route;



Route::post('/stripe-webhook', [StripeWebhookController::class,'handlewebhook'])->name('webhook');
Route::post('update', [UserController::class,'update'])->name('user.update')->middleware(['auth:sanctum']);


Route::post('update', [UserController::class,'update'])->name('user.update')->middleware(['auth:sanctum']);
//Auth Routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/dual-register', [AuthController::class, 'dualRegister'])->name('auth.dualRegister');
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('/sendpasswordrecoverymail',[AuthController::class,'sendPasswordRecoveryMail'])->name('sendpasswordrecoveryMail');
    Route::post('/password-recover', [AuthController::class, 'recoverPassword'])->name('password-recover');


    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::post('sendverifyemail', [AuthController::class , 'sendverifyEmail'])->name('auth.sendEmail');
        Route::post('verifyemail', [AuthController::class , 'verifyEmail'])->name('auth.verifyEmail');
        Route::post('updatepassword', [UserController::class,'updatePassword'])->name('user.updatePassword');
    });
});

Route::post('/subscription-checkout',[SubscriptionController::class,'checkout'])->name('checkout')->middleware(['auth:sanctum']);
Route::get('/billing-portal',[UserController::class,'createCustomerPortalSession'])->name('user.billing-portal')->middleware(['auth:sanctum']);
Route::get('/checksuscription',[UserController::class,'hello'])->name('hello')->middleware(['auth:sanctum']);
//Company Profiles Routes
Route::prefix('company')->group(function () {

    Route::middleware(['auth:sanctum'])->group(function () {

        //Negocio
        Route::prefix('business')->group(function () {
            Route::post('/', [BusinessController::class, 'storeAsCompany'])->name('business.storeAsCompany'); //Falta validar por company profile
            Route::post('/{id}/logged-user', [BusinessController::class, 'updateByCurrentCompany'])->name('business.updateByCurrentCompany');
            Route::post('/publish/{id}',[BusinessController::class,'publish'])->name('business.publish');
            Route::post('/unpublish/{id}',[BusinessController::class,'unpublish'])->name('business.unpublish');
            Route::get('/logged-user', [BusinessController::class, 'getAllByCurrentCompany'])->name('business.getAllByCurrentCompany');
            Route::get('/{id}/logged-user', [BusinessController::class, 'getByIdByCurrentCompany'])->name('business.getByIdByCurrentCompany');

        });

        //Tarjetas de sellos
        Route::prefix('stampcard')->group(function () {
            Route::post('/', [StampCardController::class, 'storeAsCompany'])->name('stampcard.storeAsCompany');
            Route::post('/publish/{id}',[StampCardController::class,'publish'])->name('stampcard.publish');
            Route::post('/unpublish/{id}',[StampCardController::class,'unpublish'])->name('stampcard.unpublish');
            Route::post('/{id}/logged-user', [StampCardController::class, 'updateByIdAsCurrentCompany'])->name('stampcard.updateByIdAsCurrentCompany');
            Route::get('/logged-user', [StampCardController::class, 'getAllByCurrentCompany'])->name('stampcard.getAllByCurrentCompany');
            Route::get('/business/{id}/logged-user', [StampCardController::class, 'getAllByIdByCurrentCompany'])->name('stampcard.getAllByIdByCurrentCompany');
            Route::get('/{id}/logged-user', [StampCardController::class, 'getByIdAsCurrentCompany'])->name('stampcard.getByIdByCurrentCompany');

        });

        //Visitas
        Route::prefix('visit')->group(function () {
            Route::post('/', [VisitController::class, 'storeAsCompany'])->name('visit.storeAsCompany');
            Route::get('stampcard/{id}/logged-user', [VisitController::class, 'getAllByStampCardAsCurrentCompany'])->name('visit.getAllByStampCardAsCurrentCompany');
            Route::get('business/{id}/logged-user', [VisitController::class, 'getAllByBusinessAsCurrentCompany'])->name('visit.getAllByBusinessAsCurrentCompany');

        });

        //Métricas
        Route::prefix('metric')->group(function () {
            Route::post('/global', [MetricController::class, 'getGlobalMetrics'])->name('metric.getGlobalMetrics');
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

Route::prefix('subscription')->group(function () {
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/checkout',[SubscriptionController::class,'checkout'])->name('subscription.checkout');
    });
});

Route::prefix('utils')->group(function () {
    //Categorías de negocio
    Route::prefix('segments')->group(function () {
        Route::get('/', [SegmentController::class, 'getAllSegments'])->name('segment.getAllSegments');
    });

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::prefix('refresh')->group(function () {
            Route::get('/user-data', [UserController::class, 'refreshUserData'])->name('refresh.userData');
        });
    });
});


