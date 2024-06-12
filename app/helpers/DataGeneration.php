<?php

namespace App\Helpers;
use Illuminate\Support\Str;

class DataGeneration
{
    public function generateRepittCode($lenght) :string 
    {

        $alphabet = 'abcdefghijklmnopqrstuvwxyz';
        $randomIndex = rand(0, strlen($alphabet) - 1);
        $randomCharacter = $alphabet[$randomIndex];  

        $repittCode = Str::random($lenght);
        $repittCode = strtolower(preg_replace('/[^a-zA-Z]/', $randomCharacter, $repittCode));
        $repittCode = implode('-', str_split($repittCode, $lenght / 3));
        return $repittCode;

    }
}
