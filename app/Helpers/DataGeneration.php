<?php

namespace App\Helpers;

use Exception;
use Illuminate\Support\Str;
use App\Models\Business;
use App\Models\UserStampCard;

class DataGeneration
{
    public function generateRepittCode($lenght, $groups): string
    {

        $alphabet = 'abcdefghijklmnopqrstuvwxyz';
        $randomIndex = rand(0, strlen($alphabet) - 1);
        $randomCharacter = $alphabet[$randomIndex];

        $repittCode = Str::random($lenght);
        $repittCode = strtolower(preg_replace('/[^a-zA-Z]/', $randomCharacter, $repittCode));
        $repittCode = implode('-', str_split($repittCode, $lenght / $groups));
        return $repittCode;

    }

    public function generateRepittCodeByTinker()
    {
        try {

            $businesses = Business::whereNull('business_repitt_code')->get();
            foreach ($businesses as $business) {
                $repittCode = $this->generateRepittCode(6, 3);
                while (Business::where('business_repitt_code', $repittCode)->exists()) {
                    $repittCode = app(DataGeneration::class)->generateRepittCode(6);
                }
                //$business->update(['business_repit_code' => $repittCode]);
                $business->business_repitt_code = $repittCode;
                $business->save();
                // echo 'Business '.$business->id.' updated sucessfully';
            }
            echo "All Businessesess updated succesfully";

        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function generateUserStampCardRepittCodeByTinker()
    {
        try {

            $userStampCards = UserStampCard::whereNull('userstampcard_repitt_code')->get();
            foreach ($userStampCards as $userStampCard) {
                $repittCode = $this->generateRepittCode(12, 4);
                while (Business::where('business_repitt_code', $repittCode)->exists()) {
                    $repittCode = app(DataGeneration::class)->generateRepittCode(12, 4);
                }
                $userStampCard->userstampcard_repitt_code = $repittCode;
                $userStampCard->save();
            }
            echo "All UserStampCards updated succesfully";

        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

}
