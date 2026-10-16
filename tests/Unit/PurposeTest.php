<?php

use Vipertecpro\DataRightsDesk\Enums\Basis;
use Vipertecpro\DataRightsDesk\Exceptions\InvalidPurposeDefinition;
use Vipertecpro\DataRightsDesk\Facades\DataRights;
use Vipertecpro\DataRightsDesk\Support\Purpose;

it('reads a purpose with plain strings as the default locale', function () {
    $purpose = Purpose::fromConfig('orders', [
        'title' => 'Orders',
        'description' => 'Delivering what you buy.',
        'goods_or_services' => 'Delivery',
        'data' => ['Address', 'Phone'],
        'basis' => 'legitimate_use',
        'legitimate_use' => 'A',
    ], 'en');

    expect($purpose->title('hi'))->toBe('Orders')
        ->and($purpose->data())->toBe(['Address', 'Phone'])
        ->and($purpose->basis)->toBe(Basis::LegitimateUse)
        ->and($purpose->legitimateUse)->toBe('a')
        ->and($purpose->canBeObjectedTo())->toBeTrue()
        ->and($purpose->missingFor('hi'))->toBe(['purposes.orders.title', 'purposes.orders.description', 'purposes.orders.goods_or_services', 'purposes.orders.data'])
        ->and($purpose->missingFor('en'))->toBe([]);
});

it('rejects invalid purpose definitions', function (string $key, array $definition) {
    Purpose::fromConfig($key, $definition, 'en');
})->throws(InvalidPurposeDefinition::class)->with([
    'bad key' => ['Bad Key', []],
    'bad basis' => ['x', ['basis' => 'contract']],
    'legitimate use without clause' => ['x', ['basis' => 'legitimate_use']],
    'clause out of range' => ['x', ['basis' => 'legitimate_use', 'legitimate_use' => 'j']],
    'unknown schedule key' => ['x', ['fourth_schedule' => 'C1']],
]);

it('lists purposes per locale and the consent-based ones', function () {
    expect(array_keys(DataRights::purposes()->consentBased()))->toBe(['account', 'marketing', 'analytics'])
        ->and(DataRights::purposes()->forLocale('hi')['marketing']['title'])->toBe(config('data-rights.purposes.marketing.title.hi'))
        ->and(DataRights::purposes()->forLocale('hi')['analytics']['tracking'])->toBeTrue();
});
