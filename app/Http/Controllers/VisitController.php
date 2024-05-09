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
    public function storeAsCompany(Request $request)
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

            $userBusinessesIds = auth()->user()->businesses->pluck('id')->toArray();

            // $user = User::find($request->user_id);
            $user = User::where('repitt_code', $request->user_repitt_code)->first();

            //get the user's visits for the stamp card
            $visits = $user->visits->where('visitable_id', $request->stamp_card_id)->where('visitable_type', 'App\Models\StampCard');


            $stampCard = StampCard::find($request->stamp_card_id);
            //Check if the stampCard business is the same as the user's business
            if (!in_array($stampCard->business_id, $userBusinessesIds)) {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => ['Visit unsuccesful']
                    ], 400
                );
            }

            if (!$visits or $visits->isEmpty()){

                // $user = User::find($request->user_id);
                $user = User::where('repitt_code', $request->user_repitt_code)->first();
                $visit = new Visit();
                $visit->user()->associate($user);
                $stampCard->visits()->save($visit);
                return response()->json(
                    [
                        'status' => 'success',
                        'data' => [
                            $visit
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
                    return response()->json(
                        [
                            'status' => 'success',
                            'data' => [
                                $visit
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


    public function getAllByCurrentVisitor() //used
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


    public function getAllByStampCardAsCurrentCompany($id)
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

    public function getAllByBusinessAsCurrentCompany($id)
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
