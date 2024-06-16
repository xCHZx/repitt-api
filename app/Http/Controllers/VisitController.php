<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\StampCard;
use App\Models\User;
use App\Models\UserStampCard;
use App\Models\Visit;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Helpers\DataGeneration;
use App\Helpers\FilesGeneration;

class VisitController extends Controller
{
    protected $messages = [
        'stamp_card_id.required' => 'El campo de stamp_card_id es requerido',
        'stamp_card_id.integer' => 'El campo de stamp_card_id debe contener un numero entero',
        'user_reppitt_code.required' => 'El repitt code del usuario es requerido',
        'user_repitt_code.string' => 'El reppit code del usuario debe ser texto'
    ];
    public function registerVisitAsCompany(Request $request)
    {
        $rules = [
            'stamp_card_id' => 'required|integer',
            'user_repitt_code' => 'required|string',
        ];

        $validator = Validator::make($request->all(), $rules,$this->messages);
        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => $validator->errors()->all()
                ], 400
            );
        }

        try{

            $user = User::where('repitt_code', $request->user_repitt_code)->first();
            if (!$user){
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => ['El usuario no existe']
                    ], 400
                );
            }

            //Check if the stampCard exists
            $stampCard = StampCard::find($request->stamp_card_id);
            if(!$stampCard->is_active)
            {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => ['Esta tarjeta no esta activa o no existe']
                    ], 400
                );
            }

            //Check if the stampCard business is the same as the user's business
            $userBusinessesIds = auth()->user()->businesses->where('is_active',1)->pluck('id')->toArray();
            if (!in_array($stampCard->business_id, $userBusinessesIds)) {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => ['El negocio no existe o no se encuentra activo']
                    ], 400
                );
            }

            //Get the user's visits for the stamp card
            $visits = $user->visits->where('visitable_id', $request->stamp_card_id)->where('visitable_type', 'App\Models\StampCard');
            if (!$visits or $visits->isEmpty()){

                 // It's the first visit, create a UserStampCard
                $repittCode = app(DataGeneration::class)->generateRepittCode(12, 4);
                $userStampCard = new UserStampCard();
                $userStampCard->user_id = $user->id;
                $userStampCard->stamp_card_id = $request->stamp_card_id;
                $userStampCard->visits_count = 1;
                $userStampCard->is_active = true;
                $userStampCard->is_reward_redeemed = false;
                $userStampCard->userstampcard_repitt_code = $repittCode;
                $userStampCard->save();

                $userStampCard->qr_path = app(FilesGeneration::class)->generateQr($userStampCard->userstampcard_repitt_code,'userstampcard');
                $userStampCard->save();

                //Create a visit
                $visit = new Visit();
                $visit->user()->associate($user);
                $visit->user_stamp_card()->associate($userStampCard->id);
                $stampCard->visits()->save($visit); //Here sabes the visitable_id and visitable_type
                $visit->save();

                return response()->json(
                    [
                        'status' => 'success',
                        'data' => [
                            'visit' => $visit,
                        ]
                    ], 201
                );

            }else{
                //If is not the first visit
                if ($this->isPastRequiredHours($visits, $stampCard->required_hours)){
                // if (1 == 1){

                    //Count the UserStampCard instances of te StampCard
                    $userStampCardCount = UserStampCard::where('stamp_card_id', $request->stamp_card_id)
                                        ->where('is_active', 0)
                                        ->count();
                    $validationStampCard = StampCard::find($request->stamp_card_id);

                    if($userStampCardCount >= $validationStampCard->allowed_repeats){
                        return response()->json(
                            [
                                'status' => 'error',
                                'message' => ['El usuario ya ha completado el limite de tarjetas']
                            ], 400
                        );
                    }

                    $userStampCard = UserStampCard::where('user_id', $user->id)
                                        ->where('stamp_card_id', $request->stamp_card_id)
                                        ->where('is_active', 1)
                                        ->with('stamp_card')
                                        ->with('visits')
                                        ->first();

                    if(!$userStampCard){
                        //Create a new UserStampCard
                        $repittCode = app(DataGeneration::class)->generateRepittCode(12, 4);
                        $userStampCard = new UserStampCard();
                        $userStampCard->user_id = $user->id;
                        $userStampCard->stamp_card_id = $request->stamp_card_id;
                        $userStampCard->visits_count = 0;  //Se crea en cero porque abajo se asigna en el flujo principal
                        $userStampCard->is_active = true;
                        $userStampCard->is_reward_redeemed = false;
                        $userStampCard->userstampcard_repitt_code = $repittCode;
                        $userStampCard->save();

                        $userStampCard->qr_path = app(FilesGeneration::class)->generateQr($userStampCard->userstampcard_repitt_code,'userstampcard');
                        $userStampCard->save();


                        //Create a visit
                        $visit = new Visit();
                        $visit->user()->associate($user);
                        $visit->user_stamp_card()->associate($userStampCard->id);
                        $stampCard->visits()->save($visit); //Here sabes the visitable_id and visitable_type
                        $visit->save();
                    }


                    $completedUserStampCardCount = UserStampCard::where('stamp_card_id', $request->stamp_card_id)
                                        ->where('is_active', 1)
                                        ->where('is_completed', 1)
                                        ->count();

                    //Validate if the user has more than one active userStampCard
                    if ($completedUserStampCardCount >= 1){
                        return response()->json(
                            [
                                'status' => 'error',
                                'message' => ['Existen tarjetas activas con recompensas sin reclamar']
                            ], 400
                        );
                    }

                    if($userStampCard->visits_count >= $userStampCard->stamp_card->required_stamps){

                        //Create a new UserStampCard
                        $repittCode = app(DataGeneration::class)->generateRepittCode(12, 4);
                        $userStampCard = new UserStampCard();
                        $userStampCard->user_id = $user->id;
                        $userStampCard->stamp_card_id = $request->stamp_card_id;
                        $userStampCard->visits_count = 0;  //Se crea en cero porque abajo se asigna en el flujo principal
                        $userStampCard->is_active = true;
                        $userStampCard->is_reward_redeemed = false;
                        $userStampCard->userstampcard_repitt_code = $repittCode;

                        $userStampCard->save();

                        $userStampCard->qr_path = app(FilesGeneration::class)->generateQr($userStampCard->userstampcard_repitt_code,'userstampcard');
                        $userStampCard->save();
                    }

                    //If normal conditions are met, increment the visitsCount
                    $userStampCard->visits_count += 1;
                    //Check if this visit is the last one, if so, set the user_stamp_card is_completed to true
                    if ($userStampCard->visits_count == $userStampCard->stamp_card->required_stamps){
                        $userStampCard->is_completed = true;
                        $userStampCard->completed_at = Carbon::now();
                    }
                    $userStampCard->save();

                    //Create a visit
                    $visit = new Visit();
                    $visit->user()->associate($user);
                    $visit->user_stamp_card()->associate($userStampCard->id);
                    $stampCard->visits()->save($visit); //Here sabes the visitable_id and visitable_type
                    $visit->save();

                    return response()->json(
                        [
                            'status' => 'success',
                            'data' => [
                                'visit' => $visit,
                            ]
                        ], 201
                    );

                }else{
                    return response()->json(
                        [
                            'status' => 'error',
                            'message' => ['Solo puede visitar una vez cada '.$stampCard->required_hours.' horas']
                        ], 400
                    );
                }
            }

        }catch(Exception $e){
            return $e;
            return response()->json(
                [
                    'status' => 'error',
                    'message' => [$e->getMessage()]
                ], 400
            );
        }
    }


    public function getAllVisitsAsCurrentVisitor() //used
    {
        try{
            $userId = auth()->user()->id;
            $user = User::find($userId);
            $visits = $user->visits;
            // Load the business and stampcard information for each visit
            $visits->load('stamp_card.business');
            if (!$visits or $visits->isEmpty()){
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => ['There are no visits']
                    ], 404
                );
            }
            return response()->json(
                [
                    'status' => 'success',
                    'data' => [
                        'visits' => $visits,
                        'visits_count' => $visits->count()
                    ]
                ], 200
            );}
        catch(Exception $e){
            return response()->json(
                [
                    'status' => 'error',
                    'message' => [$e->getMessage()]
                ], 400
            );
        }
    }


    public function getAllVisitsByStampCardIdAsCurrentCompany($id)
    {
        try{
            $stampCard = StampCard::find($id);
            if (!$stampCard){
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => ['Resource not found']
                    ], 404
                );
            }

            //Check if the stampCard business exists in the user's businesses
            $userBusinessesIds = auth()->user()->businesses->pluck('id')->toArray();
            if (!in_array($stampCard->business_id, $userBusinessesIds)) {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => ['Error retrieving visits for this stamp card. You can only view visits for businesses in your company']
                    ], 400
                );
            }


            $stampCard->load([
                'visits' => function ($query) {
                    $query->orderBy('created_at', 'desc');
                },
                'visits.user:id,first_name,last_name,repitt_code',
                'visits.stamp_card:id,name',
                'business:id,name,logo_path'
            ]);

            // Add visits count
            $stampCard->visits_count = $stampCard->visits->count();
            return response()->json(
                [
                    'status' => 'success',
                    'data' => [
                        $stampCard,
                    ]
                ], 200
            );
        }catch(Exception $e){
            return response()->json(
                [
                    'status' => 'error',
                    'message' => [$e->getMessage()]
                ], 400
            );
        }
    }

    public function getAllVisitsByBusinessIdAsCurrentCompany($id)
    {
        try{
            $business = Business::find($id);
            if (!$business){
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => ['Resource not found']
                    ], 404
                );
            }

            //Check if the business exists in the user's businesses
            $userBusinessesIds = auth()->user()->businesses->pluck('id')->toArray();
            if (!in_array($business->id, $userBusinessesIds)) {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => ['You can only view visits for businesses in your company']
                    ], 400
                );
            }

            // //Add all the visits for the business
            // $business->load('visits');

            // //Add the user information for each visit
            // $business->load('visits.user:id,first_name,last_name,repitt_code', 'visits.stamp_card:id,name');

            //Add the visits count for the business
            $business->visits_count = $business->visits->count();

            $business->load([
                'visits' => function ($query) {
                    $query->orderBy('created_at', 'desc');
                },
                'visits.user:id,first_name,last_name,repitt_code',
                'visits.stamp_card:id,name',
            ]);



            return response()->json(
                [
                    'status' => 'success',
                    'data' => [
                        $business,
                    ]
                ], 200
            );

        }catch(Exception $e){
            return response()->json(
                [
                    'status' => 'error',
                    'message' => [$e->getMessage()]
                ], 400
            );
        }
    }

    private function isPastRequiredHours($visits, $requiredHours){
        $now = Carbon::now();
        $lastVisit = $visits->last();
        $diffMins = $now->diffInMinutes($lastVisit->created_at);
        $diffHours = $diffMins / 60;

        $diffHours = abs($diffHours);

        if ($diffHours >= $requiredHours ){
            return true;
        }
        return false;
    }
}
