<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Helpers\DataValidation;


// Artisan::command('inspire', function () {
//     $this->comment(Inspiring::quote());
// })->purpose('Display an inspiring quote')->hourly();
// desctivar las cards que ya hayan expirado


//Compare .ENV APP_ENV=local

if (env('APP_ENV') == 'local') {
    Schedule::call(function(){
        $dataValidation = new DataValidation;
        $dataValidation->DeactivateStampCards();
    })->everyMinute();
}

if (env('APP_ENV') == 'staging') {
    Schedule::call(function(){
        $dataValidation = new DataValidation;
        $dataValidation->changeBusinessessQrByTinker();
    })->everyMinute();
}

if (env('APP_ENV') == 'production') {
    Schedule::call(function(){
        $dataValidation = new DataValidation;
        $dataValidation->changeBusinessFlyerByTinker();
    })->daily();
}
