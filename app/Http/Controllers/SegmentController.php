<?php

namespace App\Http\Controllers;

use App\Models\Segment;
use Exception;
use Illuminate\Http\Request;

class SegmentController extends Controller
{
    public function getAllSegments(){
        try{
            $segments = Segment::select('id', 'name')->get();
            return response()->json(
                [
                    'status' => 'success',
                    'data' => [
                        $segments
                    ]
                ],200
            );

        }catch(Exception $e){
            return response()->json(
                [
                    'status' => 'error',
                    'message' => [$e->getMessage()]
                ],404
            );
        }
    }
}
