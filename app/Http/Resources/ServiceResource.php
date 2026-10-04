<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class ServiceResource extends JsonApiResource
{
    /**
     * @var list<string>
     */
    public $attributes = [
        'name',
        'description',
        'duration_minutes',
        'price_cents',
        'currency',
        'is_active',
    ];

    public function toType(Request $request): string
    {
        return 'services';
    }
}
