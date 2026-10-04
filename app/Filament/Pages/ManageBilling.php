<?php

namespace App\Filament\Pages;

use App\Billing\SubscriptionManager;
use App\Enums\Plan;
use App\Models\Tenant;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class ManageBilling extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static ?string $navigationLabel = 'Billing';

    protected static ?string $title = 'Subscription';

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.pages.manage-billing';

    public static function canAccess(): bool
    {
        $tenant = Filament::getTenant();
        $user = auth()->user();

        return $tenant instanceof Tenant
            && $user !== null
            && $user->can('manageBilling', $tenant);
    }

    public function subscribe(string $plan, SubscriptionManager $billing): void
    {
        $tenant = Filament::getTenant();

        if (! $tenant instanceof Tenant) {
            return;
        }

        $selected = Plan::from($plan);

        if ($billing->usesStripe()) {
            $this->redirect($billing->checkoutUrl($tenant, $selected));

            return;
        }

        $billing->activate($tenant, $selected);

        Notification::make()
            ->title($selected->label().' is now active for this salon.')
            ->success()
            ->send();
    }

    public function plan(): string
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Tenant ? $tenant->resolvedPlan()->value : Plan::Basic->value;
    }

    public function onTrial(): bool
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Tenant && $tenant->onGenericTrial();
    }

    public function driver(): string
    {
        return config('billing.driver') === 'stripe' && filled(config('cashier.secret')) ? 'stripe' : 'fake';
    }
}
