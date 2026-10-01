<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SystemAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public string $title;
    public string $message;
    public string $level;
    public ?string $actionUrl;
    public ?array $metadata;

    public function __construct(
        string $title,
        string $message,
        string $level = 'info',
        ?string $actionUrl = null,
        ?array $metadata = null
    ) {
        $this->title = $title;
        $this->message = $message;
        $this->level = $level;
        $this->actionUrl = $actionUrl;
        $this->metadata = $metadata;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'level' => $this->level,
            'action_url' => $this->actionUrl,
            'metadata' => $this->metadata,
        ];
    }
}
