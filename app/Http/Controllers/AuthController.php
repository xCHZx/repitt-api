<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

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
            'account_status' => 'required',
            'role' => 'required' //Falta validar que acepte un enum de 2 opciones o con un IF, checar validation rules de laravel
        ];
        $validator = Validator::make($request->input(), $rules);
        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => 'error',
                    'errors' => $validator->errors()->all()
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
                'token' => $token
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
                    'errors' => $validator->errors()->all()
                ]
                ,
                400
            );
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(
                [
                    'status' => 'error',
                    'errors' => 'Wrong credentials'
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
                'data' => [
                    'name' => $user->name,
                    'email' => $user->email,
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

                Cache::add("userCode:".$userId,$validationCode, now()->addMinutes(1));
               
                app(EmailController::class)->sendVerifyEmail($validationCode,$userMail,$userName);
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
                    'message' => $e->getMessage()
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
                    'errors' => $validator->errors()->all()
                ]
                ,
                400
            );
        }
        
        $userId = auth()->user()->id;
        $verificationCode = Cache::pull("userCode:".$userId);

        if($verificationCode == $request->verification_code)
        {
            // modificar el has_verified_email de el usuario
            // modificar el email_verified_at del usuario
            return response()->json(
                [
                    'status' => 'success',
                    'message' => 'Email Verified with success',
                    'verification_code' => $verificationCode
                ],200
            );
        }
    }


}