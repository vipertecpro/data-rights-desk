<?php

namespace Vipertecpro\DataRightsDesk\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Vipertecpro\DataRightsDesk\Support\Contact;

/**
 * Base for the package's notifications: queued, channels from config, and
 * every message ends with the contact block (Rule 9).
 */
abstract class DataRightsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        if ($notifiable instanceof AnonymousNotifiable) {
            return array_map('strval', array_keys($notifiable->routes));
        }

        return array_values(array_map('strval', (array) config('data-rights.notifications.channels', ['mail'])));
    }

    abstract public function toMail(object $notifiable): MailMessage;

    /**
     * For the database channel: the text without personal data beyond what the person already has.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $mail = $this->toMail($notifiable);

        return [
            'subject' => $mail->subject,
            'lines' => [...$mail->introLines, ...$mail->outroLines],
            'action_url' => $mail->actionUrl,
        ];
    }

    protected function message(): MailMessage
    {
        return (new MailMessage)
            ->greeting((string) __('data-rights::mail.greeting'))
            ->salutation((string) __('data-rights::mail.salutation', ['business' => Contact::businessName()]));
    }

    protected function withContact(MailMessage $message): MailMessage
    {
        $message->line((string) __('data-rights::mail.contact_heading'));

        foreach (Contact::lines(app()->getLocale()) as $line) {
            $message->line($line);
        }

        return $message;
    }
}
