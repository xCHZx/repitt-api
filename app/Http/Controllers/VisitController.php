<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\StampCard;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VisitController extends Controller
{
    public function registerVisitAsCompany(Request $request)
    {
        $rules = [
            'stamp_card_id' => 'required|integer',
            'user_repitt_code' => 'required|string',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => $validator->errors()->all()
                ], 400
            );
        }

        try{



            $userBusinessesIds = auth()->user()->businesses->where('is_active',1)->pluck('id')->toArray();

            // $user = User::find($request->user_id);
            $user = User::where('repitt_code', $request->user_repitt_code)->first();


            //get the user's visits for the stamp card
            $visits = $user->visits->where('visitable_id', $request->stamp_card_id)->where('visitable_type', 'App\Models\StampCard');


            $stampCard = StampCard::find($request->stamp_card_id);
            if(!$stampCard->is_active)
            {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => ['Esta tarjeta no esta activa']
                    ], 400
                );  
            }
            //Check if the stampCard business is the same as the user's business
            if (!in_array($stampCard->business_id, $userBusinessesIds)) {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => ['El negocio no existe o no se encuentra activo']
                    ], 400
                );
            }

            if (!$visits or $visits->isEmpty()){

                // $user = User::find($request->user_id);
                $user = User::where('repitt_code', $request->user_repitt_code)->first();
                $visit = new Visit();
                $visit->user()->associate($user);
                $stampCard->visits()->save($visit);

                // Retrieve the StampCard instance associated with the user
                $userStampCard = $user->stamp_cards()->where('stamp_card_id', $request->stamp_card_id)->first();

                // Check if the user has the stamp card
                if (!$userStampCard) {
                    // It's the first visit, create a user_stamp_card
                    $user->stamp_cards()->attach($request->stamp_card_id, ['visits_count' => 1, 'is_active' => true, 'is_reward_redeemed' => false]);
                } else {
                    // It's not the first visit, increment the visitsCount
                    $userStampCard->pivot->visits_count += 1;
                    $userStampCard->pivot->save();
                }

                return response()->json(
                    [
                        'status' => 'success',
                        'data' => [
                            'visit' => $visit,
                            'user_stamp_card' => $userStampCard,
                        ]
                    ], 201
                );
            }else{
                if ($this->isPast12Hours($visits)){

                    // $user = User::find($request->user_id);
                    $user = User::where('repitt_code', $request->user_repitt_code)->first();
                    $visit = new Visit();
                    $visit->user()->associate($user);
                    $stampCard->visits()->save($visit);

                    // Retrieve the StampCard instance associated with the user
                    $userStampCard = $user->stamp_cards()->where('stamp_card_id', $request->stamp_card_id)->first();

                    // Check if the user has the stamp card
                    if (!$userStampCard) {
                        // It's the first visit, create a user_stamp_card
                        $user->stamp_cards()->attach($request->stamp_card_id, ['visits_count' => 1, 'is_active' => true, 'is_reward_redeemed' => false]);
                    } else {
                        // It's not the first visit, increment the visitsCount
                        $userStampCard->pivot->visits_count += 1;
                        $userStampCard->pivot->save();
                    }
                    return response()->json(
                        [
                            'status' => 'success',
                            'data' => [
                                'visit' => $visit,
                                'user_stamp_card' => $userStampCard,
                            ]
                        ], 201
                    );
                }else{
                    return response()->json(
                        [
                            'status' => 'error',
                            'message' => ['You can only visit once every 12 hours']
                        ], 400
                    );
                }
            }

        }catch(Exception $e){
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

            $stampCard->visits;

            //Add the business relation to the stampCard
            $stampCard->load('business:id,name,logo_path');

            // Load the user information for each visit
            $stampCard->load('visits.user:id,first_name,last_name,repitt_code', 'visits.stamp_card:id,name');

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

            //Add all the visits for the business
            $business->load('visits');

            //Add the user information for each visit
            $business->load('visits.user:id,first_name,last_name,repitt_code', 'visits.stamp_card:id,name');

            //Add the stamp card information for each visit


            //Add the visits count for the business
            $business->visits_count = $business->visits->count();

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

    private function isPast12Hours($visits){
        $now = Carbon::now();
        $lastVisit = $visits->last();
        $diffMins = $now->diffInMinutes($lastVisit->created_at);
        $diffHours = $diffMins / 60;

        $diffHours = abs($diffHours);

        if ($diffHours >= 12 ){
            return true;
        }
        return false;
    }
}
