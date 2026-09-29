<?php

namespace App\Notifications;

use App\Models\InstructorApplication;

/**
 * Tells admins someone asked to become an instructor.
 */
class InstructorApplicationSubmitted extends AppNotification
{
    public function __construct(public InstructorApplication $application) {}

    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'application',
            'tone' => 'info',
            'title' => 'Pengajuan instruktur baru',
            'body' => "{$this->application->user->name} · {$this->application->expertise}",
            'url' => self::path('admin.instructor-applications.index'),
        ];
    }
}
