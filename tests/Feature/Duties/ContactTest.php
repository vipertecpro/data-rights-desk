<?php

use Illuminate\Support\Facades\Notification;
use Vipertecpro\DataRightsDesk\Enums\RequestType;
use Vipertecpro\DataRightsDesk\Facades\DataRights;
use Vipertecpro\DataRightsDesk\Notifications\RequestClosedNotification;
use Vipertecpro\DataRightsDesk\Notifications\RequestReceivedNotification;

it('Rule 9: the contact block is on the rights page, in every notice and in every request notification', function () {
    $this->get('/data-rights')->assertOk()
        ->assertSee('Asha Rao, Grievance officer')
        ->assertSee('Email: privacy@shop.example')
        ->assertSee('Phone: +91 80 0000 0000');

    $this->publishNotice();

    foreach (DataRights::notices()->offeredLocales() as $locale) {
        expect(DataRights::notices()->current($locale)->document())->toContain('privacy@shop.example');
    }

    $user = $this->makeUser();
    $request = DataRights::requests()->open(RequestType::Access, $user, null, ['actor' => $user]);
    $lines = fn ($mail) => implode("\n", [...$mail->introLines, ...$mail->outroLines]);
    $received = $lines((new RequestReceivedNotification($request))->toMail($user));

    DataRights::requests()->complete($request, 'Here is your summary.');
    $closed = $lines((new RequestClosedNotification($request->fresh()))->toMail($user));

    foreach ([$received, $closed] as $text) {
        expect($text)->toContain('Asha Rao, Grievance officer')
            ->toContain('Email: privacy@shop.example')
            ->toContain('Example Shop');
    }

    expect(DataRights::contact())->toMatchArray(['email' => 'privacy@shop.example', 'role' => 'Grievance officer']);
});

it('Rule 9: notifications go out in the person\'s language', function () {
    Notification::fake();
    $user = $this->makeUser();
    $user->preferredDataRightsLocale = 'hi';

    DataRights::requests()->open(RequestType::Grievance, $user, null);

    Notification::assertSentTo($user, RequestReceivedNotification::class, fn ($n) => $n->locale === 'hi');
});
