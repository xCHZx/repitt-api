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
    public function getAll(){
        try{
            $businesses = Business::all();
            return response()->json(
                [
                    'status' => 'success',
                    'data' => [
                        $businesses
                    ]
                ],200
            );

        }catch(Exception $e){
            return $e;
        }
    }

    public function getById($id){
        try{
            if (! $business = Business::find($id)){
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'Resource not found'
                    ],
                    404
                );
            }
            return response()->json(
                [
                    'status' => 'success',
                    'data' => [
                        $business
                    ]
                ],
                200
            );
        } catch (Exception $e) {
            return $e;
        }
    }

    public function getAllByCurrentUser()
    {
        try {
            if (!$businesses = auth()->user()->businesses) {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'Resource not found'
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
            return $e;
        }
    }

    public function storeAsCompany(Request $request)
    {
        // Validate if the user is an owner
        if (!auth()->user()->hasRole('Owner')) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unauthorized'
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
                    'errors' => $validator->errors()->all()
                ]
                ,
                400
            );
        }
        try {

            $business = new Business();
            $business->name = $request->name;
            $business->description = $request->description;
            $business->address = $request->address;
            $business->phone = $request->phone;
            $business->segment_id = $request->segment;
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
            return $e;
        }
    }


    public function update(Request $request, $id)
    {
        try {
            if (!$business = Business::find($id)) {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'Resource not found'
                    ],
                    404
                );
            }
            $business = Business::find($id);
            $business->name = $request->name;
            $business->description = $request->description;
            $business->address = $request->address;

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
            return $e;
        }
    }

    public function updateByCurrentUser(Request $request, $id)
    {
        try {
            if (!$business = auth()->user()->businesses->find($id)) {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'Resource not found'
                    ],
                    404
                );
            }
            $business->name = $request->name;
            $business->description = $request->description;
            $business->address = $request->address;
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
            return $e;
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
                        'message' => 'Resource not found'
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
            return $e;
        }
    }

    public function getAllByCurrentCompany(){
        try{
            $res = auth()->user()->businesses()->with('segment')->get();
            if (! $res or $res->isEmpty()){
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'Resource not found'
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
            return $e;
        }
    }

    public function getByIdByCurrentCompany($id){
        try{
            $res = auth()->user()->businesses()->with('segment')->find($id);
            if (! $res){
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'Resource not found'
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
            return $e;
        }
    }

    public function delete(Request $request)
    {
        return ('delete');
    }

    private function SaveLogo($logo)
    {
        Storage::disk('public')->put('business/images/logo/',$logo);

    }
}
