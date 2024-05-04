<?php

namespace App\Http\Controllers;

use App\Models\StampCard;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class StampCardController extends Controller
{
    public function storeAsCompany(Request $request)
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
            //'stamp_icon_string' => 'required|base64_image_size:500',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            // 'stamp_icon_path' => 'required|string',
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
                ], 400
            );
        }

        try {
            $stampCard = new StampCard();
            $stampCard->name = $request->name;
            $stampCard->description = $request->description;
            $stampCard->required_stamps = $request->required_stamps;
            //$stampCard->stamp_icon_path = $request->stamp_icon_path;
            $stampCard->start_date = $request->start_date;
            $stampCard->end_date = $request->end_date;
            $stampCard->stamp_icon_path = $request->stamp_icon_path;
            $stampCard->primary_color = $request->primary_color;
            $stampCard->business_id = $request->business_id;
            $stampCard->reward = $request->reward;
            if(!$request->stamp_icon_file)
            {
                $stampCard->stamp_icon_path = asset('storage/placeholders/icon-placeholder.png');
            }
            else
            {
                $file = $request->file('stamp_icon_file');
                $this->saveIcon($file);
                $stampCard->stamp_icon_path = asset('storage/business/images/icons/'.$file->hashName());

            }
            $stampCard->save();

            return response()->json(
                [
                    'status' => 'success',
                    'data' => [
                        $stampCard
                    ]
                ], 201
            );
        } catch (Exception $e) {
            return $e;
        }

    }

    public function getAllByCurrentVisitor() //Used
    {


        try{
            $userId = auth()->user()->id;

            // $stampCards = StampCard::whereHas('visits', function($query) use ($userId){
            //     $query->where('user_id', $userId);
            // })->with('business')->get();

            // $stampCards = $stampCards->map(function($stampCard) use ($userId) {
            //     $stampCard->load(['visits' => function($query) use ($userId) {
            //         $query->where('user_id', $userId);
            //     }]);
            //     return $stampCard;
            // });

            $stampCards = StampCard::whereHas('visits', function($query) use ($userId){
                $query->where('user_id', $userId);
            })->with(['business' => function($query) {
                $query->select('id', 'name', 'logo_path','segment_id');
            }, 'business.segment'])->with(['visits' => function($query) use ($userId) {
                $query->where('user_id', $userId);
            }])->withCount(['visits' => function($query) use ($userId) {
                $query->where('user_id', $userId);
            }])->get();


            if (! $stampCards or $stampCards->isEmpty()){
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
                        $stampCards
                    ]
                ],200
            );

        }catch(Exception $e){
            return $e;
        }
    }

    public function getByIdAsVisitor($stampCardId) //Used
    {
        try{
            $userId = auth()->user()->id;
            $stampCard = StampCard::whereHas('visits', function($query) use ($userId){
                $query->where('user_id', $userId);
            })->with(['business' => function($query) {
                $query->select('id', 'name', 'logo_path','segment_id');
            }, 'business.segment'])->with(['visits' => function($query) use ($userId) {
                $query->where('user_id', $userId);
            }])->withCount(['visits' => function($query) use ($userId) {
                $query->where('user_id', $userId);
            }])->find($stampCardId);

            if (! $stampCard){
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
                        $stampCard
                    ]
                ],200
            );

        }catch(Exception $e){
            return $e;
        }
    }

    public function getAllByCurrentCompany(){
        try{
            $businessesIds = auth()->user()->businesses->pluck('id');
            $stampCards = StampCard::whereIn('business_id', $businessesIds)->with(['business' => function($query) {
                $query->select('id', 'name');
            }])->get();
            return response()->json(
                [
                    'status' => 'success',
                    'data' => [
                        $stampCards
                    ]
                ],200
            );
        }
        catch(Exception $e){
            return $e;
        }
    }


    public function delete(Request $request, $id)
    {

    }

    private function saveIcon($stamp_icon)
    {
        
        Storage::disk('public')->put('business/images/icons/',$stamp_icon);
    }

}
