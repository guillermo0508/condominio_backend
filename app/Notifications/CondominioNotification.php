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

    /**
     * Create a new notification instance.
     */
    public function __construct(string $typeNotification, string $title, string $message, array $details = [])
    {
        $this->typeNotification = $typeNotification;
        $this->title = $title;
        $this->message = $message;
        $this->details = $details;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the array representation of the notification (used for database).
     *
     * @return array<string, mixed>
     */
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
