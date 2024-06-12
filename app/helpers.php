<?php

/*
Aqui van todas las funciones que se necesiten repetir varias veces en la app
favor de incluir un comentario para saber lo que hace la funcion, los parametros que espera
y lo que retorna */

// funcion para generar el repitt code de el usuario
// espera un $lenght que es un numero con la cantida de caracteres que tendra el repit code
// retorna una cadena de caracteres con el repitcode generado
if (!function_exists('generateRepittCode')){
    function generateRepittCode($lenght) :string {
        $alphabet = 'abcdefghijklmnopqrstuvwxyz';
        $randomIndex = rand(0, strlen($alphabet) - 1);
        $randomCharacter = $alphabet[$randomIndex];  

        $repittCode = Str::random($lenght);
        $repittCode = strtolower(preg_replace('/[^a-zA-Z]/', $randomCharacter, $repittCode));
        $repittCode = implode('-', str_split($repittCode, $lenght / 3));
        return $repittCode;
    }
}