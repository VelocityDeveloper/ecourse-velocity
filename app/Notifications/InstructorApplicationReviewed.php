<?php

namespace App\Notifications;

use App\Models\InstructorApplication;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Tells an applicant whether they may now teach.
 */
class InstructorApplicationReviewed extends AppNotification
{
    public function __construct(public InstructorApplication $application) {}

    protected function mailed(): bool
    {
        return true;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $approved = $this->application->status === InstructorApplication::STATUS_APPROVED;
        $note = $this->application->admin_note;

        $message = (new MailMessage)
            ->subject($approved ? 'Pengajuan instruktur Anda disetujui' : 'Pengajuan instruktur Anda belum disetujui')
            ->greeting('Halo, '.$this->application->user->name.'!');

        if ($approved) {
            $message->line('Selamat, pengajuan Anda menjadi instruktur telah disetujui. Anda sekarang bisa membuat dan menjual kursus.');
        } else {
            $message->line('Terima kasih atas minat Anda mengajar. Untuk saat ini pengajuan Anda belum dapat kami setujui.');
        }

        if ($note !== null && $note !== '') {
            $message->line('Catatan admin: '.$note);
        }

        return $approved
            ? $message->action('Buka Dasbor', route('dashboard'))
            : $message->line('Anda dapat memperbaiki data lalu mengajukan kembali.')
                ->action('Ajukan Kembali', route('instructor-applications.create'));
    }

    /**
     * @return array{kind: string, tone: string, title: string, body: string, url: string}
     */
    public function toArray(object $notifiable): array
    {
        $approved = $this->application->status === InstructorApplication::STATUS_APPROVED;

        return [
            'kind' => 'application',
            'tone' => $approved ? 'success' : 'warning',
            'title' => $approved ? 'Pengajuan instruktur disetujui' : 'Pengajuan instruktur belum disetujui',
            'body' => $approved
                ? 'Anda sekarang bisa membuat dan menjual kursus.'
                : ($this->application->admin_note ?: 'Anda dapat memperbaiki data lalu mengajukan kembali.'),
            'url' => $approved ? self::path('dashboard') : self::path('instructor-applications.create'),
        ];
    }
}
