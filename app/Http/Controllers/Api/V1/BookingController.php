<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\BookAppointment;
use App\Actions\CancelAppointment;
use App\Actions\RescheduleAppointment;
use App\Enums\AppointmentSource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\CancelBookingRequest;
use App\Http\Requests\Booking\RescheduleBookingRequest;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    /**
     * Book a confirmed appointment for a customer.
     */
    public function store(StoreBookingRequest $request, BookAppointment $bookAppointment): JsonResponse
    {
        $service = Service::query()->where('is_active', true)->findOrFail($request->integer('service_id'));
        $staff = User::query()->where('is_bookable', true)->findOrFail($request->integer('staff_id'));

        $appointment = $bookAppointment->handle(
            service: $service,
            staff: $staff,
            startsAt: CarbonImmutable::parse($request->string('starts_at')->toString()),
            customerName: $request->string('customer_name')->toString(),
            customerEmail: $request->string('customer_email')->toString(),
            customerPhone: $request->filled('customer_phone') ? $request->string('customer_phone')->toString() : null,
            source: AppointmentSource::Api,
            notes: $request->filled('notes') ? $request->string('notes')->toString() : null,
        );

        return AppointmentResource::make($appointment)->response()->setStatusCode(201);
    }

    /**
     * Cancel a booking when the customer email matches.
     */
    public function cancel(
        CancelBookingRequest $request,
        Appointment $appointment,
        CancelAppointment $cancelAppointment,
    ): AppointmentResource {
        $this->assertCustomerEmail($appointment, $request->string('email')->toString());

        return AppointmentResource::make(
            $cancelAppointment->handle($appointment)->load(['customer', 'staff', 'service']),
        );
    }

    /**
     * Move a booking to another open slot.
     */
    public function reschedule(
        RescheduleBookingRequest $request,
        Appointment $appointment,
        RescheduleAppointment $rescheduleAppointment,
    ): AppointmentResource {
        $this->assertCustomerEmail($appointment, $request->string('email')->toString());

        return AppointmentResource::make(
            $rescheduleAppointment->handle(
                $appointment,
                CarbonImmutable::parse($request->string('starts_at')->toString()),
            ),
        );
    }

    private function assertCustomerEmail(Appointment $appointment, string $email): void
    {
        $appointment->loadMissing('customer');

        if (strcasecmp($appointment->customer->email, $email) !== 0) {
            throw ValidationException::withMessages([
                'email' => ['The customer email does not match this appointment.'],
            ]);
        }
    }
}
