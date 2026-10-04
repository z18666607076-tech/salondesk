<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\StaffResource;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StaffController extends Controller
{
    /**
     * List bookable staff for the current salon.
     */
    public function index(): AnonymousResourceCollection
    {
        return StaffResource::collection(
            User::query()->where('is_bookable', true)->orderBy('name')->get(),
        );
    }
}
