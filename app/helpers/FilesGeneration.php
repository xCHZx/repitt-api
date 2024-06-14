<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;


class FilesGeneration
{
    public function generateQr($repittCode,$model)
    {
        $content = $repittCode;
        if($model == "business")
        {
            $content = env('FRONT_URL') . '/visitante/negocios/' . $repittCode;
        }

        $qrCode = Http::get("http://api.qrserver.com/v1/create-qr-code/?data=".$content. "&size=200x200&margin=15&format=png");
        Storage::disk('public')->put($model.'/images/qr/' . 'repittCode=' . $repittCode . '.png', $qrCode);
        $qrPath = asset('storage/'.$model.'/images/qr/' . 'repittCode=' . $repittCode . '.png');

        return $qrPath;
    }
}