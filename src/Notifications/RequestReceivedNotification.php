<?php

namespace Vipertecpro\DataRightsDesk\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Route;
use Vipertecpro\DataRightsDesk\Models\DataRightsRequest;
use Vipertecpro\DataRightsDesk\Support\Dates;

/** The acknowledgement of a request, with its due date and the contact block. */
class RequestReceivedNotification extends DataRightsNotification
{
    public function __construct(public DataRightsRequest $request) {}

    public function toMail(object $notifiable): MailMessage
    {
        $locale = app()->getLocale();
        $message = $this->message()
            ->subject((string) __('data-rights::mail.request_received.subject'))
            ->line((string) __('data-rights::mail.request_received.intro', ['type' => (string) __('data-rights::public.types.'.$this->request->type->value)]))
            ->line((string) __('data-rights::mail.request_received.reference', ['id' => $this->request->getKey()]))
            ->line((string) __('data-rights::mail.request_received.due', ['date' => Dates::day($this->request->due_at, $locale)]));

        if (Route::has('data-rights.requests.show')) {
            $message->action((string) __('data-rights::mail.request_received.action'), route('data-rights.requests.show', $this->request));
        }

        return $this->withContact($message);
    }
}
