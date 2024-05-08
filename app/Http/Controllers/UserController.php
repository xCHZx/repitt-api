<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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

        while (User::where('repitt_code', $repittCode)->exists()) {
            $repittCode = $this->generateRepittCode();
        }
        $user->repitt_code = $repittCode;
        $user->qr_path = asset('storage/business/images/qr/' . 'repittcode=' . $user->repitt_code . '.png');

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

    public function update(Request $request)
    {
        try {
            $authenticatedUser = auth()->user();
            // modificar todos los datos que el usuario envio
            $user = User::find($authenticatedUser->id);
            $user->first_name = (isset($request->first_name)) ? $request->first_name : $authenticatedUser->first_name;
            $user->last_name = (isset($request->last_name)) ? $request->last_name : $authenticatedUser->last_name;
            $user->phone = (isset($request->phone)) ? $request->phone : $authenticatedUser->phone;
            $user->email = (isset($request->email)) ? $request->email : $authenticatedUser->email;
            // si modifico el correo poner el has_verified_email como false
            if ($user->isDirty('email')) {
                $user->has_verified_email = 0;
            }
            $user->save();
        } catch (Exception $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'error' => $e->getMessage()
                ],403
                );
        }
        return response()->json(
            [
                'status' => 'success',
                'message' => 'User updated successfully'
            ],
            200
        );
    }

    public function updatePassword(Request $request)
    {
        $rules = [
            'activePassword' => 'required|string', // contraseña actual del usuario
            'newPassword' => 'required|string' // contraseña que el usuario quiere usar
        ];
        $validation = validator::make($request->input(),$rules);
        if($validation->fails())
        {
            return response()->json(
                [
                    'status' => 'error',
                    'error' => $validation->errors()->all()
                ],401
            );
        }
        try {
            $authenticatedUser = auth()->user();
            if(!Hash::check($request->activePassword,$authenticatedUser->password))
            {
                throw new Exception("Wrong password", 1);
            }
            if($request->activePassword == $request->newPassword)
            {
                throw new Exception("Your new passsord has to be different to your actual password", 1);
            }
            User::where('id',$authenticatedUser->id)->update(['password' => Hash::make($request->newPassword)]);
            app(EmailController::class)->notifyPasswordChange($authenticatedUser->name,$authenticatedUser->email);
            return response()->json(
                [
                    'status' => 'success',
                    'message' => 'password changed successfully'
                ],200
            );
        } catch (Exception $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => [$e->getMessage()]
                ],403
            );
        }
    }

    public function getCurrentVisitorData()
    {
        try {
            $user = auth()->user();
            //Agregar la relación AccountStatus
            $user->account_status;
            //Agregar el total de visitas
            $user->visits_count = $user->visits->count();
            // Agregar sólo nombre del rol asignado
            // if($user->hasRole('Owner')){
            //     $user->role = 'Owner';
            // }else{
            //     $user->role = 'Visitor';
            // }

            return response()->json(
                [
                    'status' => 'success',
                    'data' => [
                        $user
                    ]
                ],
                200
            );
        } catch (Exception $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => [$e->getMessage()]
                ],
                400
            );
        }
    }

    private function generateRepittCode()
    {
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

        Storage::disk('public')->put('business/images/qr/' . 'repittcode=' . $repittCode . '.png', $qrCode);

    }
// private function saveqrPath($repittCode,$userId)
// {
//     $this->generateQr($repittCode,$userId);

//     $user = User::find($userId);
//     $user->qr_path = asset('storage/business/images/qr/'.$userId.'.png');
//     $user->save();

// }
}
