<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Carbon\Carbon;

// Artisan::command('inspire', function () {
//     $this->comment(Inspiring::quote());
// })->purpose('Display an inspiring quote')->hourly();
// desctivar las cards que ya hayan expirado
Schedule::call(function (){
    DB::table('stamp_cards')->where('end_date','<=',Carbon::now())
                            ->where('is_active', '=' ,1)
                            ->update(['is_active' => 0]);
})->daily();