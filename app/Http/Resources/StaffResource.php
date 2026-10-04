<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class StaffResource extends JsonApiResource
{
    /**
     * @var list<string>
     */
    public $attributes = [
        'name',
        'email',
        'is_bookable',
    ];

    public function toType(Request $request): string
    {
        return 'staff';
    }
}
