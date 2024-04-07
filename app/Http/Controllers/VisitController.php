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
    public function store(Request $request)
    {
        $rules = [
            'stamp_card_id' => 'required|integer',
            'user_id' => 'required|integer',
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
            $user = User::find($request->user_id);

            //get the user's visits for the stamp card
            $visits = $user->visits->where('visitable_id', $request->stamp_card_id)->where('visitable_type', 'App\Models\StampCard');
            $stampCard = StampCard::find($request->stamp_card_id);

            if (!$visits or $visits->isEmpty()){

                $user = User::find($request->user_id);
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

                    $user = User::find($request->user_id);
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
                            'message' => 'You can only visit once every 12 hours',
                        ], 400
                    );
                }
            }

        }catch(Exception $e){
            return $e;
        }
    }

    public function getAllByStampCard()
    {
        $stampCard = StampCard::find(1);
        $visits = $stampCard->visits;
        return response()->json(
            [
                'status' => 'success',
                'data' => [
                    $visits
                ]
            ], 200
        );
    }
    public function getAllByCurrentUser()
    {
        try{
            $userId = auth()->user()->id;
            $user = User::find($userId);
            $visits = $user->visits;
            if (!$visits or $visits->isEmpty()){
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'Resource not found'
                    ], 404
                );
            }
            return response()->json(
                [
                    'status' => 'success',
                    'data' => [
                        $visits
                    ]
                ], 200
            );}
        catch(Exception $e){
            return $e;
        }
    }

    public function getByBusiness($id)
    {
        try{
            $business = Business::find($id);
            if (!$business){
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'Resource not found'
                    ], 404
                );
            }
            $visits = $business->visits;
            return response()->json(
                [
                    'status' => 'success',
                    'data' => [
                        $visits
                    ]
                ], 200
            );}
        catch(Exception $e){
            return $e;
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
