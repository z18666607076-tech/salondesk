<?php

namespace App\Ai\Tools;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ListStaffTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'List bookable staff members for the current salon.';
    }

    public function handle(Request $request): Stringable|string
    {
        $staff = User::query()
            ->where('is_bookable', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return (string) $staff->toJson();
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
