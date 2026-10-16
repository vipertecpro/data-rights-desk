<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Vipertecpro\DataRightsDesk\Facades\DataRights;
use Vipertecpro\DataRightsDesk\Models\Breach;
use Vipertecpro\DataRightsDesk\Models\BreachSubject;
use Vipertecpro\DataRightsDesk\Notifications\BreachNotification;

function recordBreach(): Breach
{
    return DataRights::breaches()->record([
        'title' => 'Exposed backup',
        'nature' => 'A database backup was readable on a public link.',
        'extent' => 'Names and emails of about 300 customers.',
        'location' => 'Cloud storage bucket in Mumbai region',
        'likely_impact' => 'Phishing emails to customers.',
        'occurred_at' => '2027-06-01 08:00:00',
        'became_aware_at' => '2027-06-02 10:00:00',
        'consequences_for_people' => 'You may get emails that pretend to be from us.',
        'mitigation' => 'We closed the link and rotated keys.',
        'safety_steps' => 'Do not click links in unexpected emails.',
        'facts' => 'A staff member shared the wrong folder.',
        'findings' => 'No outside access found in the logs.',
        'remedial' => 'Links now expire after one hour.',
    ]);
}

it('Rule 7(1): the message to a person has the description, consequences, mitigation, safety steps and contact', function () {
    $message = DataRights::breaches()->personMessage(recordBreach());

    expect($message)
        ->toContain('(a) What happened')
        ->toContain('A database backup was readable on a public link.')
        ->toContain('Extent: Names and emails of about 300 customers.')
        ->toContain('When: 1 Jun 2027')
        ->toContain('(b) What it may mean for you')
        ->toContain('You may get emails that pretend to be from us.')
        ->toContain('(c) What we are doing')
        ->toContain('We closed the link and rotated keys.')
        ->toContain('(d) What you can do to protect yourself')
        ->toContain('Do not click links in unexpected emails.')
        ->toContain('(e) Who to contact')
        ->toContain('Email: privacy@shop.example');

    expect(DataRights::breaches()->personMessage(recordBreach(), 'hi'))->toContain('(e)');
});

it('Rule 7(1): notifyAffected tells each person once and records when', function () {
    Notification::fake();
    $breach = recordBreach();
    $one = $this->makeUser('one@example.com');
    $two = $this->makeUser('two@example.com');

    expect(DataRights::breaches()->addAffected($breach, [$one, $two, $one]))->toBe(2)
        ->and(DataRights::breaches()->notifyAffected($breach))->toBe(2)
        ->and(DataRights::breaches()->notifyAffected($breach))->toBe(0);

    Notification::assertSentToTimes($one, BreachNotification::class, 1);
    Notification::assertSentToTimes($two, BreachNotification::class, 1);
    expect(BreachSubject::query()->whereNull('notified_at')->count())->toBe(0);

    $mail = (new BreachNotification($breach))->toMail($one);
    expect(implode("\n", [...$mail->introLines, ...$mail->outroLines]))->toContain('Do not click links in unexpected emails.')
        ->toContain('Email: privacy@shop.example');
});

it('Rule 7(2): the alert draft has the nature, extent, timing, location and likely impact', function () {
    $alert = DataRights::breaches()->boardAlertDraft(recordBreach());

    expect($alert)->toContain('Rule 7(2)(a)')
        ->toContain('Nature: A database backup was readable on a public link.')
        ->toContain('Extent: Names and emails of about 300 customers.')
        ->toContain('Timing: occurred 1 Jun 2027, 08:00; became aware 2 Jun 2027, 10:00')
        ->toContain('Location: Cloud storage bucket in Mumbai region')
        ->toContain('Likely impact: Phishing emails to customers.')
        ->toContain('Check every detail before sending it to the Board.');
});

