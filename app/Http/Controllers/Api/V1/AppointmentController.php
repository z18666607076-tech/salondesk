<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CancelAppointment;
use App\Actions\RescheduleAppointment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\StaffAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Attributes\Controllers\Authorize;

class AppointmentController extends Controller
{
    /**
     * List appointments visible to the authenticated staff member.
     */
    #[Authorize('viewAny', Appointment::class)]
    public function index(): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = request()->user();

        $appointments = Appointment::query()
            ->with(['customer', 'staff', 'service'])
            ->when(! $user->seesEveryAppointment(), fn ($query) => $query->where('staff_id', $user->id))
            ->orderBy('starts_at')
            ->get();

        return AppointmentResource::collection($appointments);
    }

    /**
     * Cancel an appointment. Owners and receptionists may bypass the cancellation window.
     */
    #[Authorize('cancel', 'appointment')]
    public function cancel(Appointment $appointment, CancelAppointment $cancelAppointment): AppointmentResource
    {
        /** @var User $user */
        $user = request()->user();

        return AppointmentResource::make(
            $cancelAppointment->handle($appointment, $user->seesEveryAppointment())->load(['customer', 'staff', 'service']),
        );
    }

    /**
     * Reschedule an appointment. Owners and receptionists may bypass the cancellation window.
     */
    #[Authorize('reschedule', 'appointment')]
    public function reschedule(
        StaffAppointmentRequest $request,
        Appointment $appointment,
        RescheduleAppointment $rescheduleAppointment,
    ): AppointmentResource {
        /** @var User $user */
        $user = request()->user();

        return AppointmentResource::make(
            $rescheduleAppointment->handle(
                $appointment,
                CarbonImmutable::parse($request->string('starts_at')->toString()),
                $user->seesEveryAppointment(),
            ),
        );
    }
}
