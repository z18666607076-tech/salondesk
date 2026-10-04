<?php

use App\Models\Tenant;
use Stripe\WebhookSignature;

it('activates a cashier subscription without calling stripe', function () {
    $salon = salon(['trial_ends_at' => null]);

    $token = $this->withHeaders(tenantHeaders($salon['tenant']))
        ->postJson('/api/v1/auth/login', [
            'email' => $salon['owner']->email,
            'password' => 'password',
        ])
        ->assertOk()
        ->json('token');

    $this->withHeaders([
        ...tenantHeaders($salon['tenant']),
        'Authorization' => 'Bearer '.$token,
    ])->postJson('/api/v1/billing/subscribe', ['plan' => 'pro'])
        ->assertOk()
        ->assertJsonPath('data.plan', 'pro')
        ->assertJsonPath('data.subscribed', true)
        ->assertJsonPath('data.driver', 'fake');

    expect($salon['tenant']->refresh()->subscribed('default'))->toBeTrue()
        ->and($salon['tenant']->resolvedPlan()->value)->toBe('pro');
});

it('forbids staff from changing the plan', function () {
    $salon = salon();

    $token = $this->withHeaders(tenantHeaders($salon['tenant']))
        ->postJson('/api/v1/auth/login', [
            'email' => $salon['staff']->email,
            'password' => 'password',
        ])
        ->json('token');

    $this->withHeaders([
        ...tenantHeaders($salon['tenant']),
        'Authorization' => 'Bearer '.$token,
    ])->postJson('/api/v1/billing/subscribe', ['plan' => 'pro'])
        ->assertForbidden();
});

it('records a signed stripe subscription webhook', function () {
    $tenant = Tenant::factory()->basic()->create([
        'stripe_id' => 'cus_test_1',
    ]);

    $payload = [
        'id' => 'evt_test_1',
        'type' => 'customer.subscription.created',
        'data' => [
            'object' => [
                'id' => 'sub_test_1',
                'customer' => 'cus_test_1',
                'status' => 'active',
                'metadata' => ['type' => 'default'],
                'items' => [
                    'data' => [[
                        'id' => 'si_test_1',
                        'quantity' => 1,
                        'price' => [
                            'id' => 'price_fake_pro',
                            'product' => 'prod_pro',
                        ],
                    ]],
                ],
            ],
        ],
    ];

    $json = json_encode($payload, JSON_THROW_ON_ERROR);
    $signature = WebhookSignature::generateSignatureHeader($json, 'whsec_test_secret');

    $this->call('POST', '/api/v1/billing/webhook', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => $signature,
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
    ], $json)->assertOk();

    expect($tenant->refresh()->subscribed('default'))->toBeTrue()
        ->and($tenant->resolvedPlan()->value)->toBe('pro');
});

it('rejects an unsigned stripe webhook', function () {
    $this->postJson('/api/v1/billing/webhook', [
        'id' => 'evt_bad',
        'type' => 'customer.subscription.created',
        'data' => ['object' => []],
    ])->assertForbidden();
});
