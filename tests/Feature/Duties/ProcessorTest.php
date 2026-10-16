<?php

use Vipertecpro\DataRightsDesk\Enums\ProcessorKind;
use Vipertecpro\DataRightsDesk\Facades\DataRights;
use Vipertecpro\DataRightsDesk\Models\Processor;

it('s.8(2): the processor register keeps contract terms and the checker warns when they are missing', function () {
    $this->allowDashboard();
    $admin = $this->makeUser('admin@shop.example');

    $this->actingAs($admin)->post('/data-rights-desk/processors', [
        'name' => 'Mail Sender Ltd',
        'kind' => 'processor',
        'description' => 'Sends our emails.',
        'purposes' => ['marketing'],
        'data_shared' => "Email address\nName",
        'contract_reference' => 'DPA-2027-01',
        'contract_date' => '2027-01-10',
        'security_clause' => '1',
        'country' => 'India',
        'hidden_from_access_summary' => '0',
        'active' => '1',
    ])->assertRedirect('/data-rights-desk/processors');

    $processor = Processor::query()->sole();

    expect($processor->kind)->toBe(ProcessorKind::Processor)
        ->and($processor->purposes)->toBe(['marketing'])
        ->and($processor->data_shared)->toBe(['Email address', 'Name'])
        ->and($processor->security_clause)->toBeTrue()
        ->and(DataRights::check()->hasWarning('processor_contract'))->toBeFalse();

    $this->actingAs($admin)->put('/data-rights-desk/processors/'.$processor->id, [
        'name' => 'Mail Sender Ltd', 'kind' => 'processor', 'purposes' => ['marketing'],
        'contract_reference' => '', 'security_clause' => '0', 'active' => '1',
    ])->assertRedirect('/data-rights-desk/processors');

    expect(DataRights::check()->hasWarning('processor_contract'))->toBeTrue();

    $this->actingAs($admin)->get('/data-rights-desk/processors')->assertOk()->assertSee('No security clause');
    $this->actingAs($admin)->get('/data-rights-desk/processors/'.$processor->id.'/edit')->assertOk()->assertSee('Mail Sender Ltd');
});
