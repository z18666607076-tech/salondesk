<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tenant base domain
    |--------------------------------------------------------------------------
    |
    | When a request host is "{slug}.{base_domain}", the slug selects the
    | tenant. API clients and tests can send the same slug in X-Tenant.
    |
    */

    'base_domain' => env('TENANT_BASE_DOMAIN', 'salondesk.test'),

    'header' => 'X-Tenant',

];
