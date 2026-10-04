<?php

namespace App\Ai\Tools;

use App\Models\Service;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ListServicesTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'List active services for the current salon, including id, name, duration, and price.';
    }

    public function handle(Request $request): Stringable|string
    {
        $services = Service::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'duration_minutes', 'price_cents', 'currency']);

        return (string) $services->toJson();
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
