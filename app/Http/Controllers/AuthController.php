<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request){
        $rules = [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone' => 'required|string|max:20|unique:users',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|min:8',
            'account_status' => 'required',
            'role' => 'required' //Falta validar que acepte un enum de 2 opciones o con un IF, checar validation rules de laravel
        ];
        $validator = Validator::make($request->input(),$rules);
        if ($validator->fails()){
            return response()->json(
                [
                    'status' => 'error',
                    'errors' => $validator->errors()->all()
                ]
                ,400
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
            ],200
        );
    }

    public function login(Request $request){
        $rules = [
            'email' => 'required|string|email|max:100',
            'password' => 'required|string'
        ];
        $validator = Validator::make($request->input(),$rules);
        if ($validator->fails()){
            return response()->json(
                [
                    'status' => 'error',
                    'errors' => $validator->errors()->all()
                ]
                ,400
                );
        }

        if(!Auth::attempt($request->only('email','password'))){
            return response()->json(
                [
                    'status' => 'error',
                    'errors' => 'Wrong credentials'
                ]
                ,400
                );
        }
        auth()->user()->tokens()->delete(); //Si el login es correcto, elimina todos los tokens para crear uno nuevo (para 1 solo usuario por sesión)

        $user = User::where('email',$request->email)->first();

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
            ],200
        );
    }

    public function logout(){
        auth()->user()->tokens()->delete();
        // Auth::logout();
        return response()->json(
            [
                'status' => 'success',
                'message' => 'User logout successful',
            ],200
        );
    }


}
