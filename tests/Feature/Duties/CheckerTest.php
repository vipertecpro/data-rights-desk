<?php

use Illuminate\Support\Carbon;
use Vipertecpro\DataRightsDesk\Contracts\ParentalConsentVerifier;
use Vipertecpro\DataRightsDesk\Enums\RequestType;
use Vipertecpro\DataRightsDesk\Facades\DataRights;
use Vipertecpro\DataRightsDesk\Models\Notice;
use Vipertecpro\DataRightsDesk\Tests\Support\AllowingParentVerifier;
use Vipertecpro\DataRightsDesk\Tests\Support\UsersSource;

function cleanSetup(): void
{
    config()->set('data-rights.purposes', [
        'marketing' => [
            'title' => ['en' => 'Newsletter', 'hi' => '[hi] Newsletter'],
            'description' => ['en' => 'Emails about new things.', 'hi' => '[hi] Emails about new things.'],
            'goods_or_services' => ['en' => 'A monthly email', 'hi' => '[hi] A monthly email'],
            'data' => ['en' => ['Email address'], 'hi' => ['[hi] Email address']],
            'basis' => 'consent',
        ],
    ]);
    config()->set('data-rights.board.url', 'https://board.example');
    config()->set('data-rights.sources', [UsersSource::class]);
    app()->bind(ParentalConsentVerifier::class, AllowingParentVerifier::class);
    DataRights::notices()->publish();
}

it('Checker: a clean setup passes and the command exits 0', function () {
    cleanSetup();

    $report = DataRights::check();

    expect($report->errors())->toBe([])
        ->and($report->warnings())->toBe([])
        ->and($report->passed())->toBeTrue();

    $this->artisan('data-rights:check')->expectsOutputToContain('0 error(s), 0 warning(s).')->assertExitCode(0);
});

it('Checker: errors for missing contact, missing or stale notice, needed locale, response days, overdue requests and late breach reports', function () {
    cleanSetup();
    config()->set('data-rights.contact.email', null);
    config()->set('data-rights.contact.phone', null);
    config()->set('data-rights.requests.response_days', 100);

    $this->travelTo(Carbon::parse('2027-06-01 10:00:00'));
    DataRights::requests()->open(RequestType::Grievance, $this->makeUser(), null);
    DataRights::breaches()->record(['nature' => 'Lost laptop']);
    $this->travelTo(Carbon::parse('2027-09-15 10:00:00'));
    config()->set('data-rights.sources', []);

    $report = DataRights::check();

    foreach (['contact_missing', 'notice_stale', 'response_days_over_90', 'requests_overdue', 'breach_report_overdue', 'no_eraser'] as $code) {
        expect($report->hasError($code))->toBeTrue($code);
    }

    config()->set('data-rights.locales', ['en', 'hi', 'ta']);
    expect(DataRights::check()->hasError('locale_needed'))->toBeTrue();

    Notice::query()->delete();
    expect(DataRights::check()->hasError('notice_missing'))->toBeTrue();

    $this->artisan('data-rights:check')->assertExitCode(1);
    $this->artisan('data-rights:check', ['--json' => true])->expectsOutputToContain('"passed": false')->assertExitCode(1);
});

it('Checker: warnings for keep days, Hindi, Board URL, example purposes, uncovered columns, unchecked purposes, the default gate and the parental verifier', function () {
    config()->set('data-rights.retention.keep_days', 100);
    config()->set('data-rights.locales', ['en']);
    $this->app['env'] = 'production';
    DataRights::notices()->publish();

    $report = DataRights::check();

    foreach ([
        'keep_days_below_minimum', 'hindi_not_offered', 'board_url_empty', 'example_purposes',
        'uncovered_personal_columns', 'consent_unchecked', 'default_gate', 'parental_verifier_missing',
    ] as $code) {
        expect($report->hasWarning($code))->toBeTrue($code);
    }

    $unchecked = array_column(array_filter($report->warnings(), fn ($w) => $w['code'] === 'consent_unchecked'), 'message');
    expect(implode(' ', $unchecked))->toContain('[account]')->toContain('[analytics]')->not->toContain('[marketing]');

    $columns = array_column(array_filter($report->warnings(), fn ($w) => $w['code'] === 'uncovered_personal_columns'), 'message');
    expect(implode(' ', $columns))->toContain('[users]')->toContain('email')->not->toContain('[orders]');
});

it('Checker: invalid purposes are reported as an error', function () {
    config()->set('data-rights.purposes.Bad Key', ['title' => 'x']);

    expect(DataRights::check()->hasError('purposes_invalid'))->toBeTrue();
});
