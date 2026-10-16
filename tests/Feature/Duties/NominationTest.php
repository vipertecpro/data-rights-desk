<?php

use Illuminate\Support\Facades\DB;
use Vipertecpro\DataRightsDesk\Enums\ErasureReason;
use Vipertecpro\DataRightsDesk\Facades\DataRights;
use Vipertecpro\DataRightsDesk\Models\Nomination;

it('s.14: a person nominates and revokes, with nominee fields encrypted', function () {
    $person = $this->makeUser();

    $this->actingAs($person)->post('/data-rights/nominations', [
        'nominee_name' => 'Meera Iyer', 'nominee_contact' => '+91 98 0000 0000', 'relationship' => 'Sister',
    ])->assertRedirect('/data-rights/requests');

    $nomination = Nomination::query()->sole();
    $raw = DB::table('data_rights_nominations')->first();

    expect($nomination->nominee_name)->toBe('Meera Iyer')
        ->and($raw->nominee_name)->not->toContain('Meera')
        ->and($raw->nominee_contact)->not->toContain('+91')
        ->and($raw->relationship)->not->toContain('Sister');

    $this->actingAs($person)->get('/data-rights/requests')->assertSee('Meera Iyer');
    $this->actingAs($person)->delete("/data-rights/nominations/{$nomination->id}")->assertRedirect('/data-rights/requests');

    expect($nomination->fresh()->revoked_at)->not->toBeNull()
        ->and(DataRights::nominations()->for($person))->toHaveCount(0);
});

it('s.14: nominations are erased with the person', function () {
    $person = $this->makeUser();
    DataRights::nominations()->nominate($person, 'Meera Iyer', 'meera@example.com');

    $report = DataRights::requests()->eraseSubject($person, ErasureReason::Request);

    expect($report->nominationsDeleted)->toBe(1)
        ->and(Nomination::query()->count())->toBe(0);
});
