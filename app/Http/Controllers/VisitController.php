<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\StampCard;
use App\Models\User;
use App\Models\Visit;
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
            $stampCard = StampCard::find($request->stamp_card_id);
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

//     public function getById($id)
//     {


//         $visit = Visit::find(1);
//         $user = $visit->user;
//     }
}