it('Rule 7(2): the report draft has items (i) to (vi) with the count of people told', function () {
    Notification::fake();
    $breach = recordBreach();
    DataRights::breaches()->addAffected($breach, [$this->makeUser('one@example.com'), $this->makeUser('two@example.com')]);
    DataRights::breaches()->notifyAffected($breach);
    DataRights::breaches()->addAffected($breach, [$this->makeUser('three@example.com')]);

    $report = DataRights::breaches()->boardReportDraft($breach);

    expect($report)->toContain('Rule 7(2)(b)')
        ->toContain('(i) Updated and detailed description')
        ->toContain('(ii) Broad facts')
        ->toContain('A staff member shared the wrong folder.')
        ->toContain('(iii) Measures implemented or proposed to mitigate risk')
        ->toContain('(iv) Findings regarding the person who caused the breach')
        ->toContain('No outside access found in the logs.')
        ->toContain('(v) Remedial measures taken to prevent recurrence')
        ->toContain('Links now expire after one hour.')
        ->toContain('(vi) Intimations given to affected Data Principals')
        ->toContain('3 people recorded as affected; 2 told so far');

    $this->artisan('data-rights:breach-draft', ['breach' => $breach->id, 'kind' => 'alert'])->expectsOutputToContain('Rule 7(2)(a)')->assertExitCode(0);
    $this->artisan('data-rights:breach-draft', ['breach' => $breach->id, 'kind' => 'person', '--lang' => 'en'])->expectsOutputToContain('(e) Who to contact')->assertExitCode(0);
    $this->artisan('data-rights:breach-draft', ['breach' => $breach->id, 'kind' => 'other'])->assertExitCode(1);
    $this->artisan('data-rights:breach-draft', ['breach' => 'missing'])->assertExitCode(1);
});

it('Rule 7(2): the report is due 72 hours after becoming aware, an extension counts, and overdue is shown', function () {
    $this->travelTo(Carbon::parse('2027-06-02 12:00:00'));
    $breach = recordBreach();

    expect($breach->board_report_due_at->toDateTimeString())->toBe('2027-06-05 10:00:00')
        ->and($breach->isReportOverdue())->toBeFalse()
        ->and($breach->hoursLeft())->toBe(70);

    $this->travelTo(Carbon::parse('2027-06-05 11:00:00'));
    expect($breach->fresh()->isReportOverdue())->toBeTrue()
        ->and(DataRights::check()->hasError('breach_report_overdue'))->toBeTrue();

    $this->allowDashboard();
    $admin = $this->makeUser('admin@shop.example');
    $this->actingAs($admin)->get('/data-rights-desk')->assertOk()->assertSee('1 h overdue');

    DataRights::breaches()->update($breach, ['board_extension_until' => '2027-06-09 10:00:00']);
    expect($breach->fresh()->isReportOverdue())->toBeFalse()
        ->and(DataRights::check()->hasError('breach_report_overdue'))->toBeFalse();

    DataRights::breaches()->markBoardAlerted($breach);
    DataRights::breaches()->markBoardReported($breach);
    expect($breach->fresh()->board_reported_at)->not->toBeNull();
});

it('Rule 7(2): the dashboard records a breach, adds people by email and offers the drafts', function () {
    Notification::fake();
    $this->allowDashboard();
    $admin = $this->makeUser('admin@shop.example');
    $person = $this->makeUser('person@example.com');

    $this->actingAs($admin)->post('/data-rights-desk/breaches', ['title' => 'Stolen laptop', 'nature' => 'Laptop stolen'])->assertRedirect();
    $breach = Breach::query()->sole();

    $this->actingAs($admin)->post("/data-rights-desk/breaches/{$breach->id}/affected", ['emails' => "person@example.com\nunknown@example.com"])
        ->assertRedirect()->assertSessionHas('data-rights.status', 'Added 1 person. 1 email(s) did not match anyone.');
    $this->actingAs($admin)->post("/data-rights-desk/breaches/{$breach->id}/notify")->assertSessionHas('data-rights.status', 'Told 1 person.');
    Notification::assertSentTo($person, BreachNotification::class);

    $this->actingAs($admin)->get("/data-rights-desk/breaches/{$breach->id}")->assertOk()->assertSee('1 / 1');
    $this->actingAs($admin)->get("/data-rights-desk/breaches/{$breach->id}/draft/report")->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    $this->actingAs($admin)->put("/data-rights-desk/breaches/{$breach->id}", ['mitigation' => 'Remote wipe done'])->assertRedirect();
    expect($breach->fresh()->mitigation)->toBe('Remote wipe done')->and($breach->fresh()->nature)->toBe('Laptop stolen');

    $this->actingAs($admin)->post("/data-rights-desk/breaches/{$breach->id}/board-alerted")->assertRedirect();
    $this->actingAs($admin)->post("/data-rights-desk/breaches/{$breach->id}/board-reported")->assertRedirect();
    expect($breach->fresh()->board_alerted_at)->not->toBeNull()->and($breach->fresh()->board_reported_at)->not->toBeNull();
});
