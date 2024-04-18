<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Validator::extend('base64_image_size', function ($attribute, $value, $parameters, $validator) {

        //     // Decode the image
        //     $decodedImage = base64_decode($value);
          
        //     // Get image size in kilobytes 
        //     $imageSize = strlen($decodedImage) / 1024;
          
        //     // Check if image is below max size
        //     return $imageSize <= $parameters[0]; 
          
        //   });

        // Validator::replacer('base64_image_size', function ($message, $attribute, $rule, $parameters) {
        //     return str_replace([':attribute', ':max'], [$attribute, $parameters[0]], $message);
        // });


    }
}
