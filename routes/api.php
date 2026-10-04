<?php

use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\AssistantController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AvailabilityController;
use App\Http\Controllers\Api\V1\BillingController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\ServiceController;
use App\Http\Controllers\Api\V1\StaffController;
use App\Http\Middleware\EnsureUserBelongsToTenant;
use App\Http\Middleware\IdentifyTenant;
use Illuminate\Support\Facades\Route;
use Laravel\Cashier\Http\Controllers\WebhookController;

Route::prefix('v1')->group(function (): void {
    Route::get('health', HealthController::class)->withoutMiddleware(IdentifyTenant::class);

    Route::post('billing/webhook', [WebhookController::class, 'handleWebhook'])
        ->withoutMiddleware(IdentifyTenant::class);

    Route::get('services', [ServiceController::class, 'index']);
    Route::get('staff', [StaffController::class, 'index']);
    Route::get('availability', [AvailabilityController::class, 'index']);

    Route::post('bookings', [BookingController::class, 'store']);
    Route::post('bookings/{appointment}/cancel', [BookingController::class, 'cancel']);
    Route::post('bookings/{appointment}/reschedule', [BookingController::class, 'reschedule']);

    Route::post('auth/login', [AuthController::class, 'login']);

    Route::middleware(['auth:sanctum', EnsureUserBelongsToTenant::class])->group(function (): void {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::get('appointments', [AppointmentController::class, 'index']);
        Route::post('appointments/{appointment}/cancel', [AppointmentController::class, 'cancel']);
        Route::post('appointments/{appointment}/reschedule', [AppointmentController::class, 'reschedule']);

        Route::post('assistant/bookings', [AssistantController::class, 'store']);

        Route::get('billing', [BillingController::class, 'show']);
        Route::post('billing/subscribe', [BillingController::class, 'subscribe']);
    });
});
