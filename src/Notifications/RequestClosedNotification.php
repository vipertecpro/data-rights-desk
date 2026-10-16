<?php

namespace Vipertecpro\DataRightsDesk\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Route;
use Vipertecpro\DataRightsDesk\Enums\RequestStatus;
use Vipertecpro\DataRightsDesk\Models\DataRightsRequest;
use Vipertecpro\DataRightsDesk\Support\Dates;
use Vipertecpro\DataRightsDesk\Support\Limits;

/**
 * The reply to a request, completed or rejected. For erasure it is sent after
 * erasure to the address captured before, through an on-demand route.
 */
class RequestClosedNotification extends DataRightsNotification
{
    public function __construct(public DataRightsRequest $request) {}

    public function toMail(object $notifiable): MailMessage
    {
        $completed = $this->request->status === RequestStatus::Completed;
        $type = (string) __('data-rights::public.types.'.$this->request->type->value);

        $message = $this->message()
            ->subject((string) __($completed ? 'data-rights::mail.request_closed.subject_completed' : 'data-rights::mail.request_closed.subject_rejected'))
            ->line((string) __($completed ? 'data-rights::mail.request_closed.intro_completed' : 'data-rights::mail.request_closed.intro_rejected', ['type' => $type]))
            ->line((string) __('data-rights::mail.request_closed.reference', ['id' => $this->request->getKey()]));

        if (filled($this->request->response)) {
            $message->line((string) __('data-rights::mail.request_closed.response'));

            foreach (preg_split('/\R{2,}/', (string) $this->request->response) ?: [] as $paragraph) {
                $message->line($paragraph);
            }
        }

        if ($this->request->downloadable() && Route::has('data-rights.requests.download') && $this->request->closed_at !== null) {
            $message->action((string) __('data-rights::mail.request_closed.download'), route('data-rights.requests.download', $this->request));
            $message->line((string) __('data-rights::mail.request_closed.download_until', [
                'date' => Dates::day($this->request->closed_at->copy()->addDays(Limits::downloadDays()), app()->getLocale()),
            ]));
        }

        $message->line((string) __('data-rights::mail.request_closed.grievance'));

        return $this->withContact($message);
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'request_closed',
            'reference' => $this->request->getKey(),
            'request_type' => $this->request->type->value,
            'status' => $this->request->status->value,
            'closed_at' => $this->request->closed_at?->toIso8601String(),
        ];
    }
}
