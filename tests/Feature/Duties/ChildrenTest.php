<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Vipertecpro\DataRightsDesk\Contracts\ParentalConsentVerifier;
use Vipertecpro\DataRightsDesk\Enums\AgeStatus;
use Vipertecpro\DataRightsDesk\Enums\GivenBy;
use Vipertecpro\DataRightsDesk\Enums\GuardianKind;
use Vipertecpro\DataRightsDesk\Enums\GuardianMethod;
use Vipertecpro\DataRightsDesk\Events\AgeAnswerChanged;
use Vipertecpro\DataRightsDesk\Facades\DataRights;
use Vipertecpro\DataRightsDesk\Models\SubjectProfile;
use Vipertecpro\DataRightsDesk\Support\FourthSchedule;
use Vipertecpro\DataRightsDesk\Support\GuardianVerification;
use Vipertecpro\DataRightsDesk\Tests\Support\AllowingParentVerifier;

beforeEach(function () {
    $this->publishNotice();
    $this->child = $this->makeUser('child@example.com');
});

it('s.9(1): a declared child is not allowed until a guardian consent and a given row by the parent exist', function () {
    DataRights::give($this->child, 'marketing');
    expect(DataRights::allows($this->child, 'marketing'))->toBeTrue();

    $this->actingAs($this->child)->post('/data-rights/age', ['is_adult' => '0'])->assertRedirect('/data-rights/preferences');

    expect(DataRights::children()->isChild($this->child))->toBeTrue()
        ->and(DataRights::children()->needsGuardianConsent($this->child))->toBeTrue()
        ->and(DataRights::allows($this->child, 'marketing'))->toBeFalse();

    $this->actingAs($this->child)->post('/data-rights/consents/marketing')->assertSessionHasErrors('purpose');

    $this->app->bind(ParentalConsentVerifier::class, AllowingParentVerifier::class);
    $this->actingAs($this->child)->post('/data-rights/guardian')->assertRedirect('/data-rights/preferences');

    expect(DataRights::children()->needsGuardianConsent($this->child))->toBeFalse()
        ->and(DataRights::allows($this->child, 'marketing'))->toBeFalse();

    $this->actingAs($this->child)->post('/data-rights/consents/marketing')->assertSessionHasNoErrors();

    $latest = DataRights::consents()->latest($this->child, 'marketing');
    expect($latest->given_by)->toBe(GivenBy::Parent)
        ->and($latest->guardian_consent_id)->toBe(DataRights::children()->activeGuardianConsent($this->child)->id)
        ->and(DataRights::allows($this->child, 'marketing'))->toBeTrue();

    DataRights::children()->revokeGuardianConsent(DataRights::children()->activeGuardianConsent($this->child));
    expect(DataRights::allows($this->child, 'marketing'))->toBeFalse();
});

it('s.9(1): the default parental verifier refuses', function () {
    DataRights::children()->recordAge($this->child, false);

    $this->actingAs($this->child)->from('/data-rights/preferences')->post('/data-rights/guardian')
        ->assertRedirect('/data-rights/preferences')
        ->assertSessionHasErrors(['guardian' => 'Parent and guardian checks are not set up in this app yet. Please contact us.']);

    expect(DataRights::children()->activeGuardianConsent($this->child))->toBeNull();
});

it('s.9(1): an unknown age counts as a child only when the app says so', function () {
    expect(DataRights::children()->isChild($this->child))->toBeFalse();

    config()->set('data-rights.children.unknown_age_is_child', true);
    expect(DataRights::children()->isChild($this->child))->toBeTrue();

    config()->set('data-rights.children.enabled', false);
    expect(DataRights::children()->isChild($this->child))->toBeFalse();
});

it('s.9(3): tracking purposes stay denied for children even with a parent\'s consent', function () {
    DataRights::children()->recordAge($this->child, false);
    DataRights::children()->recordGuardianConsent($this->child, new GuardianVerification(GuardianKind::Parent, GuardianMethod::ReliableDetailsHeld));
    DataRights::give($this->child, 'analytics', ['given_by' => GivenBy::Parent]);
    DataRights::give($this->child, 'marketing', ['given_by' => GivenBy::Parent]);

    expect(DataRights::allows($this->child, 'analytics'))->toBeFalse()
        ->and(DataRights::allows($this->child, 'marketing'))->toBeTrue();

    $this->actingAs($this->child)->get('/data-rights/preferences')->assertOk()->assertSee('Not available for children.');
});

it('s.9(3): mayTrack is false for children and the Blade directive hides tracking code', function () {
    $adult = $this->makeUser('adult@example.com');
    DataRights::children()->recordAge($adult, true);
    DataRights::children()->recordAge($this->child, false);

    expect(DataRights::children()->mayTrack($adult))->toBeTrue()
        ->and(DataRights::children()->mayTrack($this->child))->toBeFalse()
        ->and(DataRights::children()->mayTrack(null))->toBeTrue();

    $template = '@dataRightsTracking<script src="/tracker.js"></script>@endDataRightsTracking';

    $this->actingAs($this->child);
    expect(Blade::render($template))->toBe('');

    $this->actingAs($adult);
    expect(Blade::render($template))->toBe('<script src="/tracker.js"></script>');

    DataRights::give($adult, 'marketing');
    expect(trim(Blade::render("@dataRightsAllows('marketing') yes @endDataRightsAllows")))->toBe('yes')
        ->and(trim(Blade::render("@dataRightsAllows('analytics') yes @endDataRightsAllows")))->toBe('');
});

