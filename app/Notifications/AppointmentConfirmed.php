<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentConfirmed extends Notification
{
    use Queueable;

    public function __construct(public Appointment $appointment) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appointment = $this->appointment;
        $timezone = $appointment->tenant->timezone;
        $when = $appointment->starts_at->timezone($timezone)->format('D, M j, Y \a\t g:i A');

        return (new MailMessage)
            ->subject('Your '.$appointment->service->name.' is booked')
            ->greeting('You are booked, '.$appointment->customer->name.'.')
            ->line($appointment->service->name.' with '.$appointment->staff->name.' at '.$appointment->tenant->name.'.')
            ->line($when.' ('.$timezone.').')
            ->line('If you need to cancel or move this visit, use the same email address you booked with.');
    }
}
