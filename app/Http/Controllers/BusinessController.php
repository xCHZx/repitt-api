<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Segment;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BusinessController extends Controller
{
    public function getAllByCurrentUser()
    {
        try {
            if (!$businesses = auth()->user()->businesses) {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => ['Resource not found']
                    ],
                    404
                );
            }
            return response()->json(
                [
                    'status' => 'success',
                    'data' => [
                        $businesses
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
                404
            );
        }
    }

    public function storeAsCompany(Request $request)
    {
        // Validate if the user is an owner
        if (!auth()->user()->hasRole('Owner')) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => ['Unauthorized']
                ],
                    401
                );
            }

        $rules = [
            'name' => 'required|string|max:100',
            //'logo_string' => 'required|base64_image_size:500',
            'segment_id' => 'required|integer',
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

            // return($request->logo_file);

            $business = new Business();
            $business->name = $request->name;
            $business->description = $request->description;
            $business->address = $request->address;
            $business->phone = $request->phone;
            $business->opening_hours = $request->opening_hours;
            $business->segment_id = $request->segment_id;
            if(!$request->logo_file)
            {
                $business->logo_path = asset('storage/placeholders/logo-placeholder.png');
                //$logo_path = resource_path('../resources/placeholders/logo-placeholders.png');
            }
            else
            {
                $file = $request->file('logo_file');
                $this->SaveLogo($file);
                $business->logo_path = asset('storage/business/images/logo/'.$file->hashName());

            }
            $business->save();
            $business->users()->attach(auth()->id());
            return response()->json(
                [
                    'status' => 'success',
                    'message' => 'Business creation successful',
                    'data' => [
                        $business,
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
                404
            );
        }
    }

    public function storeFromLogin($request, $userId)
    {
        // Validate if the user is an owner
        $user = User::find($userId);
        if (!$user->hasRole('Owner')) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => ['Unauthorized']
                ],
                    401
                );
            }

        $rules = [
            'name' => 'required|string|max:100',
            //'logo_string' => 'required|base64_image_size:500',
            'segment_id' => 'required|integer',
        ];
        $validator = Validator::make($request, $rules);
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

            // return($request->logo_file);

            $business = new Business();
            $business->name = $request["name"];
            $business->description = $request["description"];
            $business->address = $request["address"];
            $business->phone = $request["phone"];
            $business->opening_hours = $request["opening_hours"];
            $business->segment_id = $request["segment"];
            if(!$request->logo_file)
            {
                $business->logo_path = asset('storage/placeholders/logo-placeholder.png');
                //$logo_path = resource_path('../resources/placeholders/logo-placeholders.png');
            }
            else
            {
                $file = $request->file('logo_file');
                $this->SaveLogo($file);
                $business->logo_path = asset('storage/business/images/logo/'.$file->hashName());

            }
            $business->save();
            $business->users()->attach(auth()->id());
            return response()->json(
                [
                    'status' => 'success',
                    'message' => 'Business creation successful',
                    'data' => [
                        $business,
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
                404
            );
        }
    }

    public function updateByCurrentCompany(Request $request, $id)
    {

                // Validate if the user is an owner
        if (!auth()->user()->hasRole('Owner')) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => ['Unauthorized']
                ],
                    401
                );
            }

        $rules = [
            'name' => 'required|string|max:100',
            //'logo_string' => 'required|base64_image_size:500',
            'segment' => 'required|integer',
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
            if (!$business = auth()->user()->businesses()->find($id)) {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => ['Resource not found']
                    ],
                    404
                );
            }
            $business = auth()->user()->businesses()->find($id);
            $business->name = $request->name;
            $business->description = $request->description;
            $business->address = $request->address;
            $business->phone = $request->phone;
            $business->opening_hours = $request->opening_hours;
            $business->segment_id = $request->segment;
            if($request->logo_file)
            {
                $file = $request->file('logo_file');
                $this->SaveLogo($file);
                $business->logo_path = asset('storage/business/images/logo/'.$file->hashName());
            }
            $business->save();
            return response()->json(
                [
                    'status' => 'success',
                    'message' => 'Business update successful',
                    'data' => [
                        'name' => $business->name,
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
                404
            );
        }
    }

    public function getVisitedByCurrentUser(){
        try{
            $res = auth()->user()->businesses()->whereHas('stamp_cards', function($query){
                $query->whereHas('visits', function($query){
                    $query->where('user_id', auth()->id());
                });
            })->get();


            if (! $res or $res->isEmpty()){
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => ['Resource not found']
                    ],404
                );
            }
            return response()->json(
                [
                    'status' => 'success',
                    'data' => [
                        $res
                    ]
                ],200
            );

        }catch(Exception $e){
            return response()->json(
                [
                    'status' => 'error',
                    'message' => [$e->getMessage()]
                ],404
            );
        }
    }

    public function getAllByCurrentCompany(){
        try{
            $res = auth()->user()->businesses()->with('segment')->get();
            if (! $res or $res->isEmpty()){
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => ['Resource not found']
                    ],404
                );
            }
            return response()->json(
                [
                    'status' => 'success',
                    'data' => [
                        $res
                    ]
                ],200
            );

        }catch
        (Exception $e){
            return response()->json(
                [
                    'status' => 'error',
                    'message' => [$e->getMessage()]
                ],404
            );
        }
    }

    public function getByIdByCurrentCompany($id){
        try{
            $res = auth()->user()->businesses()->with('segment')->find($id);
            if (! $res){
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => ['Resource not found']
                    ],404
                );
            }
            return response()->json(
                [
                    'status' => 'success',
                    'data' => [
                        $res
                    ]
                ],200
            );

        }catch
        (Exception $e){
            return response()->json(
                [
                    'status' => 'error',
                    'message' => [$e->getMessage()]
                ],404
            );
        }
    }

    private function SaveLogo($logo)
    {
        Storage::disk('public')->put('business/images/logo/',$logo);

    }
}
