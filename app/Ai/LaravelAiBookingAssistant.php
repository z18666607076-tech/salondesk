<?php

namespace App\Ai;

use App\Ai\Agents\BookingAgent;
use App\Ai\Contracts\BookingAssistant;
use App\Booking\CalculateAvailability;
use App\Models\Service;
use App\Models\User;
use App\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Laravel\Ai\Responses\StructuredAgentResponse;

final class LaravelAiBookingAssistant implements BookingAssistant
{
    public function __construct(
        private TenantContext $tenants,
        private CalculateAvailability $availability,
    ) {}

    public function propose(string $message): BookingProposal
    {
        $response = (new BookingAgent)->prompt($message);

        if (! $response instanceof StructuredAgentResponse) {
            return new BookingProposal(false, 'The assistant did not return a structured booking proposal.');
        }

        return $this->guard($response->structured);
    }

    /**
     * @param  array<string, mixed>  $structured
     */
    private function guard(array $structured): BookingProposal
    {
        $tenant = $this->tenants->get();
        $serviceId = isset($structured['service_id']) ? (int) $structured['service_id'] : 0;
        $staffId = isset($structured['staff_id']) ? (int) $structured['staff_id'] : 0;
        $startsAtRaw = isset($structured['starts_at']) ? (string) $structured['starts_at'] : '';
        $summary = isset($structured['summary']) ? (string) $structured['summary'] : 'Proposed booking.';

        if ($tenant === null || $serviceId === 0 || $staffId === 0 || $startsAtRaw === '') {
            return new BookingProposal(false, 'The assistant proposal was incomplete, so no slot was reserved.');
        }

        $service = Service::query()->whereKey($serviceId)->where('is_active', true)->first();
        $staff = User::query()->whereKey($staffId)->where('is_bookable', true)->first();

        if ($service === null || $staff === null) {
            return new BookingProposal(false, 'The assistant named a service or staff member this salon does not offer.');
        }

        $startsAt = CarbonImmutable::parse($startsAtRaw);
        $slot = $this->availability->findSlot($service, $staff, $startsAt);

        if ($slot === null) {
            return new BookingProposal(
                false,
                'The assistant suggested a time that is not actually open. No appointment was created.',
                serviceId: $service->id,
                staffId: $staff->id,
                serviceName: $service->name,
                staffName: $staff->name,
            );
        }

        return new BookingProposal(
            available: true,
            summary: $summary,
            serviceId: $service->id,
            staffId: $staff->id,
            serviceName: $service->name,
            staffName: $staff->name,
            startsAt: $slot->startsAt,
            endsAt: $slot->endsAt,
        );
    }
}
