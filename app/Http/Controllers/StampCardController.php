<?php

namespace App\Http\Controllers;

use App\Models\StampCard;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class StampCardController extends Controller
{
    public function createStampCardAsCompany(Request $request)
    {
        //Verificar si el usuario tiene el rol Owner
        if (!auth()->user()->hasRole('Owner')) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unauthorized Role'
                ],
                401
            );
        }

        $rules = [
            'name' => 'required|string',
            'description' => 'required|string',
            'required_stamps' => 'required|integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            // 'primary_color' => 'required|string',
            'business_id' => 'required|integer',
            'reward' => 'required|string'
        ];

        $validator = Validator::make($request->all(), $rules);
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
            $stampCard = new StampCard();
            $stampCard->name = $request->name;
            $stampCard->description = $request->description;
            $stampCard->start_date = $request->start_date;
            $stampCard->end_date = $request->end_date;
            // $stampCard->stamp_icon_path = $request->stamp_icon_path;
            // $stampCard->primary_color = $request->primary_color;
            $stampCard->business_id = $request->business_id;
            $stampCard->reward = $request->reward;
            if ($request->required_stamps >= 3 && $request->required_stamps <= 20) {
                $stampCard->required_stamps = $request->required_stamps;
            } else {
                throw new Exception("Las visitas requeridas deben estar entre 3 y 20", 1);
            }
            $stampCard->required_stamps = $request->required_stamps;
            if (!$request->stamp_icon_file) {
                $stampCard->stamp_icon_path = asset('assets/placeholders/icon-placeholder.png');
            } else {
                $file = $request->file('stamp_icon_file');
                $this->saveIcon($file);
                $stampCard->stamp_icon_path = asset('storage/business/images/icons/' . $file->hashName());

            }
            $stampCard->save();

            return response()->json(
                [
                    'status' => 'success',
                    'data' => [
                        $stampCard
                    ]
                ],
                201
            );
        } catch (Exception $e) {
            return $e;
        }

    }

    public function updateStampCardByIdAsCurrentCompany(Request $request, $id)
    {
        if (!auth()->user()->hasRole('Owner')) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => ['Unauthorized Role']
                ],
                401
            );
        }

        $rules = [
            'name' => 'required|string',
            'description' => 'required|string',
            // 'required_stamps' => 'required|integer',
            // 'start_date' => 'required|date',
            // 'end_date' => 'required|date',
            // 'primary_color' => 'required|string',
            // 'business_id' => 'required|integer',
            // 'reward' => 'required|string'
        ];

        $validator = Validator::make($request->all(), $rules);

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
            //Verify if the StampCard exists in the current business collection of the user
            $businessesIds = auth()->user()->businesses->pluck('id');
            $stampCard = StampCard::whereIn('business_id', $businessesIds)->find($id);
            if (!$stampCard) {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => ['Resource not found']
                    ],
                    404
                );
            }
            $stampCard->name = $request->name;
            $stampCard->description = $request->description;
            // $stampCard->required_stamps = $request->required_stamps;
            // $stampCard->start_date = $request->start_date;
            // $stampCard->end_date = $request->end_date;
            // $stampCard->business_id = $request->business_id;
            // $stampCard->reward = $request->reward;
            if ($request->hasFile('stamp_icon_file')) {
                $file = $request->file('stamp_icon_file');
                $this->saveIcon($file);
                $stampCard->stamp_icon_path = asset('storage/business/images/icons/' . $file->hashName());
            }
            $stampCard->save();

            return response()->json(
                [
                    'status' => 'success',
                    'data' => [
                        $stampCard
                    ]
                ],
                201
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

    public function getAllStampCardsByBusinessIdAsCurrentCompany($businessId)
    {
        try {
            $userId = auth()->user()->id;
            //Validate if the businessId belongs to a business of the current user
            $business = auth()->user()->businesses->where('id', $businessId)->first();
            if (!$business) {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => ['Resource not found']
                    ],
                    404
                );
            }

            $stampCards = StampCard::where('business_id', $businessId)->with([
                'business' => function ($query) {
                    $query->select('id', 'name');
                }
            ])->get();




            if (!$stampCards or $stampCards->isEmpty()) {
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
                        $stampCards
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

    public function getAllActiveStampCardsByBusinessIdAsCurrentCompany($businessId)
    {
        try {
            $userId = auth()->user()->id;
            //Validate if the businessId belongs to a business of the current user
            $business = auth()->user()->businesses->where('id', $businessId)->first();
            if (!$business) {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => ['Resource not found']
                    ],
                    404
                );
            }

            $stampCards = StampCard::where('business_id', $businessId)
                ->where('is_active', 1)
                ->with([
                    'business' => function ($query) {
                        $query->select('id', 'name');
                    }
                ])->get();

            if (!$stampCards or $stampCards->isEmpty()) {
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
                        $stampCards
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

    public function getAllStampCardsByCurrentVisitor() //Used
    {
        try {
            $userId = auth()->user()->id;

            $stampCards = StampCard::whereHas('visits', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })->with([
                    'business' => function ($query) {
                        $query->select('id', 'name', 'logo_path', 'segment_id');
                    },
                    'business.segment'
                ])->with([
                    'visits' => function ($query) use ($userId) {
                        $query->where('user_id', $userId);
                    }
                ])->withCount([
                    'visits' => function ($query) use ($userId) {
                        $query->where('user_id', $userId);
                    }
                ])->get();


            if (!$stampCards or $stampCards->isEmpty()) {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => ['There are no StampCards']
                    ],
                    404
                );
            }
            return response()->json(
                [
                    'status' => 'success',
                    'data' => [
                        $stampCards
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

    public function getStampCardByIdAsVisitor($stampCardId) //Used
    {
        try {
            $userId = auth()->user()->id;
            $stampCard = StampCard::whereHas('visits', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })->with([
                    'business' => function ($query) {
                        $query->select('id', 'name', 'logo_path', 'segment_id');
                    },
                    'business.segment'
                ])->with([
                    'visits' => function ($query) use ($userId) {
                        $query->where('user_id', $userId);
                    }
                ])->withCount([
                    'visits' => function ($query) use ($userId) {
                        $query->where('user_id', $userId);
                    }
                ])
                ->with([
                    'users' => function ($query) use ($userId) {
                        $query->where('user_id', $userId);
                    }
                ])
                ->find($stampCardId);

            if (!$stampCard) {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => ['Resource not found']
                    ],
                    404
                );
            }

            $userStampCard = $stampCard->users->first()->pivot;

            // Add user_stamp_card to the stampCard object
            $stampCard->user_stamp_card = $userStampCard;

            return response()->json(
                [
                    'status' => 'success',
                    'data' => [
                        $stampCard
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

    public function getStampCardByIdAsCurrentCompany($stampCardId)
    {
        try {
            //Get the StampCard by stampCardId, the business_id of the StampCard must belong to the current business collection of the user
            $businessesIds = auth()->user()->businesses->pluck('id');
            $stampCard = StampCard::whereIn('business_id', $businessesIds)
                ->withCount('visits')
                ->with([
                    'business' => function ($query) {
                        $query->select('id', 'name', 'logo_path');
                    }
                ])
                ->find($stampCardId);

            if (!$stampCard) {
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
                        $stampCard
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

    public function getAllStampCardsAsCurrentCompany()
    {
        try {
            $businessesIds = auth()->user()->businesses->pluck('id');
            $stampCards = StampCard::whereIn('business_id', $businessesIds)->with([
                'business' => function ($query) {
                    $query->select('id', 'name');
                }
            ])->get();
            return response()->json(
                [
                    'status' => 'success',
                    'data' => [
                        $stampCards
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

    public function publishStampCard($id)
    {
        //validar que estas suscrito
        try {
            if (!auth()->user()->subscribed()) {
                throw new Exception("Necesitas una suscripcion para realizar esta accion");
            }
            //obtener stamps activas y limite que el user puede activar
            $businessesIds = auth()->user()->businesses->pluck('id');
            $activeStamps = StampCard::whereIn('business_id', $businessesIds)
                ->where('is_active', 1)
                ->get();
            $userStampsLimit = auth()->user()->account_details->stamp_cards_limit;
            // verificar si tiene cards activas
            if ($activeStamps->isNotEmpty()) {
                //si tiene verificar que no haya pasado del limite o este en el limite
                if ($userStampsLimit <= count($activeStamps)) {
                    throw new Exception("No puedes publicar mas stamps", 1);
                }
            } else {
                //si no tiene cards activas verificar que su limite no sea 0
                if ($userStampsLimit == 0) {
                    throw new Exception("No puedes publicar stamps", 1);
                }
            }
            // encontrar stamp y validar que pertence a un negocio activo
            $stampCard = StampCard::whereIn('business_id', $businessesIds)->find($id);
            if (!$stampCard->business->is_active) {
                throw new Exception("No puedes publicar esta stamp, publica el negocio al que pertenece antes", 1);

            }
            //publicar stampcard
            $stampCard->is_active = 1;
            $stampCard->save();
            return response()->json(
                [
                    'status' => 'success',
                    'message' => ['Stampcard publicada con exito']
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
    public function unpublishStampCard($id)
    {
        // validar que el usuario es Owner
        if (!auth()->user()->hasRole('Owner')) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => ['Unauthorized Role']
                ],
                401
            );
        }
        try {
            // validar que el usuario tiene el stamp que quiere despublicar
            $businessesIds = auth()->user()->businesses->pluck('id');
            $stampCard = StampCard::whereIn('business_id', $businessesIds)->find($id);
            if (!$stampCard) {
                throw new Exception("no se encontro la stampcard requerida",1);
            }
            // desactivar el stamp
            $stampCard->is_active = 0;
            $stampCard->save();
            return response()->json([
                'status' => 'success',
                'message' => ['la stampcard ya no se encuentra publica']
            ],200);
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

    private function saveIcon($stamp_icon)
    {
        Storage::disk('public')->put('business/images/icons/', $stamp_icon);
    }
}
