<?php

namespace App\Http\Controllers;

use App\Models\UserStampCard;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserStampCardController extends Controller
{
    protected $messages = [
        'required' => 'The :attribute field is required.',
        'integer' => 'The :attribute field must be an integer.',
    ];

    public function getAllUserStampCardsByCurrentVisitor()
    {
        try {
            $userId = auth()->user()->id;

            $stampCards = UserStampCard::where('user_id', $userId)
                ->with([
                    'stamp_card' => function ($query) {
                        $query->with([
                            'business' => function ($query) {
                                $query->select('id', 'name', 'logo_path', 'segment_id');
                            },
                            'business.segment'
                        ]);
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

    public function getUserStampCardByIdAsVisitor($userStampCardId) //Used
    {
        try {

            $userId = auth()->user()->id;

            $stampCard = UserStampCard::where('id', $userStampCardId)
                ->with([
                    'stamp_card' => function ($query) {
                        $query->with([
                            'business' => function ($query) {
                                $query->select('id', 'name', 'logo_path', 'segment_id');
                            },
                            'business.segment'
                        ]);
                    }
                ])
                ->with([
                    'visits' => function ($query) {
                        $query->where('user_id', auth()->user()->id)
                            ->orderBy('created_at', 'desc');
                    }
                ])
                ->first();

                return response()->json(
                    [
                        'status' => 'success',
                        'data' => [
                            $stampCard
                        ]
                    ],
                    200
                );

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

    public function getUserStampCardByIdAsCurrentCompany($id){
        try{
            $userStampCard = UserStampCard::where('id', $id)
                            ->with([
                                'stamp_card' => function ($query) {
                                    $query->with([
                                        'business' => function ($query) {
                                            $query->select('id', 'name', 'logo_path', 'segment_id');
                                        },
                                        'business.segment'
                                    ]);
                                }
                            ])
                            ->with([
                                'user' => function ($query) {
                                    $query->select('id', 'first_name','last_name', 'email', 'phone');
                                }
                            ])
                            ->with([
                                'visits' => function ($query) {
                                    $query->where('user_id', auth()->user()->id);
                                }
                            ])
                            ->first();

            $stampCardIds = auth()->user()->stamp_cards->pluck('id');

            // if(!$userStampCard || !in_array($userStampCard->stamp_card_id, $stampCardIds->toArray())){
            if(!$userStampCard ){
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => ['User stamp card not found']
                    ],
                    404
                );
            }

            return response()->json(
                [
                    'status' => 'success',
                    'data' => $userStampCard
                ],
                200
            );

        }catch(Exception $e){
            return response()->json(
                [
                    'status' => 'error',
                    'message' => [$e->getMessage()]
                ],
                403
            );
        }
    }

    public function redeemReward(Request $request){
        try {

            $rules = [
                'user_stamp_card_id' => 'required|integer'
            ];

            $validator = Validator::make($request->all(), $rules,$this->messages);
            if ($validator->fails()) {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => $validator->errors()->all()
                    ],
                    400
                );
            }

            $userStampCardId = $request->user_stamp_card_id;

            $userStampCard = UserStampCard::find($userStampCardId);
            if (!$userStampCard) {
                throw new Exception("Resource not found", 1);
            }

            if ($userStampCard->is_completed == 0) {
                throw new Exception("StampCard not completed", 1);
            }

            if ($userStampCard->is_reward_redeemed) {
                throw new Exception("Reward already redeemed", 1);
            }
            $userStampCard->is_reward_redeemed = 1;
            $userStampCard->is_active = 0;
            $userStampCard->save();
            return response()->json(
                [
                    'status' => 'success',
                    'message' => ['Reward redeemed successfully'],
                    'data' => [
                        $userStampCard
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
                403
            );
        }

    }
}
