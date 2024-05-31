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



//Auth Routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('/send-password-recovery-mail',[AuthController::class,'sendPasswordRecoveryMail'])->name('sendpasswordrecoveryMail');
    Route::post('/password-recover', [AuthController::class, 'recoverPassword'])->name('password-recover');


    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::post('/send-verify-email', [AuthController::class , 'sendverifyEmail'])->name('auth.sendEmail');
        Route::post('/verify-email', [AuthController::class , 'verifyEmail'])->name('auth.verifyEmail');
        Route::post('/update-password', [UserController::class,'updatePassword'])->name('user.updatePassword');
    });
});


//Company Profiles Routes
Route::prefix('company')->group(function () {
    Route::middleware(['auth:sanctum'])->group(function () {
        //Negocio
        Route::prefix('business')->group(function () {
            Route::post('/', [BusinessController::class, 'createBusinessAsCompany'])->name('business.createBusinessAsCompany');
            Route::post('/{id}/logged-user', [BusinessController::class, 'updateBusinessAsCurrentCompany'])->name('business.updateBusinessAsCurrentCompany');
            Route::post('/{id}/publish',[BusinessController::class,'publishBusiness'])->name('business.publishBusiness');
            Route::post('/{id}/unpublish',[BusinessController::class,'unpublishBusiness'])->name('business.unpublishBusiness');
            Route::get('/logged-user', [BusinessController::class, 'getAllBusinessAsCurrentCompany'])->name('business.getAllBusinessAsCurrentCompany');
            Route::get('/{id}/logged-user', [BusinessController::class, 'getBusinessByIdAsCurrentCompany'])->name('business.getBusinessByIdAsCurrentCompany');
        });

        //Tarjetas de sellos
        Route::prefix('stampcard')->group(function () {
            Route::post('/', [StampCardController::class, 'createStampCardAsCompany'])->name('stampcard.createStampCardAsCompany');
            Route::post('/{id}/publish',[StampCardController::class,'publishStampCard'])->name('stampcard.publishStampCard');
            Route::post('/{id}/unpublish',[StampCardController::class,'unpublishStampCard'])->name('stampcard.unpublishStampCard');
            Route::post('/{id}/logged-user', [StampCardController::class, 'updateStampCardByIdAsCurrentCompany'])->name('stampcard.updateStampCardByIdAsCurrentCompany');
            Route::get('/logged-user', [StampCardController::class, 'getAllStampCardsAsCurrentCompany'])->name('stampcard.getAllStampCardsAsCurrentCompany');
            Route::get('/business/{id}/logged-user', [StampCardController::class, 'getAllStampCardsByBusinessIdAsCurrentCompany'])->name('stampcard.getAllStampCardsByBusinessIdAsCurrentCompany');
            Route::get('/business/{id}/active/logged-user', [StampCardController::class, 'getAllActiveStampCardsByBusinessIdAsCurrentCompany'])->name('stampcard.getAllActiveStampCardsByBusinessIdAsCurrentCompany');
            Route::get('/{id}/logged-user', [StampCardController::class, 'getStampCardByIdAsCurrentCompany'])->name('stampcard.getStampCardByIdAsCurrentCompany');
        });

        //Visitas
        Route::prefix('visit')->group(function () {
            Route::post('/', [VisitController::class, 'registerVisitAsCompany'])->name('visit.registerVisitAsCompany');
            Route::get('stampcard/{id}/logged-user', [VisitController::class, 'getAllVisitsByStampCardIdAsCurrentCompany'])->name('visit.getAllVisitsByStampCardIdAsCurrentCompany');
            Route::get('business/{id}/logged-user', [VisitController::class, 'getAllVisitsByBusinessIdAsCurrentCompany'])->name('visit.getAllVisitsByBusinessIdAsCurrentCompany');
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
        //Tarjetas de sellos
        Route::prefix('stampcard')->group(function () {
            Route::get('/logged-user', [StampCardController::class, 'getAllStampCardsByCurrentVisitor'])->name('stampcard.getAllStampCardsByCurrentVisitor');
            Route::get('/{id}', [StampCardController::class, 'getStampCardByIdAsVisitor'])->name('business.getStampCardByIdAsVisitor');
        });

        Route::prefix('visit')->group(function () {
        Route::get('/logged-user', [VisitController::class, 'getAllVisitsAsCurrentVisitor'])->name('visit.getAllVisitsAsCurrentVisitor');
        });

        Route::prefix('user')->group(function () {
            Route::get('/logged-user', [UserController::class, 'getCurrentVisitorData'])->name('user.getCurrentVisitorData');
        });
    });

    //Without auth
    Route::prefix('business')->group(function () {
        // Route::get('/visited/logged-in', [BusinessController::class, 'getVisitedByCurrentVisitor'])->name('business.getVisitedByCurrentVisitor');
        Route::get('/{id}', [BusinessController::class, 'getBusinessByIdAsVisitor'])->name('business.getBusinessByIdAsVisitor');
    });
});

Route::prefix('subscription')->group(function () {
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/checkout',[SubscriptionController::class,'checkout'])->name('subscription.checkout');
        Route::get('/billing-portal',[UserController::class,'createCustomerPortalSession'])->name('user.billing-portal');
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


//Others
Route::post('/stripe-webhook', [StripeWebhookController::class,'handlewebhook'])->name('webhook');
