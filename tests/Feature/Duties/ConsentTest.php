<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Vipertecpro\DataRightsDesk\Enums\ConsentAction;
use Vipertecpro\DataRightsDesk\Enums\ConsentChannel;
use Vipertecpro\DataRightsDesk\Enums\DeliveryChannel;
use Vipertecpro\DataRightsDesk\Enums\GivenBy;
use Vipertecpro\DataRightsDesk\Events\ConsentGiven;
use Vipertecpro\DataRightsDesk\Events\ConsentWithdrawn;
use Vipertecpro\DataRightsDesk\Exceptions\ConsentRecordImmutable;
use Vipertecpro\DataRightsDesk\Exceptions\NoticeMissing;
use Vipertecpro\DataRightsDesk\Exceptions\UnknownPurpose;
use Vipertecpro\DataRightsDesk\Facades\DataRights;
use Vipertecpro\DataRightsDesk\Models\ConsentRecord;
use Vipertecpro\DataRightsDesk\Models\Notice;
use Vipertecpro\DataRightsDesk\Models\NoticeDelivery;

beforeEach(function () {
    $this->publishNotice();
    $this->user = $this->makeUser();
});

it('s.7: a consent purpose needs a given row as the latest ledger entry', function () {
    expect(DataRights::allows($this->user, 'marketing'))->toBeFalse()
        ->and(DataRights::denies($this->user, 'marketing'))->toBeTrue();

    DataRights::give($this->user, 'marketing');
    expect(DataRights::allows($this->user, 'marketing'))->toBeTrue();

    DataRights::withdraw($this->user, 'marketing');
    expect(DataRights::allows($this->user, 'marketing'))->toBeFalse();

    DataRights::give($this->user, 'marketing');
    expect(DataRights::allows($this->user, 'marketing'))->toBeTrue()
        ->and($this->user->consentsTo('marketing'))->toBeTrue()
        ->and(DataRights::consents()->current($this->user))->toMatchArray(['marketing' => true, 'account' => false]);
});

it('s.7: legitimate uses under clauses b to i are allowed without consent', function () {
    config()->set('data-rights.purposes.tax_records', [
        'title' => 'Tax records', 'description' => 'Keeping invoices the law asks for.', 'goods_or_services' => 'Invoices',
        'data' => ['Name', 'Address'], 'basis' => 'legitimate_use', 'legitimate_use' => 'd',
    ]);

    expect(DataRights::allows($this->user, 'tax_records'))->toBeTrue();

    DataRights::withdraw($this->user, 'tax_records');

    expect(DataRights::allows($this->user, 'tax_records'))->toBeTrue();
});

it('s.7: clause (a) is allowed until the person objects with withdraw', function () {
    config()->set('data-rights.purposes.delivery', [
        'title' => 'Delivery updates', 'description' => 'Sending order updates to the phone you gave.', 'goods_or_services' => 'SMS updates',
        'data' => ['Phone number'], 'basis' => 'legitimate_use', 'legitimate_use' => 'a',
    ]);

    expect(DataRights::allows($this->user, 'delivery'))->toBeTrue();

    DataRights::withdraw($this->user, 'delivery');
    expect(DataRights::allows($this->user, 'delivery'))->toBeFalse();

    DataRights::give($this->user, 'delivery');
    expect(DataRights::allows($this->user, 'delivery'))->toBeTrue();
});

it('s.7: an unknown purpose throws UnknownPurpose', function () {
    DataRights::allows($this->user, 'nope');
})->throws(UnknownPurpose::class);

it('s.5(1): consent cannot be recorded before a notice is published in that language', function () {
    config()->set('data-rights.locales', ['en', 'hi']);
    Notice::query()->where('locale', 'hi')->delete();

    DataRights::give($this->user, 'marketing', ['locale' => 'hi']);
})->throws(NoticeMissing::class);

it('s.5(3): a posted language that is not offered falls back to the default notice', function () {
    $record = DataRights::give($this->user, 'marketing', ['locale' => 'ta']);

    expect($record->locale)->toBe('en');
});

it('s.6(1): consent is recorded per purpose only from an explicit POST by the person', function () {
    $this->actingAs($this->user)->get('/data-rights/preferences')->assertOk();
    expect(ConsentRecord::query()->count())->toBe(0);

    $this->actingAs($this->user)->post('/data-rights/consents/marketing')->assertRedirect('/data-rights/preferences');

    $rows = ConsentRecord::query()->get();
    expect($rows)->toHaveCount(1)
        ->and($rows[0]->purpose)->toBe('marketing')
        ->and($rows[0]->action)->toBe(ConsentAction::Given)
        ->and($rows[0]->channel)->toBe(ConsentChannel::Web)
        ->and($rows[0]->given_by)->toBe(GivenBy::DataPrincipal)
        ->and($rows[0]->notice_hash)->toBe(DataRights::notices()->current('en')->hash)
        ->and(DataRights::allows($this->user, 'account'))->toBeFalse()
        ->and(DataRights::allows($this->user, 'analytics'))->toBeFalse();
});

