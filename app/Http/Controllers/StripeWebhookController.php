<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Laravel\Cashier\Cashier;
use Illuminate\Support\Carbon;
use Laravel\Cashier\Subscription;
use Illuminate\Routing\Controller;
use App\Http\Controllers\SubscriptionController;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Cashier\Http\Middleware\VerifyWebhookSignature;
use Stripe\Stripe;
use Stripe\Subscription as StripeSubscription;

class StripeWebhookController extends Controller
{
    /**
     * Create a new webhook controller instance.
     *
     * @return void
     */
    /*
    *Custormer.suscription.updated*
    -customer.subscription.created
    *customer.subscription.deleted*
    *customer.updated*
    *customer.deleted*
    -payment_method.automatically_updated
    -invoice.payment_action_required
    *invoice.payment_succeded*
    */

    public function __construct()
    {
        if (env('STRIPE_WEBHOOK_SECRET')) {
            $this->middleware(VerifyWebhookSignature::class);
        }
    }

    /**
     * Handle a Stripe webhook call.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleWebhook(Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        $method = 'handle'.Str::studly(str_replace('.', '_', $payload['type']));

        if (method_exists($this, $method)) {
            return $this->{$method}($payload);
        }

        return $this->missingMethod();
    }

    /**
     * Handle customer subscription updated.
     *
     * @param  array $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleCustomerSubscriptionCreated(array $payload)
    {
        $user = app(UserController::class)->getUserByStripeId($payload['data']['object']['customer']);
        if ($user) {
            $data = $payload['data']['object'];

            if (! $user->subscriptions->contains('stripe_id', $data['id'])) {
                if (isset($data['trial_end'])) {
                    $trialEndsAt = Carbon::createFromTimestamp($data['trial_end']);
                } else {
                    $trialEndsAt = null;
                }

                $firstItem = $data['items']['data'][0];
                $isSinglePrice = count($data['items']['data']) === 1;

                $type = $data['metadata']['type'] ?? $data['metadata']['name'] ?? $this->newSubscriptionType($payload);
                $stripeId = $data['id'];
                $stripeStatus = $data['status'];
                $stripePrice = $isSinglePrice ? $firstItem['price']['id'] : null;
                $quantity = $isSinglePrice && isset($firstItem['quantity']) ? $firstItem['quantity'] : null;
                $subscription = app(SubscriptionController::class)->store(
                    $user,$type,$stripeId,$stripeStatus,$stripePrice,$quantity,$trialEndsAt
                ); 
                app(SubscriptionController::class)->storeItems($subscription,$data);
            }

            // Terminate the billable's generic trial if it exists...
            if (! is_null($user->trial_ends_at)) {
                $user->update(['trial_ends_at' => null]);
            }          
        }
        return new Response('Webhook Handled', 200);
    }
    protected function handleCustomerSubscriptionUpdated(array $payload)
    {
        if ($user = app(UserController::class)->getUserByStripeId($payload['data']['object']['customer'])) {
            $data = $payload['data']['object'];

            $subscription = $user->subscriptions()->firstOrNew(['stripe_id' => $data['id']]);

            if (
                isset($data['status']) &&
                $data['status'] === StripeSubscription::STATUS_INCOMPLETE_EXPIRED
            ) {
                $subscription->items()->delete();
                $subscription->delete();

                return new Response('Webhook Handled', 200);
            }

            $subscription->type = $subscription->type ?? $data['metadata']['type'] ?? $data['metadata']['name'] ?? $this->newSubscriptionType($payload);

            $firstItem = $data['items']['data'][0];
            $isSinglePrice = count($data['items']['data']) === 1;

            // Price...
            $subscription->stripe_price = $isSinglePrice ? $firstItem['price']['id'] : null;

            // Quantity...
            $subscription->quantity = $isSinglePrice && isset($firstItem['quantity']) ? $firstItem['quantity'] : null;

            // Trial ending date...
            if (isset($data['trial_end'])) {
                $trialEnd = Carbon::createFromTimestamp($data['trial_end']);

                if (! $subscription->trial_ends_at || $subscription->trial_ends_at->ne($trialEnd)) {
                    $subscription->trial_ends_at = $trialEnd;
                }
            }

            // Cancellation date...
            if ($data['cancel_at_period_end'] ?? false) {
                $subscription->ends_at = $subscription->onTrial()
                    ? $subscription->trial_ends_at
                    : Carbon::createFromTimestamp($data['current_period_end']);
            } elseif (isset($data['cancel_at']) || isset($data['canceled_at'])) {
                $subscription->ends_at = Carbon::createFromTimestamp($data['cancel_at'] ?? $data['canceled_at']);
            } else {
                $subscription->ends_at = null;
            }

            // Status...
            if (isset($data['status'])) {
                $subscription->stripe_status = $data['status'];
            }

            $subscription->save();

            // Update subscription items...
            if (isset($data['items'])) {
                $subscriptionItemIds = [];

                foreach ($data['items']['data'] as $item) {
                    $subscriptionItemIds[] = $item['id'];

                    $subscription->items()->updateOrCreate([
                        'stripe_id' => $item['id'],
                    ], [
                        'stripe_product' => $item['price']['product'],
                        'stripe_price' => $item['price']['id'],
                        'quantity' => $item['quantity'] ?? null,
                    ]);
                }

                // Delete items that aren't attached to the subscription anymore...
                $subscription->items()->whereNotIn('stripe_id', $subscriptionItemIds)->delete();
            }
        }
        return new Response('Webhook Handled', 200);
    }

    /**
     * Handle a cancelled customer from a Stripe subscription.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleCustomerSubscriptionDeleted(array $payload)
    {
        $user = app(UserController::class)->getUserByStripeId($payload['data']['object']['customer']);

        if ($user) {
            $user->subscriptions->filter(function ($subscription) use ($payload) {
                return $subscription->stripe_id === $payload['data']['object']['id'];
            })->each(function ($subscription) {
                $subscription->markAsCanceled();
            });
        }

        return new Response('Webhook Handled', 200);
    }

    /**
     * Handle customer updated.
     *
     * @param  array $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handlecustomerupdated(array $payload)
    {
        //$stripeId = $payload['data']['object']['id'];
        try {
            app(UserController::class)->updateFromStripe($payload);
            return new Response('Webhook Handled', 200);
        } catch (Exception $e) {
            return new Response($e->getMessage(),500);
        }
       
    }

    /**
     * Handle customer source deleted.
     *
     * @param  array $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleCustomerSourceDeleted(array $payload)
    {
        if ($user = $this->getUserByStripeId($payload['data']['object']['customer'])) {
            $user->updateCardFromStripe();
        }

        return new Response('Webhook Handled', 200);
    }

    /**
     * Handle deleted customer.
     *
     * @param  array $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleCustomerDeleted(array $payload)
    {
        $user = app(UserController::class)->getUserByStripeId($payload['data']['object']['id']);

        if ($user) {
            $user->subscriptions->each(function (Subscription $subscription) {
                // marcamos el status de la subscription como cancelada
                $subscription->skipTrial()->markAsCancelled();
            });

            $user->forceFill([
                'stripe_id' => null,
                'trial_ends_at' => null,
                'card_brand' => null,
                'card_last_four' => null,
            ])->save();
        }

        return new Response('Webhook Handled', 200);
    }

    /**
     * Get the billable entity instance by Stripe ID.
     *
     * @param  string  $stripeId
     * @return \Laravel\Cashier\Billable
     */
    // protected function getUserByStripeId($stripeId)
    // {
    //     $model = Cashier::stripeModel();

    //     return (new $model)->where('stripe_id', $stripeId)->first();
    // }

    /**
     * Handle calls to missing methods on the controller.
     *
     * @param  array  $parameters
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function missingMethod($parameters = [])
    {
        return new Response;
    }

    /**
     * Handle successfull payment.
     *
     * @param  array $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleInvoicePaymentSucceeded(array $payload)
    {
        // try {    
        // $user = app(UserController::class)->getUserByStripeId($payload['data']['object']['customer']);
        // if($user->subscribed())
        // {
        //     return response('Webhook Handled', 200);
        // }
        // $subscriptionId = $payload['data']['object']['id'];
        // app(SubscriptionController::class)->store($user->id,$subscriptionId);
        return response('Webhook Handled', 200);
        // } catch (Exception $e) {
        //     return response()->json([
        //         'status' => 'error',
        //         'error' => $e
        //     ],500);
        // }
    }

    protected function newSubscriptionType(array $payload)
    {
        return 'default';
    }


}