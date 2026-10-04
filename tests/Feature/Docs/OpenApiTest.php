<?php

it('publishes the openapi document', function () {
    $this->get('/docs/api')->assertOk();
    $this->getJson('/docs/api.json')->assertOk()->assertJsonStructure(['openapi', 'paths', 'info']);
});

it('exposes a health check that does not need a tenant', function () {
    $this->getJson('/api/v1/health')->assertOk()->assertJson(['status' => 'ok']);
    $this->get('/up')->assertOk();
});
