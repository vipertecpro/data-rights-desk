<?php

namespace Vipertecpro\DataRightsDesk\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Vipertecpro\DataRightsDesk\Breaches\BreachDesk;
use Vipertecpro\DataRightsDesk\Models\Breach;
use Vipertecpro\DataRightsDesk\Support\Contact;

/** Rule 7(1)(a)-(e): tells an affected person about a breach. */
class BreachNotification extends DataRightsNotification
{
    public function __construct(public Breach $breach) {}

    public function toMail(object $notifiable): MailMessage
    {
        $text = app(BreachDesk::class)->personMessage($this->breach, app()->getLocale());
        $paragraphs = preg_split('/\R{2,}/', trim($text)) ?: [];

        // The first paragraph is the greeting.
        $greeting = array_shift($paragraphs);

        $message = (new MailMessage)
            ->subject((string) __('data-rights::mail.breach.subject', ['business' => Contact::businessName()]))
            ->greeting((string) $greeting)
            ->salutation((string) __('data-rights::mail.salutation', ['business' => Contact::businessName()]));

        foreach ($paragraphs as $paragraph) {
            foreach (preg_split('/\R/', $paragraph) ?: [] as $line) {
                $message->line($line);
            }
        }

        return $message;
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'breach',
            'reference' => $this->breach->getKey(),
            'became_aware_at' => $this->breach->became_aware_at->toIso8601String(),
        ];
    }
}
