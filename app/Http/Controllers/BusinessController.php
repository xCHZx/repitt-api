<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Segment;
use App\Models\StampCard;
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

    public function createBusinessAsCompany(Request $request)
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
                ],
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
            if (!$request->logo_file) {
                $business->logo_path = asset('storage/placeholders/logo-placeholder.png');
                //$logo_path = resource_path('../resources/placeholders/logo-placeholders.png');
            } else {
                $file = $request->file('logo_file');
                $this->SaveLogo($file);
                $business->logo_path = asset('storage/business/images/logo/' . $file->hashName());
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
                ],
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
            if (!$request->logo_file) {
                $business->logo_path = asset('storage/placeholders/logo-placeholder.png');
                //$logo_path = resource_path('../resources/placeholders/logo-placeholders.png');
            } else {
                $file = $request->file('logo_file');
                $this->SaveLogo($file);
                $business->logo_path = asset('storage/business/images/logo/' . $file->hashName());
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

    public function updateBusinessAsCurrentCompany(Request $request, $id)
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
                ],
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
            if ($request->logo_file) {
                $file = $request->file('logo_file');
                $this->SaveLogo($file);
                $business->logo_path = asset('storage/business/images/logo/' . $file->hashName());
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

    public function getVisitedByCurrentUser()
    {
        try {
            $res = auth()->user()->businesses()->whereHas('stamp_cards', function ($query) {
                $query->whereHas('visits', function ($query) {
                    $query->where('user_id', auth()->id());
                });
            })->get();


            if (!$res or $res->isEmpty()) {
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
                        $res
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

    public function getAllBusinessAsCurrentCompany()
    {
        try {
            $res = auth()->user()->businesses()->with('segment')->get();
            if (!$res or $res->isEmpty()) {
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
                        $res
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

    public function getBusinessByIdAsCurrentCompany($id)
    {
        try {
            $res = auth()->user()->businesses()->with('segment')->find($id);
            if (!$res) {
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
                        $res
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

    public function publishBusiness($id)
    {
        try {
            // validar que el usuario esta suscrito
            if (!auth()->user()->subscribed('default')) {
                throw new Exception("Necesitas una suscripcion para realizar esta accion", 1);
            }
            // validar que el usuario pueda publicar negocios
            $activeBusinesses = auth()->user()->businesses->where('is_active', 1);
            $userAccountDetails = auth()->user()->account_details;
            // si usuario ya tiene negocios activos ver que no pase del limite
            if ($activeBusinesses->isNotEmpty()) {
                if ($userAccountDetails->locations_limit <= count($activeBusinesses)) {
                    throw new Exception("No puedes publicar mas negocios", 1);
                }
            } // cambiar a elseif
            else { // si no tiene negocios activos, ver que el limite sea mayor a 0
                if ($userAccountDetails->locations_limit == 0) {
                    throw new Exception("No puedes publicar ningun negocio", 1);
                }
            }
            // publicar negocio
            $business = Business::find($id);
            $business->is_active = 1;
            $business->save();
            return response()->json(
                [
                    'status' => 'success',
                    'data' => $business,
                    'message' => ['negocio publicado con exito']
                ],
                200
            );
        } catch (Exception $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => [$e->getMessage()]
                ],
                403
            );
        }
    }
    public function unpublishBusiness($id)
    {
        // validar que el usuario tenga ese negocio
        try {
            if (!auth()->user()->businesses->find($id)) { // -- si no lo tiene retornar un mensaje de no autorizado
                throw new Exception('no se encontro el negocio que buscas', 1);
            }
            // editar status
            $business = Business::find($id);
            $business->is_active = 0;
            $business->save();
            // verificar si tiene stampcards activas
            $aciveStampCards = $business->stamp_cards()->where('is_active', 1)->get();
            if ($aciveStampCards->isNotEmpty()) {
                // si tiene ,desactivarlas
                StampCard::where('business_id', $business->id)
                    ->where('is_active', 1)
                    ->update(['is_active' => 0]);
            }
            return response()->json(
                [
                    'status' => 'success',
                    'data' => $business,
                    'message' => ['el negocio ya no se encuentra publico']
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

    public function unpublishByStripe($user)
    {
        try {
            $businessesIds = $user->businesses->where('is_active', 1)->pluck('id');
            if ($businessesIds->isNotEmpty()) {
                Business::whereIn('id', $businessesIds)->update(['is_active' => 0]);
                StampCard::whereIn('business_id', $businessesIds)->update(['is_active' => 0]);
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
    private function SaveLogo($logo)
    {
        Storage::disk('public')->put('business/images/logo/', $logo);
    }
}
