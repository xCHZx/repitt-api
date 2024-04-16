<?php

namespace App\Http\Controllers;

use App\Models\User;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function store($request)
    {
        $user = new User();
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->phone = $request->phone;
        $user->email = $request->email;
        $user->account_status_id = $request->account_status;
        $user->password = hash::make($request->password);
        $repittCode = $this->generateRepittCode();
        $user->qr_path = asset('storage/business/images/qr/'.'repittcode='.$repittCode.'.png');

        while (User::where('repitt_code', $repittCode)->exists()) {
            $repittCode = $this->generateRepittCode();
        }
        $user->repitt_code = $repittCode;

        switch ($request->role) {
            case 'Owner':
                $user->assignRole('Owner');
                break;
            case 'Visitor':
                $user->assignRole('Visitor');
                break;
            default:
                $user->assignRole('Visitor');
                break;
        }
        $user->save();

        $this->generateQr($repittCode);

        return $user;
    }

    private function generateRepittCode(){
        $alphabet = 'abcdefghijklmnopqrstuvwxyz';
        $randomIndex = rand(0, strlen($alphabet) - 1);
        $randomCharacter = $alphabet[$randomIndex];

        $repittCode = Str::random(9);
        $repittCode = strtolower(preg_replace('/[^a-zA-Z]/', $randomCharacter, $repittCode));
        $repittCode = implode('-', str_split($repittCode, 3));
        return $repittCode;
    }

    private function generateQr($repittCode)
    {
        $qrCode = QrCode::format('png')
                ->size(200)
                ->errorCorrection('H')
                ->generate($repittCode);
        
        Storage::disk('public')->put('business/images/qr/'.'repittcode='.$repittCode.'.png',$qrCode);

    }
    // private function saveqrPath($repittCode,$userId)
    // {
    //     $this->generateQr($repittCode,$userId);

    //     $user = User::find($userId);
    //     $user->qr_path = asset('storage/business/images/qr/'.$userId.'.png');
    //     $user->save();

    // }
}
