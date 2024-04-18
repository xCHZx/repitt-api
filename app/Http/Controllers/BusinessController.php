<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BusinessController extends Controller
{
    public function getAll()
    {
        try {
            $businesses = Business::all();
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

    public function getById($id)
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

    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:100',
            //'logo_string' => 'required|base64_image_size:500',
            'segment' => 'required|string|max:100',
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
            $business->segment = $request->segment;
            
            if(!$request->logo_string)
            {
                $business->logo_path = asset('storage/business/images/logo/placeholder.png');
                $business->save();
                
            }
            else{
                $business->save();
                $this->generateLogo($request->logo_string,$business->id);
                $logo_path = asset('storage/business/images/logo/'.$business->id.'.png');
                Business::where('id',$business->id)
                          ->update(['logo_path' => $logo_path]);
            }
           
            $business->users()->attach(auth()->id()); 
            return response()->json(
                [
                    'status' => 'success',
                    'message' => 'Business creation successful',
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

            if($request->logo_string)
            {
                $this->generateLogo($request->logo_string,$id);
                $business->logo_path = asset('storage/business/images/logo/'.$id.'.png');
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
            if($request->logo_string)
            {
                $this->generateLogo($request->logo_string,$id);
                $business->logo_path = asset('storage/business/images/logo/'.$id.'.png');
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

    public function delete(Request $request)
    {
        return ('delete');
    }

    private function generateLogo($logo_string,$id)
    {
        // Obtengo los bins de la imagen decodificando el string
        $bin = base64_decode($logo_string);

        // obtengo el tamaño de la imagen en kilobytes 
        $imageSize = strlen($bin) / 1024;
          
        // Checo si la imagen es mayor a el tamaño maximo
        if($imageSize > 500)
        {
            throw new Exception("La imagen es mayor a 500 kb", 1);
        }

        // convierto los bin en un Gdimage
        $im = imageCreateFromString($bin);
 
        // me aseguro de si tener la imagen 
        if (!$im) {
            throw new Exception("Este recurso no es una imagen o hubo un problema con la imagen", 1);
            
        }
        //guardo el recurso GD como una imagen png en el storage, para eso utilizo un buffer
        ob_start();
        imagepng($im);
        $imagebuffer = ob_get_clean();
        Storage::disk('public')->put('business/images/logo/'.$id.'.png',$imagebuffer);

        // libero la memoria
        imagedestroy($im);

    }
}