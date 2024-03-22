<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BusinessController extends Controller
{
    public function getAll(){
        return ('getAll');
    }

    public function getById($hashedId){
        return ('getById');
    }

    public function getAllByUser($id){
        return ('getAllByUser');
    }

    public function store(Request $request){
        return ('store');
    }

    public function update(Request $request){
        return ('update');
    }

    public function delete(Request $request){
        return ('delete');
    }
}
