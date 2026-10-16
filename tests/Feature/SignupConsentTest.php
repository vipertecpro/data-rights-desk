<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Validator;
use Vipertecpro\DataRightsDesk\Enums\ConsentChannel;
use Vipertecpro\DataRightsDesk\Facades\DataRights;
use Vipertecpro\DataRightsDesk\Models\ConsentRecord;
use Vipertecpro\DataRightsDesk\Models\Notice;
use Vipertecpro\DataRightsDesk\Rules\IncludesPurpose;

// A sign-up form asks for consent with the notice (s.5(1)), one unticked box per purpose (s.6(1)),
// and keeps proof of the notice version the person saw (s.6(10)).

beforeEach(function () {
    config()->set('data-rights.locales', ['en']);
    config()->set('data-rights.purposes.orders', [
        'title' => 'Your orders', 'description' => 'To deliver what you order.', 'goods_or_services' => 'Delivery.',
        'data' => ['Name', 'Address'], 'basis' => 'legitimate_use', 'legitimate_use' => 'a',
    ]);
    $this->publishNotice();
});

it('s.6(1): the sign-up box shows the notice link and one unticked box per consent purpose', function () {
    $html = Blade::render('<form><x-data-rights::consent-fields :required="[\'account\']" /></form>');

    expect($html)
        ->toContain('Read the privacy notice')
        ->toContain('/data-rights/notice/1')
        ->toContain('name="data_rights[purposes][]" value="account"')
        ->toContain('name="data_rights[purposes][]" value="marketing"')
        ->toContain('name="data_rights[notice]" value="'.Notice::query()->where('locale', 'en')->value('id').'"')
        ->not->toContain('value="orders"')
        ->not->toContain('checked');

    preg_match('/value="account"[^>]*>/', $html, $account);
    preg_match('/value="marketing"[^>]*>/', $html, $marketing);
    expect($account[0])->toContain('required')
        ->and($marketing[0])->not->toContain('required');
});

it('s.6(10): giveFromForm records only the ticked consent purposes, against the notice the form showed', function () {
    $person = $this->makeUser('person@example.com');
    $shown = Notice::query()->where('locale', 'en')->firstOrFail();

    config()->set('data-rights.purposes.marketing.description', 'Changed after the form was shown.');
    expect($this->publishNotice())->toBe(2);

    $request = Request::create('/register', 'POST', ['data_rights' => [
        'purposes' => ['account', 'orders', 'nonsense', 'account'],
        'notice' => $shown->getKey(),
        'locale' => 'en',
    ]]);

    $records = DataRights::giveFromForm($person, $request);

    expect($records)->toHaveCount(1)
        ->and($records[0]->purpose)->toBe('account')
        ->and($records[0]->channel)->toBe(ConsentChannel::Web)
        ->and($records[0]->notice_id)->toBe($shown->getKey())
        ->and(DataRights::allows($person, 'account'))->toBeTrue()
        ->and(DataRights::allows($person, 'marketing'))->toBeFalse()
        ->and(ConsentRecord::query()->count())->toBe(1);
});

it('s.6(10): without a notice id the current notice is recorded', function () {
    $person = $this->makeUser('person@example.com');

    $records = DataRights::giveFromForm($person, Request::create('/register', 'POST', ['data_rights' => ['purposes' => ['marketing']]]));

    expect($records[0]->notice_id)->toBe(DataRights::notices()->current('en')->getKey());
});

it('s.6(10): giveFromForm also takes the input array a Fortify CreateNewUser action receives', function () {
    $person = $this->makeUser('person@example.com');

    $records = DataRights::giveFromForm($person, ['name' => 'P', 'data_rights' => ['purposes' => ['account', 'marketing']]]);

    expect(array_map(fn ($record) => $record->purpose, $records))->toBe(['account', 'marketing']);
});

it('s.6(1): IncludesPurpose stops a form that needs a purpose when the box is not ticked', function () {
    $rules = ['data_rights.purposes' => ['required', 'array', new IncludesPurpose('account')]];

    expect(Validator::make(['data_rights' => ['purposes' => ['marketing']]], $rules)->fails())->toBeTrue()
        ->and(Validator::make(['data_rights' => ['purposes' => ['account']]], $rules)->passes())->toBeTrue();
});
