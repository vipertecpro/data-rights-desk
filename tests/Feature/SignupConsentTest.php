<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Vipertecpro\DataRightsDesk\Enums\ConsentChannel;
use Vipertecpro\DataRightsDesk\Exceptions\UnknownPurpose;
use Vipertecpro\DataRightsDesk\Facades\DataRights;
use Vipertecpro\DataRightsDesk\Models\ConsentRecord;
use Vipertecpro\DataRightsDesk\Models\Notice;
use Vipertecpro\DataRightsDesk\Rules\AnswersAge;
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

it('s.6(1): IncludesPurpose also fails when nothing is ticked, without a separate required rule', function () {
    $rules = ['data_rights.purposes' => [new IncludesPurpose('account')]];

    expect(Validator::make([], $rules)->fails())->toBeTrue()
        ->and(Validator::make(['data_rights' => ['purposes' => ['account']]], $rules)->passes())->toBeTrue();
});

it('s.5(1): IncludesPurpose fails while no notice is published, so no account is created without consent', function () {
    Notice::query()->delete();

    $validator = Validator::make(['data_rights' => ['purposes' => ['account']]], ['data_rights.purposes' => [new IncludesPurpose('account')]]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('data_rights.purposes'))->toContain('not published yet');
});

it('s.9(1): AnswersAge needs an adult or child answer, with the package\'s own message', function () {
    $rules = ['data_rights.age' => [new AnswersAge]];

    $missing = Validator::make([], $rules);

    expect($missing->fails())->toBeTrue()
        ->and($missing->errors()->first('data_rights.age'))->toBe('Please say whether you are 18 or older.')
        ->and(Validator::make(['data_rights' => ['age' => 'maybe']], $rules)->fails())->toBeTrue()
        ->and(Validator::make(['data_rights' => ['age' => 'child']], $rules)->passes())->toBeTrue();
});

it('s.6(1): IncludesPurpose refuses a purpose that is unknown or does not need consent', function () {
    expect(fn () => new IncludesPurpose('acount'))->toThrow(UnknownPurpose::class)
        ->and(fn () => new IncludesPurpose('orders'))->toThrow(InvalidArgumentException::class);
});

it('s.6(1): the sign-up box refuses a purpose that is unknown or does not need consent', function () {
    expect(fn () => Blade::render('<x-data-rights::consent-fields :required="[\'acount\']" />'))->toThrow(Exception::class, 'acount')
        ->and(fn () => Blade::render('<x-data-rights::consent-fields :purposes="[\'orders\']" />'))->toThrow(Exception::class, 'orders');
});

it('s.6(1): the sign-up box shows the validation message for its fields', function () {
    $errors = (new ViewErrorBag)->put('default', new MessageBag(['data_rights.purposes' => ['Please give consent for this use to continue.']]));

    view()->share('errors', $errors); // as Laravel's ShareErrorsFromSession middleware does

    $html = Blade::render('<x-data-rights::consent-fields />');

    expect($html)->toContain('role="alert"')->toContain('Please give consent for this use to continue.');
});

it('s.5(3): the sign-up box uses the notice language for every line', function () {
    config()->set('data-rights.locales', ['en', 'hi']);
    config()->set('data-rights.purposes', array_diff_key(config('data-rights.purposes'), ['orders' => true]));
    $this->publishNotice();

    $html = Blade::render('<x-data-rights::consent-fields locale="hi" />');

    expect($html)->toContain('lang="hi"')
        ->toContain(__('data-rights::public.signup.legend', [], 'hi'))
        ->not->toContain(__('data-rights::public.signup.legend', [], 'en'));
});

it('s.6(10): a posted notice that did not describe the purpose is not used as proof', function () {
    $person = $this->makeUser('person@example.com');
    $old = Notice::query()->where('locale', 'en')->firstOrFail();

    config()->set('data-rights.purposes.newsletter', [
        'title' => 'Newsletter', 'description' => 'Monthly news.', 'goods_or_services' => 'News.',
        'data' => ['Email'], 'basis' => 'consent',
    ]);
    $this->publishNotice();

    $records = DataRights::giveFromForm($person, ['data_rights' => ['purposes' => ['newsletter', 'account'], 'notice' => $old->getKey()]]);

    expect($records[0]->purpose)->toBe('newsletter')
        ->and($records[0]->notice_id)->toBe(DataRights::notices()->current('en')->getKey())
        ->and($records[1]->notice_id)->toBe($old->getKey());
});

it('s.9(1): with ask-age, a child\'s own ticks wait for a parent and are not recorded as consent', function () {
    expect(Blade::render('<x-data-rights::consent-fields ask-age />'))
        ->toContain('name="data_rights[age]" value="adult"')
        ->toContain('name="data_rights[age]" value="child"');

    $child = $this->makeUser('child@example.com');
    $records = DataRights::giveFromForm($child, ['data_rights' => ['age' => 'child', 'purposes' => ['account', 'marketing']]]);

    expect($records)->toBe([])
        ->and(DataRights::children()->isChild($child))->toBeTrue()
        ->and(DataRights::children()->needsGuardianConsent($child))->toBeTrue()
        ->and(ConsentRecord::query()->count())->toBe(0);

    $adult = $this->makeUser('adult@example.com');
    $records = DataRights::giveFromForm($adult, ['data_rights' => ['age' => 'adult', 'purposes' => ['account']]]);

    expect($records)->toHaveCount(1)
        ->and(DataRights::children()->isChild($adult))->toBeFalse();
});

it('s.9(1): an age answer already given is not changed by a form', function () {
    $person = $this->makeUser('person@example.com');
    DataRights::children()->recordAge($person, true);

    DataRights::giveFromForm($person, ['data_rights' => ['age' => 'child', 'purposes' => ['account']]]);

    expect(DataRights::children()->isChild($person))->toBeFalse();
});
