<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Tenant;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;

class StripeController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    // ── Create Checkout Session ──────────────────────────────────────────────

    public function checkout(Request $request)
    {
        $data = $request->validate([
            'plan_id'        => 'required|string|exists:mysql.plans,id',
            'billing_period' => 'required|in:monthly,annual',
        ]);

        $tenant = tenancy()->tenant;
        $plan   = Plan::findOrFail($data['plan_id']);

        $isAnnual = $data['billing_period'] === 'annual';
        $price    = $isAnnual ? $plan->annual_price : $plan->monthly_price;

        if (!$price || $price <= 0) {
            return $this->error(__('pos.invalid_plan_price'), 422);
        }

        $session = StripeSession::create([
            'payment_method_types' => ['card'],
            'mode'                 => 'payment',
            'line_items'           => [[
                'price_data' => [
                    'currency'     => config('services.stripe.currency', 'usd'),
                    'product_data' => [
                        'name'        => $plan->name . ' — ' . ($isAnnual ? '12 months' : '1 month'),
                        'description' => implode(', ', array_slice($plan->features ?? [], 0, 3)),
                    ],
                    'unit_amount'  => (int) round($price * 100),
                ],
                'quantity'   => 1,
            ]],
            'metadata' => [
                'tenant_id'      => $tenant->id,
                'plan_id'        => $plan->id,
                'billing_period' => $data['billing_period'],
                'months'         => $isAnnual ? 12 : 1,
            ],
            'success_url' => route('stripe.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => route('subscribe'),
            'customer_email' => auth()->user()?->email ?? null,
        ]);

        return response()->json(['success' => true, 'url' => $session->url]);
    }

    // ── Payment Success (redirect return) ───────────────────────────────────

    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');

        if (!$sessionId) {
            return redirect()->route('subscribe');
        }

        try {
            $session = StripeSession::retrieve([
                'id'     => $sessionId,
                'expand' => ['payment_intent'],
            ]);
        } catch (\Exception $e) {
            return redirect()->route('subscribe');
        }

        if ($session->payment_status === 'paid') {
            $this->activateSubscription(
                $session->metadata['tenant_id'],
                $session->metadata['plan_id'],
                (int) $session->metadata['months']
            );
        }

        return view('subscription.success', [
            'session' => $session,
            'tenant'  => tenancy()->tenant,
        ]);
    }

    // ── Stripe Webhook ───────────────────────────────────────────────────────

    public function webhook(Request $request)
    {
        $secret = config('services.stripe.webhook_secret');
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature failed', ['error' => $e->getMessage()]);
            return response('Invalid signature', 400);
        }

        match ($event->type) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($event->data->object),
            default                      => null,
        };

        return response('OK', 200);
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function handleCheckoutCompleted(object $session): void
    {
        if ($session->payment_status !== 'paid') return;

        $this->activateSubscription(
            $session->metadata->tenant_id,
            $session->metadata->plan_id,
            (int) $session->metadata->months
        );
    }

    private function activateSubscription(string $tenantId, string $planId, int $months): void
    {
        $tenant = Tenant::find($tenantId);
        if (!$tenant) return;

        $base = ($tenant->subscription_ends_at && $tenant->subscription_ends_at->isFuture())
            ? $tenant->subscription_ends_at
            : Carbon::now();

        $tenant->update([
            'plan'                => $planId,
            'subscription_status' => 'active',
            'subscription_ends_at'=> $base->addMonths($months),
            'is_active'           => true,
        ]);

        Log::info('Subscription activated', [
            'tenant_id' => $tenantId,
            'plan_id'   => $planId,
            'months'    => $months,
            'ends_at'   => $tenant->fresh()->subscription_ends_at,
        ]);
    }
}
