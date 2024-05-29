<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Laravel\Cashier\Subscription;
use Stripe\Subscription as StripeSubscription;
use Validator;

class SubscriptionController extends Controller
{

    public function checkout(Request $request)
    {
        $prices = [
            'mensual' => env('PRICE_ID_MONTLY'),
            'anual' => env('PRICE_ID_YEARLY')
        ];
        $customer = auth()->user()->stripe_id;
        //$email = auth()->user()->email;
        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
        $YOUR_DOMAIN = env('FRONT_URL');
        // verificar si es la primera subscripcion del usuario
        $subscriptions = auth()->user()->subscriptions();
        if($subscriptions->count() > 0)
        {
            $checkout_session = \Stripe\Checkout\Session::create([
                'customer' => $customer,
                'line_items' => [
                    [
                        'price' => $prices[$request->price],
                        'quantity' => 1,
                    ]
                ],
                'mode' => 'subscription',
                'success_url' => $YOUR_DOMAIN . '/visitante/planes/gracias',
                'cancel_url' => $YOUR_DOMAIN . '/',
                'allow_promotion_codes' => true,
            ]);
        }
        else
        {
            $checkout_session = \Stripe\Checkout\Session::create([
                'customer' => $customer,
                'line_items' => [
                    [
                        'price' => $prices[$request->price],
                        'quantity' => 1,
                    ]
                ],
                'mode' => 'subscription',
                'success_url' => $YOUR_DOMAIN . '/empresa/planes/gracias',
                'cancel_url' => $YOUR_DOMAIN . '/',
                'allow_promotion_codes' => true,
                'subscription_data' => [
                    'trial_settings' => ['end_behavior' => ['missing_payment_method' => 'pause']],
                    'trial_period_days' => 15
                ],
                'payment_method_collection' => 'if_required'
            ]);
        }

        $response = response()->json($checkout_session->url);
        $response->header('content-type', 'aplication/json');
        return response()->json([
            'url' => $checkout_session->url
        ]);
    }


    public function store($user, $type, $stripeId, $stripeStatus, $stripePrice, $quantity, $trialEndsAt)
    {
        try {
            $subscription = $user->subscriptions()->create([
                'type' => $type,
                'stripe_id' => $stripeId,
                'stripe_status' => $stripeStatus,
                'stripe_price' => $stripePrice,
                'quantity' => $quantity,
                'trial_ends_at' => $trialEndsAt,
                'ends_at' => null,
            ]);
            return $subscription;
        } catch (Exception $e) {
            return $e;
        }

    }

    public function storeItems($subscription, $data)
    {
        try {
            foreach ($data['items']['data'] as $item) {
                $subscription->items()->create([
                    'stripe_id' => $item['id'],
                    'stripe_product' => $item['price']['product'],
                    'stripe_price' => $item['price']['id'],
                    'quantity' => $item['quantity'] ?? null,
                ]);
            }
        } catch (Exception $e) {
            return $e;
        }

    }

    public function cancellSubscription($subscription)
    {
        $subscription->stripe_status = StripeSubscription::STATUS_CANCELED;
        $subscription->ends_at = Carbon::now();
        $subscription->save();

    }


}
