<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BusinessController extends Controller
{
    public function getAll(){
        return ('aqui van todos los negocios');
    }
}
