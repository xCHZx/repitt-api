<?php

namespace App\Helpers;

use Exception;
use Illuminate\Support\Str;
use App\Models\Business;

class DataGeneration
{
    public function generateRepittCode($lenght): string
    {

        $alphabet = 'abcdefghijklmnopqrstuvwxyz';
        $randomIndex = rand(0, strlen($alphabet) - 1);
        $randomCharacter = $alphabet[$randomIndex];

        $repittCode = Str::random($lenght);
        $repittCode = strtolower(preg_replace('/[^a-zA-Z]/', $randomCharacter, $repittCode));
        $repittCode = implode('-', str_split($repittCode, $lenght / 3));
        return $repittCode;

    }

    public function generateRepittCodeByTinker()
    {
        try {

            $businesses = Business::whereNull('business_repitt_code')->get();
            foreach ($businesses as $business) {
                $repittCode = $this->generateRepittCode(6);
                while (Business::where('business_repitt_code', $repittCode)->exists()) {
                    $repittCode = app(DataGeneration::class)->generateRepittCode(6);
                }
                //$business->update(['business_repit_code' => $repittCode]);
                $business->business_repitt_code = $repittCode;
                $business->save();
                echo "Business updated succesfully".$business->id."\n";
            }
            echo "All Businessesess updated succesfully";

        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

}