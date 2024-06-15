<?php

namespace App\Helpers;
use App\Models\StampCard;
use Carbon\Carbon;
use App\Models\Business;
use App\Helpers\FilesGeneration;
use Exception;

class DataValidation
{
    public function DeactivateStampCards()
    {

        StampCard::where('is_active','=',1)
                 ->where('end_date','<=',Carbon::now())
                 ->update(['is_active' => 0]);

    }

    public function changeBusinessessQrByTinker()
    {
        try {
            $businesses = Business::all();
            foreach ($businesses as $business) {
                // $business->update(
                //     ['qr_path' => app(FilesGeneration::class)->generateQr($business->business_repitt_code,'business')]
                // );
                $business->qr_path = app(FilesGeneration::class)->generateQr($business->business_repitt_code,'business');
                $business->save();
            }
            echo "Business QR updated sucessfully";
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function changeBusinessFlyerByTinker()
    {
        try {
            $businesses = Business::all();
            foreach ($businesses as $business)
            {
                // $business->update(
                //     ['flyer_path' => app(FilesGeneration::class)->generateFlyer($business->business_repitt_code)]);
                $business->flyer_path = app(FilesGeneration::class)->generateFlyer($business->business_repitt_code);
                $business->save();
                echo 'Business '.$business->id.' updated succesfully';
            }
            echo 'All businesses updated succesfully';
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
}
