<?php

use Vipertecpro\DataRightsDesk\Exceptions\NoticeIncomplete;
use Vipertecpro\DataRightsDesk\Facades\DataRights;

it('s.5(3): the notice and the consent page are offered in each offered locale', function () {
    $this->publishNotice();
    $user = $this->makeUser();

    expect(DataRights::notices()->offeredLocales())->toBe(['en', 'hi']);

    $this->get('/data-rights/notice?lang=hi')->assertOk()
        ->assertSee('<html lang="hi" dir="ltr">', false)
        ->assertSee(config('data-rights.purposes.marketing.title.hi'))
        ->assertSee('हिन्दी');

    $this->actingAs($user)->get('/data-rights/preferences?lang=hi')->assertOk()
        ->assertSee(config('data-rights.purposes.marketing.title.hi'))
        ->assertSee('name="lang" value="hi"', false);

    $this->actingAs($user)->post('/data-rights/consents/marketing', ['lang' => 'hi'])
        ->assertRedirect('/data-rights/preferences?lang=hi');

    expect(DataRights::consents()->latest($user, 'marketing')->locale)->toBe('hi')
        ->and(DataRights::consents()->latest($user, 'marketing')->notice_hash)->toBe(DataRights::notices()->current('hi')->hash);
});

it('s.5(3): a locale whose language status is needed cannot be offered or published', function () {
    config()->set('data-rights.locales', ['en', 'hi', 'ta']);

    expect(DataRights::notices()->offeredLocales())->toBe(['en', 'hi'])
        ->and(fn () => DataRights::notices()->publish())->toThrow(NoticeIncomplete::class);

    $this->artisan('data-rights:notice')
        ->expectsOutputToContain('ta: the package text has no translation yet')
        ->assertExitCode(1);

    $this->get('/data-rights/notice?lang=ta')->assertOk()->assertSee('<html lang="en"', false);
});

it('s.5(3): the notice command names missing purpose text and refuses to publish', function () {
    config()->set('data-rights.purposes.marketing.title', ['en' => 'Product news by email']);

    $this->artisan('data-rights:notice')
        ->expectsOutputToContain('hi: purposes.marketing.title is missing')
        ->assertExitCode(1);

    expect(DataRights::notices()->currentVersion())->toBeNull();
});

it('s.5(3): the consent page shows the contact block', function () {
    $this->publishNotice();

    $this->actingAs($this->makeUser())->get('/data-rights/preferences')->assertOk()
        ->assertSee('Asha Rao, Grievance officer')
        ->assertSee('Email: privacy@shop.example');
});
