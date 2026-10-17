<?php

namespace Vipertecpro\DataRightsDesk\Support;

/**
 * The gazette texts this version of the package follows. When the law changes, a new package
 * release updates this list, the limits in Limits and FourthSchedule, and the notice text; the
 * checker then reports each app's published notice as out of date until it publishes a new one.
 */
final class LegalSources
{
    /** The day the sources below were last checked against the gazette. */
    public const CHECKED_ON = '2026-10-17';

    /** @var list<string> */
    public const SOURCES = [
        'Digital Personal Data Protection Act, 2023 (Act No. 22 of 2023)',
        'Digital Personal Data Protection Rules, 2025, G.S.R. 846(E) of 13 Nov 2025',
        'Corrigendum G.S.R. 892(E) of 10 Dec 2025',
        'Removal of Difficulties Order S.O. 5458(E) of 5 Oct 2026 (wording of section 9(1))',
    ];

    public static function summary(): string
    {
        return 'Follows: '.implode('; ', self::SOURCES).'. Checked against the gazette on '.self::CHECKED_ON.'.';
    }
}
