<?php

namespace App\Billing;

use App\Enums\Plan;
use App\Models\Tenant;
use Illuminate\Support\Str;
use Laravel\Cashier\Subscription;
use RuntimeException;
use Stripe\Subscription as StripeSubscription;

final class SubscriptionManager
{
    public function usesStripe(): bool
    {
        return config('billing.driver') === 'stripe' && filled(config('cashier.secret'));
    }

    /**
     * Activate a plan without calling Stripe. Used in tests and when no secret key is configured.
     */
    public function activate(Tenant $tenant, Plan $plan): Subscription
    {
        if (blank($tenant->stripe_id)) {
            $tenant->forceFill([
                'stripe_id' => 'cus_fake_'.$tenant->getKey(),
            ])->save();
        }

        $tenant->subscriptions()
            ->where('type', 'default')
            ->update([
                'stripe_status' => StripeSubscription::STATUS_CANCELED,
                'ends_at' => now()->subMinute(),
            ]);

        $subscription = $tenant->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_fake_'.Str::lower((string) Str::ulid()),
            'stripe_status' => StripeSubscription::STATUS_ACTIVE,
            'stripe_price' => $plan->stripePriceId(),
            'quantity' => 1,
        ]);

        if (! $subscription instanceof Subscription) {
            throw new RuntimeException('Cashier did not return a subscription.');
        }

        $subscription->items()->create([
            'stripe_id' => 'si_fake_'.Str::lower((string) Str::ulid()),
            'stripe_product' => 'prod_fake_'.$plan->value,
            'stripe_price' => $plan->stripePriceId(),
            'quantity' => 1,
        ]);

        return $subscription->refresh();
    }

    public function checkoutUrl(Tenant $tenant, Plan $plan): string
    {
        $session = $tenant->newSubscription('default', $plan->stripePriceId())->checkout([
            'success_url' => url('/admin'),
            'cancel_url' => url('/admin'),
        ]);

        return (string) $session->asStripeCheckoutSession()->url;
    }
}
