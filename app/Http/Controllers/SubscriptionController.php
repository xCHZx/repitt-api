<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Laravel\Cashier\Subscription;

class SubscriptionController extends Controller
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

    public function store($userId,$subscriptionId)
    {
        try {
             // Crea una nueva suscripción en la base de datos
         Subscription::create([
            'user_id' => $userId,
            'stripe_id' => $subscriptionId,
            'type' => 'default',
            'stripe_status' => 'active',
            'quantity' => 1
            // no se como llenar los demas jaja
        ]);
        } catch (Exception $e) {
            return $e;
        }

    }

}