it('Rule 11: a person with a lawful guardian needs a lawful guardian consent with a Rule 11 method', function () {
    $person = $this->makeUser('ward@example.com');
    SubjectProfile::for($person)->forceFill(['has_lawful_guardian' => true])->save();

    expect(DataRights::children()->guardianKindNeeded($person))->toBe(GuardianKind::LawfulGuardian)
        ->and(fn () => new GuardianVerification(GuardianKind::LawfulGuardian, GuardianMethod::DetailsProvided))->toThrow(InvalidArgumentException::class);

    DataRights::children()->recordGuardianConsent($person, new GuardianVerification(GuardianKind::Parent, GuardianMethod::DetailsProvided));
    DataRights::give($person, 'marketing', ['given_by' => GivenBy::Parent]);
    expect(DataRights::allows($person, 'marketing'))->toBeFalse();

    DataRights::children()->recordGuardianConsent($person, new GuardianVerification(GuardianKind::LawfulGuardian, GuardianMethod::CourtAppointed, null, 'Guardian Name', 'Order 12/2026'));
    expect(DataRights::allows($person, 'marketing'))->toBeFalse();

    DataRights::give($person, 'marketing', ['given_by' => GivenBy::LawfulGuardian]);
    expect(DataRights::allows($person, 'marketing'))->toBeTrue()
        ->and(DataRights::allows($person, 'analytics'))->toBeFalse();
});

it('Rule 12: a purpose with a Fourth Schedule key skips the checks for children', function () {
    config()->set('data-rights.purposes.bus_location', [
        'title' => ['en' => 'School bus location', 'hi' => '[hi] School bus location'],
        'description' => ['en' => 'Showing parents where the school bus is.', 'hi' => '[hi] Showing parents where the school bus is.'],
        'goods_or_services' => ['en' => 'Live bus map', 'hi' => '[hi] Live bus map'],
        'data' => ['en' => ['Location'], 'hi' => ['[hi] Location']],
        'basis' => 'consent',
        'tracking' => true,
        'fourth_schedule' => 'A5',
    ]);
    $this->publishNotice();
    DataRights::children()->recordAge($this->child, false);

    DataRights::give($this->child, 'bus_location');

    expect(DataRights::allows($this->child, 'bus_location'))->toBeTrue()
        ->and(DataRights::allows($this->child, 'analytics'))->toBeFalse()
        ->and(FourthSchedule::condition('A5'))->toStartWith('Processing is restricted to tracking the location of such children')
        ->and(FourthSchedule::keys())->toBe(['A1', 'A2', 'A3', 'A4', 'A5', 'B1', 'B2', 'B3', 'B4', 'B5', 'B6']);
});

it('s.9(1): legitimate uses under s.7(b) to (i) do not wait for a parent, but s.7(a) does', function () {
    config()->set('data-rights.purposes.emergency', [
        'title' => 'Medical emergency', 'description' => 'Helping in a medical emergency.', 'goods_or_services' => 'Emergency help',
        'data' => ['Name'], 'basis' => 'legitimate_use', 'legitimate_use' => 'f',
    ]);
    config()->set('data-rights.purposes.delivery', [
        'title' => 'Delivery', 'description' => 'Delivering orders.', 'goods_or_services' => 'Delivery',
        'data' => ['Address'], 'basis' => 'legitimate_use', 'legitimate_use' => 'a',
    ]);
    DataRights::children()->recordAge($this->child, false);

    expect(DataRights::allows($this->child, 'emergency'))->toBeTrue()
        ->and(DataRights::allows($this->child, 'delivery'))->toBeFalse();

    DataRights::children()->recordGuardianConsent($this->child, new GuardianVerification(GuardianKind::Parent, GuardianMethod::VirtualToken));

    expect(DataRights::allows($this->child, 'delivery'))->toBeTrue();
});

it('s.9(1): the age answer is locked once given, and staff change it with a note', function () {
    Event::fake([AgeAnswerChanged::class]);

    $this->actingAs($this->child)->post('/data-rights/age', ['is_adult' => '0'])->assertSessionHasNoErrors();
    $this->actingAs($this->child)->post('/data-rights/age', ['is_adult' => '1'])
        ->assertRedirect('/data-rights/preferences')
        ->assertSessionHasErrors(['is_adult' => 'To change this, contact us.']);

    expect(DataRights::children()->isChild($this->child))->toBeTrue();

    $this->allowDashboard();
    $admin = $this->makeUser('admin@shop.example');

    $this->actingAs($admin)->post('/data-rights-desk/children', ['email' => 'child@example.com'])
        ->assertOk()->assertSee('Current answer:')->assertSee('under 18');

    $this->actingAs($admin)->post('/data-rights-desk/children/age', ['email' => 'child@example.com', 'is_adult' => '1', 'note' => 'Her parent emailed: she typed the wrong answer.'])
        ->assertRedirect('/data-rights-desk/children')
        ->assertSessionHas('data-rights.status');

    $profile = SubjectProfile::lookup($this->child);
    expect(DataRights::children()->isChild($this->child))->toBeFalse()
        ->and($profile->age_note)->toBe('Her parent emailed: she typed the wrong answer.')
        ->and($profile->age_changed_by_id)->toBe($admin->id)
        ->and((string) DB::table('data_rights_subjects')->value('age_note'))->not->toContain('wrong answer');

    Event::assertDispatched(AgeAnswerChanged::class, fn (AgeAnswerChanged $e) => $e->from === AgeStatus::Child && $e->to === AgeStatus::Adult);

    $this->actingAs($admin)->post('/data-rights-desk/children/age', ['email' => 'nobody@example.com', 'is_adult' => '1', 'note' => 'x'])
        ->assertSessionHasErrors('email');
});
