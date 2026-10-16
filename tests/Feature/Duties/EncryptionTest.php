<?php

use Illuminate\Support\Facades\DB;
use Vipertecpro\DataRightsDesk\Enums\GuardianKind;
use Vipertecpro\DataRightsDesk\Enums\GuardianMethod;
use Vipertecpro\DataRightsDesk\Enums\RequestStatus;
use Vipertecpro\DataRightsDesk\Enums\RequestType;
use Vipertecpro\DataRightsDesk\Facades\DataRights;
use Vipertecpro\DataRightsDesk\Support\GuardianVerification;

it('Rule 6: free-text fields are stored encrypted', function () {
    $user = $this->makeUser();

    $request = DataRights::requests()->open(RequestType::Correction, $user, 'My address is 12 Lake Road');
    DataRights::requests()->verifyIdentity($request, 'Answered a check sent to the account');
    DataRights::requests()->transition($request, RequestStatus::InProgress, null, 'Called the person back');
    DataRights::requests()->reject($request, 'We could not find 12 Lake Road');
    DataRights::nominations()->nominate($user, 'Meera Iyer', 'meera@example.com', 'Sister');
    DataRights::children()->recordGuardianConsent($user, new GuardianVerification(GuardianKind::Parent, GuardianMethod::DetailsProvided, null, 'Ravi Iyer', 'token-998'));
    DataRights::breaches()->record(['title' => 'Lost laptop', 'nature' => 'Laptop with customer list stolen', 'extent' => '120 customers', 'safety_steps' => 'Watch for phishing']);
    DataRights::retention()->placeHold($user, 'Court order 55/2027');
    DataRights::children()->changeAge($user, true, 'Typed the wrong answer');

    $raw = fn (string $table, string $column) => (string) DB::table($table)->value($column);

    foreach ([
        ['data_rights_requests', 'details', 'My address is 12 Lake Road'],
        ['data_rights_requests', 'response', 'We could not find 12 Lake Road'],
        ['data_rights_requests', 'identity_method', 'Answered a check sent to the account'],
        ['data_rights_subjects', 'age_note', 'Typed the wrong answer'],
        ['data_rights_nominations', 'nominee_name', 'Meera Iyer'],
        ['data_rights_nominations', 'nominee_contact', 'meera@example.com'],
        ['data_rights_nominations', 'relationship', 'Sister'],
        ['data_rights_guardian_consents', 'guardian_name', 'Ravi Iyer'],
        ['data_rights_guardian_consents', 'verification_reference', 'token-998'],
        ['data_rights_breaches', 'nature', 'Laptop with customer list stolen'],
        ['data_rights_breaches', 'extent', '120 customers'],
        ['data_rights_breaches', 'safety_steps', 'Watch for phishing'],
        ['data_rights_legal_holds', 'reason', 'Court order 55/2027'],
    ] as [$table, $column, $plain]) {
        expect($raw($table, $column))->not->toBe($plain)->not->toContain($plain)->not->toBe('');
    }

    $note = DB::table('data_rights_request_events')->whereNotNull('note')->value('note');
    expect((string) $note)->not->toContain('Called the person back');

    expect($request->fresh()->details)->toBe('My address is 12 Lake Road');
});
