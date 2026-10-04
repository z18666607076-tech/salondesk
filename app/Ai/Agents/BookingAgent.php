<?php

namespace App\Ai\Agents;

use App\Ai\Tools\ListServicesTool;
use App\Ai\Tools\ListStaffTool;
use App\Ai\Tools\SearchSlotsTool;
use App\Models\Tenant;
use App\Tenancy\TenantContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;
use Stringable;

class BookingAgent implements Agent, HasStructuredOutput, HasTools
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        $tenant = app(TenantContext::class)->get();
        $name = $tenant instanceof Tenant ? $tenant->name : 'the salon';
        $timezone = $tenant instanceof Tenant ? $tenant->timezone : 'UTC';

        return <<<TEXT
        You are the booking assistant for {$name}.
        The salon timezone is {$timezone}.
        Use the tools to list services, staff, and open slots.
        Never invent a time. Copy service_id, staff_id, and starts_at from a tool result.
        If nothing is open, say so in the summary and still return the closest ids you found, or zeros when you found none.
        TEXT;
    }

    /**
     * @return list<Tool>
     */
    public function tools(): iterable
    {
        return [
            new ListServicesTool,
            new ListStaffTool,
            new SearchSlotsTool,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'summary' => $schema->string()->required(),
            'service_id' => $schema->integer()->required(),
            'staff_id' => $schema->integer()->required(),
            'starts_at' => $schema->string()->required(),
        ];
    }
}
