<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\BackupLog;
use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectBackupFailed extends Notification
{
    use Queueable;

    public function __construct(private readonly Project $project, private readonly BackupLog $log, private readonly string $errorMessage)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Project Statement Backup Failed')
            ->error()
            ->line('Project: ' . $this->project->name . ' (' . $this->project->code . ')')
            ->line('Error: ' . $this->errorMessage)
            ->line('Log ID: ' . $this->log->id)
            ->line('Triggered By: ' . ($this->log->triggered_by ?? 'system'));
    }
}