it('s.6(1): the consent page has nothing pre-ticked and no accept-all control', function () {
    $html = $this->actingAs($this->user)->get('/data-rights/preferences')->assertOk()->getContent();

    expect($html)->not->toContain('checked')
        ->and(strtolower($html))->not->toContain('accept all')
        ->and(strtolower($html))->not->toContain('allow all')
        ->and(substr_count($html, 'action="http://localhost/data-rights/consents/'))->toBe(3)
        ->and(substr_count($html, '>Give consent<'))->toBe(3);
});

it('s.6(4): withdrawing is one request of the same shape on the same page', function () {
    $this->actingAs($this->user)->post('/data-rights/consents/marketing')->assertRedirect('/data-rights/preferences');

    $this->actingAs($this->user)->get('/data-rights/preferences')
        ->assertSee('action="http://localhost/data-rights/consents/marketing"', false)
        ->assertSee('name="_method" value="DELETE"', false)
        ->assertSee('Withdraw consent');

    $this->actingAs($this->user)->delete('/data-rights/consents/marketing')->assertRedirect('/data-rights/preferences');

    expect(DataRights::allows($this->user, 'marketing'))->toBeFalse()
        ->and(DataRights::consents()->latest($this->user, 'marketing')->action)->toBe(ConsentAction::Withdrawn);
});

it('s.6(6): ConsentWithdrawn fires with the subject and the purpose', function () {
    Event::fake([ConsentGiven::class, ConsentWithdrawn::class]);

    DataRights::give($this->user, 'marketing');
    DataRights::withdraw($this->user, 'marketing');

    Event::assertDispatched(ConsentGiven::class, fn (ConsentGiven $e) => $e->subject->is($this->user) && $e->purpose === 'marketing');
    Event::assertDispatched(ConsentWithdrawn::class, fn (ConsentWithdrawn $e) => $e->subject->is($this->user)
        && $e->purpose === 'marketing'
        && $e->record->action === ConsentAction::Withdrawn);
});

it('s.6(7): Consent Manager records carry the manager and reference and count in allows()', function () {
    $record = DataRights::consents()->recordFromConsentManager($this->user, 'marketing', ConsentAction::Given, 'CM-REG-0001', 'artefact-42', now()->subMinute());

    expect($record->channel)->toBe(ConsentChannel::ConsentManager)
        ->and($record->consent_manager)->toBe('CM-REG-0001')
        ->and($record->external_reference)->toBe('artefact-42')
        ->and(DataRights::allows($this->user, 'marketing'))->toBeTrue();

    DataRights::consents()->recordFromConsentManager($this->user, 'marketing', ConsentAction::Withdrawn, 'CM-REG-0001', 'artefact-43');

    expect(DataRights::allows($this->user, 'marketing'))->toBeFalse();
});

it('s.6(10): proof holds every ledger row, the notice text and hash each row references, and deliveries', function () {
    DataRights::give($this->user, 'marketing');
    DataRights::withdraw($this->user, 'marketing');
    DataRights::give($this->user, 'account');
    $notice = DataRights::notices()->current('en');
    NoticeDelivery::query()->create([
        'notice_id' => $notice->id, 'subject_type' => $this->user->getMorphClass(), 'subject_id' => $this->user->id,
        'channel' => DeliveryChannel::Legacy, 'delivered_at' => now(),
    ]);

    $proof = DataRights::proof($this->user);

    expect($proof['consents'])->toHaveCount(3)
        ->and(array_column($proof['consents'], 'action'))->toBe(['given', 'withdrawn', 'given'])
        ->and($proof['notices'])->toHaveCount(1)
        ->and($proof['notices'][0]['body'])->toBe($notice->body)
        ->and($proof['notices'][0]['hash'])->toBe(hash('sha256', $notice->body))
        ->and($proof['notices'][0]['hash_matches_body'])->toBeTrue()
        ->and($proof['consents'][0]['notice_hash'])->toBe($notice->hash)
        ->and($proof['deliveries'])->toHaveCount(1);

    expect(DataRights::proof($this->user, 'marketing')['consents'])->toHaveCount(2);

    $file = sys_get_temp_dir().'/drd-proof-'.uniqid().'.json';
    $this->artisan('data-rights:proof', ['email' => 'person@example.com', '--purpose' => 'marketing', '--output' => $file])->assertExitCode(0);
    expect(json_decode(File::get($file), true)['consents'])->toHaveCount(2);
    File::delete($file);

    $this->artisan('data-rights:proof', ['email' => 'nobody@example.com'])->assertExitCode(1);
});

it('s.6(10): ledger rows cannot be changed or deleted', function () {
    $record = DataRights::give($this->user, 'marketing');

    expect(fn () => $record->update(['action' => ConsentAction::Withdrawn]))->toThrow(ConsentRecordImmutable::class)
        ->and(fn () => $record->delete())->toThrow(ConsentRecordImmutable::class)
        ->and(ConsentRecord::query()->count())->toBe(1);
});

it('s.5(2): consents given before the package are imported as legacy rows', function () {
    $givenAt = now()->subYears(2);

    $record = DataRights::consents()->importLegacy($this->user, 'marketing', $givenAt);

    expect($record->channel)->toBe(ConsentChannel::Legacy)
        ->and($record->notice_id)->toBeNull()
        ->and($record->occurred_at->timestamp)->toBe($givenAt->timestamp)
        ->and(DataRights::allows($this->user, 'marketing'))->toBeTrue();
});
