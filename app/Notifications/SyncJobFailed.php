<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SyncJobFailed extends Notification
{
    use Queueable;

    /**
     * @param array{projects:int,successful:int,failed:int,remote:int,local_only:int} $stats
     */
    public function __construct(private readonly array $stats)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Scheduled Statement Sync Completed with Failures')
            ->error()
            ->line('The scheduled statements sync encountered failures:')
            ->line('Projects processed: ' . $this->stats['projects'])
            ->line('Successful: ' . $this->stats['successful'])
            ->line('Failed: ' . $this->stats['failed'])
            ->line('Remote copies: ' . $this->stats['remote'])
            ->line('Local-only copies: ' . $this->stats['local_only'])
            ->line('Inspect sync logs for more details.');
    }
}
