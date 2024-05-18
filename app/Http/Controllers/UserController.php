<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AccountDetails;
use Exception;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Laravel\Cashier\Subscription;


class UserController extends Controller
{
    public function store($request)
    {
        $user = new User();
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->phone = $request->phone;
        $user->email = $request->email;
        $user->account_status_id = 1; //Falta definir diferentes estados de cuenta
        $user->password = hash::make($request->password);
        $repittCode = $this->generateRepittCode();

        while (User::where('repitt_code', $repittCode)->exists()) {
            $repittCode = $this->generateRepittCode();
        }
        $user->repitt_code = $repittCode;
        $user->qr_path = asset('storage/business/images/qr/' . 'repittcode=' . $user->repitt_code . '.png');

        switch ($request->role) {
            case 'company':
                $user->assignRole('Owner');
                break;
            case 'visitor':
                $user->assignRole('Visitor');
                break;
            default:
                $user->assignRole('Visitor');
                break;
        }
        $user->createAsStripeCustomer(
            [
                'name' => "{$user->firstName} {$user->lastName}",
                'email' => $user->email,
                'phone' => $user->phone,
            ]
        );
        // $user->save();

        $this->generateQr($repittCode);
        $this->storeAccountDetails($user->id);


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
                    'message' => $e->getMessage()
                ],
                403
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
            'activePassword' => 'required|string',
            // contraseña actual del usuario
            'newPassword' => 'required|string' // contraseña que el usuario quiere usar
        ];
        $validation = validator::make($request->input(), $rules);
        if ($validation->fails()) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => $validation->errors()->all()
                ],
                401
            );
        }
        try {
            $authenticatedUser = auth()->user();
            if (!Hash::check($request->activePassword, $authenticatedUser->password)) {
                throw new Exception("Wrong password", 1);
            }
            if ($request->activePassword == $request->newPassword) {
                throw new Exception("Your new passsord has to be different to your actual password", 1);
            }
            User::where('id', $authenticatedUser->id)->update(['password' => Hash::make($request->newPassword)]);
            app(EmailController::class)->notifyPasswordChange($authenticatedUser->name, $authenticatedUser->email);
            return response()->json(
                [
                    'status' => 'success',
                    'message' => 'password changed successfully'
                ],
                200
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

    public function getUserByStripeId($stripeId)
    {
        return User::where('stripe_id', $stripeId)->firstOrFail();
    }

    public function hello()
    {
        try {
            $user = auth()->user();
            if ($user->subscribed()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'todo chido'
                ], 200);
            } else {
                throw new Exception("Error Processing Request", 1);
            }
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'no estas suscrito qlero, pagame'
            ], 401);
        }
    }

    public function createCustomerPortalSession(Request $request)
    {
        // $rules = ['password' => 'required|string'];
        // $validator = Validator::make($request->input(), $rules);
        // if ($validator->fails()) {
        //     return response()->json(
        //         [
        //             'status' => 'error',
        //             'errors' => $validator->errors()->all()
        //         ]
        //         ,
        //         400
        //     );
        // }
        try {
            // obtener al usuario autenticado
            $userId = auth()->user()->id;
            $user = User::find($userId);
            // if(!$user)
            // {
            //     throw new Exception("User not authenticated", 1);

            // };
            // // validar su contraseña
            // if(!Hash::check($request->password,$user->password))
            // {
            //     throw new Exception("Your password is incorrect", 1);
            // };
            $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
            return $stripe->billingPortal->sessions->create([
                'customer' => $user->stripe_id,
                'return_url' => 'https://example.com/account',
            ]);
        } catch (Exception $e) {
            return $e->getMessage();
        }

    }

    public function updateFromStripe($payload)
    {
        try {
            $user = User::where('stripe_id',$payload['data']['object']['id'])->first();
            if(!$user)
            {
                throw new Exception("no hay usuario", 1);
            }
            $user->email = $payload['data']['object']['email'];
            $user->phone = $payload['data']['object']['phone'];
            $user->first_name = $payload['data']['object']['name'];
            if ($user->isDirty('email')) {
                $user->has_verified_email = 0;
            }
            $user->save();
        } catch (Exception $e) {
            return $e;
        }
    }

    public function refreshUserData()
    {
        try {
            $user = auth()->user();
            $subscriptionStatus = $user->subscribed('default');



            return response()->json([
                'status' => 'success',
                'message' => 'User data refreshed successfully',
                'data' => $user,
                'isSubscribed' => $subscriptionStatus,
                'role' => $user->getRoleNames()->first()

            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => [$e->getMessage()]
            ], 400);
        }
    }

    private function storeAccountDetails($userId)
    {
        $accountDetails = new AccountDetails();
        $accountDetails->user_id = $userId;
        $accountDetails->locations_limit = 0; // negocios que puede tener activos
        $accountDetails->stamp_cards_limit = 0; // stampcards que puede tener activas
        $accountDetails->save();
    }
}
