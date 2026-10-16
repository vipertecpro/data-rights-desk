<?php

namespace Vipertecpro\DataRightsDesk\Notifications;

use Carbon\CarbonInterface;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Route;
use Vipertecpro\DataRightsDesk\Support\Contact;
use Vipertecpro\DataRightsDesk\Support\Dates;

/**
 * Rule 8(2): at least forty-eight hours before erasure, tell the person her
 * data will be erased unless she logs in or contacts the Data Fiduciary.
 */
class ErasureWarningNotification extends DataRightsNotification
{
    public function __construct(public CarbonInterface $erasureDueAt) {}

    public function toMail(object $notifiable): MailMessage
    {
        $date = Dates::day($this->erasureDueAt, app()->getLocale());

        $message = $this->message()
            ->subject((string) __('data-rights::mail.erasure_warning.subject', ['date' => $date]))
            ->line((string) __('data-rights::mail.erasure_warning.intro', ['business' => Contact::businessName(), 'date' => $date]))
            ->line((string) __('data-rights::mail.erasure_warning.keep'));

        if (Route::has('login')) {
            $message->action((string) __('data-rights::mail.erasure_warning.action'), route('login'));
        }

        return $this->withContact($message);
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'erasure_warning',
            'erasure_due_at' => $this->erasureDueAt->toIso8601String(),
        ];
    }
}
