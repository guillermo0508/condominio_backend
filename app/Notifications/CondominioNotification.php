<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CondominioNotification extends Notification
{
    use Queueable;

    public string $typeNotification;
    public string $title;
    public string $message;
    public array $details;

    public function __construct(string $typeNotification, string $title, string $message, array $details = [])
    {
        $this->typeNotification = $typeNotification;
        $this->title = $title;
        $this->message = $message;
        $this->details = $details;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->typeNotification,
            'title' => $this->title,
            'message' => $this->message,
            'details' => $this->details,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
