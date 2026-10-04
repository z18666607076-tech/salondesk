<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\BookAppointment;
use App\Ai\Contracts\BookingAssistant;
use App\Enums\AppointmentSource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Assistant\ProposeBookingRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Service;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AssistantController extends Controller
{
    /**
     * Turn a natural-language request into a proposed slot, and optionally book it.
     */
    public function store(
        ProposeBookingRequest $request,
        BookingAssistant $assistant,
        BookAppointment $bookAppointment,
        TenantContext $tenants,
    ): JsonResponse|AppointmentResource {
        $tenant = $tenants->get();

        if ($tenant === null || ! $tenant->resolvedPlan()->allowsAssistant()) {
            throw new AccessDeniedHttpException('The AI booking assistant is included on the Pro plan.');
        }

        $proposal = $assistant->propose($request->string('message')->toString());

        if ($request->boolean('confirm')) {
            if (! $proposal->available || $proposal->serviceId === null || $proposal->staffId === null || $proposal->startsAt === null) {
                return response()->json([
                    'message' => $proposal->summary,
                ], 422);
            }

            $appointment = $bookAppointment->handle(
                service: Service::query()->findOrFail($proposal->serviceId),
                staff: User::query()->findOrFail($proposal->staffId),
                startsAt: $proposal->startsAt,
                customerName: $request->string('customer_name')->toString(),
                customerEmail: $request->string('customer_email')->toString(),
                customerPhone: $request->filled('customer_phone') ? $request->string('customer_phone')->toString() : null,
                source: AppointmentSource::Ai,
            );

            return AppointmentResource::make($appointment);
        }

        return response()->json([
            'data' => [
                'type' => 'booking-proposals',
                'id' => sha1($proposal->summary.($proposal->startsAt?->toIso8601String() ?? '')),
                'attributes' => [
                    'available' => $proposal->available,
                    'summary' => $proposal->summary,
                    'service_id' => $proposal->serviceId,
                    'staff_id' => $proposal->staffId,
                    'service_name' => $proposal->serviceName,
                    'staff_name' => $proposal->staffName,
                    'starts_at' => $proposal->startsAt?->toIso8601String(),
                    'ends_at' => $proposal->endsAt?->toIso8601String(),
                ],
            ],
        ], 200, ['Content-Type' => 'application/vnd.api+json']);
    }
}
