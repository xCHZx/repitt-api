<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Laravel\Sanctum\HasApiTokens;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $rules = [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone' => 'required|string|max:20|unique:users',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|min:8',
            // 'account_status' => 'required',
            'role' => 'required' //Falta validar que acepte un enum de 2 opciones o con un IF, checar validation rules de laravel
        ];
        $validator = Validator::make($request->input(), $rules);
        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => $validator->errors()->all()
                ]
                ,
                400
            );
        }
        // $user = User::create([
        //     'name' => $request->name,
        //     'email' => $request->email,
        //     'password' => Hash::make($request->password)
        // ]);
        $user = app(UserController::class)->store($request);
        $token = $user->createToken('API_TOKEN')->plainTextToken;


        return response()->json(
            [
                'status' => 'success',
                'message' => 'User creation successful',
                'token' => $token,
                'role' => $user->getRoleNames()->first(),
                'data' => [
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'repitt_code' => $user->repitt_code,
                ]
            ],
            200
        );
    }

    public function login(Request $request)
    {
        $rules = [
            'email' => 'required|string|email|max:100',
            'password' => 'required|string'
        ];
        $validator = Validator::make($request->input(), $rules);
        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => $validator->errors()->all()
                ]
                ,
                400
            );
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => ['Wrong credentials']
                ]
                ,
                400
            );
        }
        auth()->user()->tokens()->delete(); //Si el login es correcto, elimina todos los tokens para crear uno nuevo (para 1 solo usuario por sesión)

        $user = User::where('email', $request->email)->first();

        $token = $user->createToken('API_TOKEN')->plainTextToken;
        return response()->json(
            [
                'status' => 'success',
                'message' => 'User login successful',
                'token' => $token,
                'role' => $user->getRoleNames()->first(),
                'data' => [
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'repitt_code' => $user->repitt_code,
                ]
            ],
            200
        );
    }

    public function logout()
    {
        auth()->user()->tokens()->delete();
        // Auth::logout();
        return response()->json(
            [
                'status' => 'success',
                'message' => 'User logout successful',
            ],
            200
        );
    }


    public function sendverifyEmail()
    {
        try {

            $userId = auth()->user()->id;
            $userName = auth()->user()->name;
            $email_verified_at = auth()->user()->email_verified_at;
            $has_verified_email = auth()->user()->has_verified_email;
            $userMail = auth()->user()->email;

            if (!$email_verified_at || !$has_verified_email) {
                $validationCode = rand(10000, 99999);
                // while (Cache::has($validationCode)) {
                //     $validationCode = rand(10000, 99999);
                // };

                Cache::add("userCode:" . $userId, $validationCode, now()->addMinutes(5));

                app(EmailController::class)->sendVerifyEmail($validationCode, $userMail, $userName);
            } else {
                throw new Exception("This email has already been verified", 1);

            }

            return response()->json(
                [
                    'status' => 'success',
                    'message' => 'verification email sended successfully'
                ],
                200
            );
        } catch (Exception $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => [$e->getMessage()]
                ],
                401
            );
        }


    }

    public function verifyEmail(Request $request)
    {

        $rules = [
            'verification_code' => 'required',
        ];
        $validator = Validator::make($request->input(), $rules);
        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => $validator->errors()->all()
                ]
                ,
                400
            );
        }

        try {
            $userId = auth()->user()->id;
            $verificationCode = Cache::get("userCode:" . $userId);

            if (!$verificationCode) {
                throw new Exception("This user does not have a verification code, or  your code may have expired", 1);

            }

            if ($verificationCode == $request->verification_code) {
                // modificar sus validaciones
                $user = User::find($userId);
                $user->has_verified_email = 1;
                $user->email_verified_at = Carbon::now();
                $user->save();

                //eliminar el codigo del cache
                Cache::forget("userCode:" . $userId);
                return response()->json(
                    [
                        'status' => 'success',
                        'message' => 'Email Verified with success'
                    ],
                    200
                );
            } else {
                throw new Exception("Your verification code is not correct", 1);

            }
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

    public function sendPasswordRecoveryMail(Request $request)
    {
        // validar que el usuario me mando un correo
        $rules = [
            'email' => 'required'
        ];
        $validation = Validator::make($request->input(), $rules);
        if ($validation->fails()) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => $validation->errors()->all()
                ],
                400
            );
        }

        try {
            $user = User::where('email', $request->email)->firstOrFail();
            if (!$user) {
                throw new Exception("El correo no coincide con ningun usuario", 1);

            }
            $userToken = rand(10000, 90000);
            Cache::add('userToken:' . $userToken, $user->id, now()->addMinutes(5));
            $encryptedToken = Crypt::encrypt($userToken);

            app(EmailController::class)->sendPasswordRecoveryEmail($encryptedToken, $user->email, $user->first_name);

            return response()->json(
                [
                    'status' => 'success',
                    'message' => 'password email recovery sended succesfully'
                ],
                200
            );
        } catch (Exception $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => [$e->getMessage()]
                ],
                401
            );
        }

    }

    public function recoverPassword(Request $request)
    {
        // validar
        $rules = [
            'token' => 'required|string',
            'password' => 'required|string'
        ];
        $validation = Validator::make($request->input(), $rules);
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
            $token = Crypt::decrypt($request->token);
            $userId = Cache::get('userToken:' . $token);
            if (!$userId) {
                throw new Exception("the token doesnt exist or  has expired", 1);

            }

            $user = User::find($userId);
            if (Hash::check($request->password,$user->password)) {
                throw new Exception("your new password must be diferent to your old password", 1);

            }
            $user->password = Hash::make($request->password);
            // $user->has_verified_email = 1;
            // $user->email_verified_at = Carbon::now();
            $user->save();
            Cache::forget('userToken:'.$token);

            // notificar al usuario por correo de que se ha cambiado su contraseña
            app(EmailController::class)->notifyPasswordChange($user->name,$user->email);

            return response()->json(
                [
                    'status' => 'succes',
                    'message' => 'password changed succesfully'
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


}
