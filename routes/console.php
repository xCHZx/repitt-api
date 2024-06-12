<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Helpers\DataValidation;


// Artisan::command('inspire', function () {
//     $this->comment(Inspiring::quote());
// })->purpose('Display an inspiring quote')->hourly();
// desctivar las cards que ya hayan expirado
Schedule::call(function(){
    $dataValidation = new DataValidation;
    $dataValidation->DeactivateStampCards();
})->everyMinute();
