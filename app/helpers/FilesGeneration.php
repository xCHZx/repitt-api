<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;



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

    public function generateFlyer($repittCode)
    {
        
        $manager = new ImageManager(Driver::class);
        $templatePath = resource_path('images/templates/flyer.jpg');
        $qrPath = public_path('storage/business/images/qr/' . 'repittCode=' . $repittCode . '.png');

        $template = $manager->read($templatePath);
        $qr = $manager->read($qrPath);
        $qr->resize(550, 550);

        $template->place($qr, 'center', 0, -180);

        $template->save(public_path('storage/business/images/flyer/' . 'repittCode=' . $repittCode . '.png'));

        $flyerPath = asset('storage/business/images/flyer/' . 'repittCode=' . $repittCode . '.png');

        return $flyerPath;
    }
}