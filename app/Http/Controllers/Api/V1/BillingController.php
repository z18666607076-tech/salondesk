<?php

namespace App\Http\Controllers\Api\V1;

use App\Billing\SubscriptionManager;
use App\Enums\Plan;
use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\SubscribeRequest;
use App\Models\Tenant;
use App\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class BillingController extends Controller
{
    /**
     * Show the salon's current plan and Cashier subscription state.
     */
    public function show(TenantContext $tenants): JsonResponse
    {
        $tenant = $this->tenant($tenants);
        $this->authorize('manageBilling', $tenant);

        return response()->json([
            'data' => $this->payload($tenant),
        ]);
    }

    /**
     * Subscribe the salon to a plan. Without Stripe keys this activates a local Cashier subscription.
     */
    public function subscribe(SubscribeRequest $request, SubscriptionManager $billing, TenantContext $tenants): JsonResponse
    {
        $tenant = $this->tenant($tenants);
        $plan = Plan::from($request->string('plan')->toString());

        if ($billing->usesStripe()) {
            return response()->json([
                'checkout_url' => $billing->checkoutUrl($tenant, $plan),
            ]);
        }

        $billing->activate($tenant, $plan);

        return response()->json([
            'data' => $this->payload($tenant->refresh()),
        ]);
    }

    private function tenant(TenantContext $tenants): Tenant
    {
        $tenant = $tenants->get();

        if ($tenant === null) {
            throw new AccessDeniedHttpException('Unknown tenant.');
        }

        return $tenant;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Tenant $tenant): array
    {
        $subscription = $tenant->subscription('default');

        return [
            'plan' => $tenant->resolvedPlan()->value,
            'on_trial' => $tenant->onGenericTrial(),
            'trial_ends_at' => $tenant->trial_ends_at?->toIso8601String(),
            'stripe_id' => $tenant->stripe_id,
            'subscribed' => $tenant->subscribed('default'),
            'stripe_status' => $subscription?->stripe_status,
            'stripe_price' => $subscription?->stripe_price,
            'driver' => config('billing.driver') === 'stripe' && filled(config('cashier.secret')) ? 'stripe' : 'fake',
        ];
    }
}
