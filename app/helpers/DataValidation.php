<?php

namespace App\Helpers;
use App\Models\StampCard;
use Carbon\Carbon;

class DataValidation
{
    public function DeactivateStampCards()
    {
        
        StampCard::where('is_active','=',1)
                 ->where('end_date','<=',Carbon::now())
                 ->update(['is_active' => 0]);

    }
}