<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DepartmentChatMessage implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public string $message;
    public int $userId;
    public string $userName;
    public ?string $profilePictureUrl;

    public function __construct(string $message, int $userId, string $userName, ?string $profilePictureUrl = null)
    {
        $this->message = $message;
        $this->userId = $userId;
        $this->userName = $userName;
        $this->profilePictureUrl = $profilePictureUrl;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('department'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}
