<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ConversationRequestNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $studentName,
        private readonly ?string $message,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Gespreksaanvraag van '.$this->studentName,
            'message' => $this->message ?: 'De student wil graag een coachgesprek inplannen.',
        ];
    }
}
