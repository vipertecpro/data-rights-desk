<?php

use Illuminate\Support\Facades\Event;
use Vipertecpro\DataRightsDesk\Events\NoticePublished;
use Vipertecpro\DataRightsDesk\Facades\DataRights;
use Vipertecpro\DataRightsDesk\Models\Notice;

it('Rule 3: the notice itemises the data and states each purpose and use', function () {
    $this->publishNotice();
    $markdown = DataRights::notices()->current('en')->document();

    expect($markdown)->toContain('Example Shop')
        ->toContain('### Product news by email')
        ->toContain('To send you emails about new features and offers.')
        ->toContain('A newsletter at most twice a month.')
        ->toContain("- Email address\n- Name")
        ->toContain('### Usage analytics')
        ->toContain("- Pages you visit\n- Buttons you press\n- Device type")
        ->toContain('Your consent. You can withdraw it at any time.')
        ->toContain('We can no longer keep your account open.');
});

it('Rule 3: the notice gives the link to withdraw as easily as consent was given, the rights page and the Board', function () {
    $built = DataRights::notices()->build('en');

    expect($built->body)->toContain('as easily as you gave it')
        ->toContain('<https://shop.example/data-rights/preferences>')
        ->toContain('<https://shop.example/data-rights>')
        ->toContain('complain to the Data Protection Board of India, using the means the Board publishes');

    config()->set('data-rights.board.url', 'https://board.example/complaints');

    expect(DataRights::notices()->build('en')->body)->toContain('<https://board.example/complaints>')
        ->and(DataRights::notices()->build('hi')->body)->toContain('<https://shop.example/data-rights/preferences?lang=hi>');
});

it('Rule 3: the notice is standalone Markdown with the contact block, languages, version and date', function () {
    $this->travelTo(now()->setDate(2027, 1, 14));
    $this->publishNotice();
    $notice = DataRights::notices()->current('en');
    $markdown = $notice->document();

    expect($markdown)->toStartWith('# How Example Shop uses your personal data')
        ->toContain('Version 1, published 14 Jan 2027')
        ->toContain('Asha Rao, Grievance officer')
        ->toContain('Email: privacy@shop.example')
        ->toContain('Phone: +91 80 0000 0000')
        ->toContain('This notice is available in: English, हिन्दी.')
        ->and($notice->hash)->toBe(hash('sha256', $notice->body))
        ->and(DataRights::notices()->build('en')->document())->toContain('Draft: not published yet');
});

it('Rule 3: notice Markdown is rendered with raw HTML escaped and unsafe links dropped', function () {
    config()->set('data-rights.purposes.marketing.description.en', 'News <script>alert(1)</script> and [a link](javascript:alert(1)).');
    $this->publishNotice();

    $html = $this->get('/data-rights/notice')->assertOk()->getContent();

    expect($html)->not->toContain('<script>alert(1)</script>')
        ->toContain('&lt;script&gt;')
        ->not->toContain('href="javascript:');
});

it('Rule 3: a new version is published only when the text changed or a locale was added', function () {
    Event::fake([NoticePublished::class]);

    expect(DataRights::notices()->publish())->toBe(1)
        ->and(DataRights::notices()->publish())->toBeNull();

    config()->set('data-rights.purposes.marketing.title.en', 'Product news');

    expect(DataRights::notices()->publish())->toBe(2)
        ->and(Notice::query()->where('version', 2)->pluck('locale')->sort()->values()->all())->toBe(['en', 'hi'])
        ->and(DataRights::notices()->find(1, 'en')->body)->toContain('Product news by email');

    Event::assertDispatchedTimes(NoticePublished::class, 2);

    $this->artisan('data-rights:notice')->expectsOutputToContain('Nothing changed')->assertExitCode(0);
    config()->set('data-rights.purposes.marketing.title.en', 'Our news');
    $this->artisan('data-rights:notice', ['--pretend' => true])->expectsOutputToContain('would be published')->assertExitCode(0);
    expect(DataRights::notices()->currentVersion())->toBe(2);
    $this->artisan('data-rights:notice')->expectsOutputToContain('Published notice version 3')->assertExitCode(0);
});

it('Rule 3: past versions stay readable', function () {
    $this->publishNotice();
    config()->set('data-rights.purposes.marketing.title.en', 'Product news');
    $this->publishNotice();

    $this->get('/data-rights/notice/1')->assertOk()->assertSee('Product news by email')->assertSee('You are reading version 1');
    $this->get('/data-rights/notice/9')->assertNotFound();
});
