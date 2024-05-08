<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class CheckoutController extends Controller
{

    public function checkout()
    {
        $customer = auth()->user()->stripe_id;
        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
        $YOUR_DOMAIN = 'http://localhost:4242';

        $checkout_session = \Stripe\Checkout\Session::create([
            'customer' => $customer,
            'line_items' => [[
              'price' => env('PRICE_ID'),
              'quantity' => 1,
            ]],
            'mode' => 'subscription',
            'success_url' => $YOUR_DOMAIN . '/success.html',
            'cancel_url' => $YOUR_DOMAIN . '/cancel.html',
        ]);

        $response = response()->json($checkout_session->url);
        $response->header('content-type','aplication/json');
        return $response;
    }

}
