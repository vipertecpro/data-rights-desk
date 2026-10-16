<?php

namespace Vipertecpro\DataRightsDesk\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Route;
use Vipertecpro\DataRightsDesk\Models\Notice;
use Vipertecpro\DataRightsDesk\Support\Contact;
use Vipertecpro\DataRightsDesk\Support\Languages;

/** s.5(2) and notice updates: the notice, sent to the person. */
class NoticeNotification extends DataRightsNotification
{
    public function __construct(public Notice $notice) {}

    public function toMail(object $notifiable): MailMessage
    {
        $message = $this->message()
            ->subject((string) __('data-rights::mail.notice.subject', ['business' => Contact::businessName()]))
            ->line((string) __('data-rights::mail.notice.intro'));

        if (Route::has('data-rights.notice.version')) {
            $parameters = ['version' => $this->notice->version];

            if ($this->notice->locale !== Languages::defaultLocale()) {
                $parameters['lang'] = $this->notice->locale;
            }

            $message->action((string) __('data-rights::mail.notice.action'), route('data-rights.notice.version', $parameters));
        }

        $message->line((string) __('data-rights::mail.notice.withdraw'));

        return $this->withContact($message);
    }
}
