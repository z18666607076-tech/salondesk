<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServiceController extends Controller
{
    /**
     * List active services for the current salon.
     */
    public function index(): AnonymousResourceCollection
    {
        return ServiceResource::collection(
            Service::query()->where('is_active', true)->orderBy('name')->get(),
        );
    }
}
