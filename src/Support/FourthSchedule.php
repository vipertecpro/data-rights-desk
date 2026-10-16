<?php

namespace Vipertecpro\DataRightsDesk\Support;

/**
 * Rule 12 and the Fourth Schedule of the DPDP Rules, 2025 (G.S.R. 846(E)).
 *
 * A purpose marked with one of these keys skips the checks of section 9(1)
 * (verifiable parental consent) and section 9(3) (no tracking, behavioural
 * monitoring or targeted advertising) for children. The developer declares
 * that the condition below is met; the package cannot check it.
 *
 * Part A names classes of Data Fiduciaries, Part B names purposes. The text is
 * quoted from the Schedule.
 */
final class FourthSchedule
{
    /**
     * @var array<string, array{part: string, applies_to: string, condition: string}>
     */
    public const ENTRIES = [
        'A1' => [
            'part' => 'A',
            'applies_to' => 'A Data Fiduciary who is a clinical establishment, mental health establishment or healthcare professional.',
            'condition' => 'Processing is restricted to provision of health services to the child by such establishment or professional, to the extent necessary for the protection of her health.',
        ],
        'A2' => [
            'part' => 'A',
            'applies_to' => 'A Data Fiduciary who is an allied healthcare professional.',
            'condition' => 'Processing is restricted to supporting implementation of any healthcare treatment and referral plan recommended by such professional for the child, to the extent necessary for the protection of her health.',
        ],
        'A3' => [
            'part' => 'A',
            'applies_to' => 'A Data Fiduciary who is an educational institution.',
            'condition' => 'Processing is restricted to tracking and behavioural monitoring— (a) for the educational activities of such institution; or (b) in the interests of safety of children enrolled with such institution.',
        ],
        'A4' => [
            'part' => 'A',
            'applies_to' => 'A Data Fiduciary who is an individual in whose care infants and children in a crèche or child day care centre are entrusted.',
            'condition' => 'Processing is restricted to tracking and behavioural monitoring in the interests of safety of children entrusted in the care of such institution, crèche or centre.',
        ],
        'A5' => [
            'part' => 'A',
            'applies_to' => 'A Data Fiduciary who is engaged by an educational institution, crèche or child care centre for transport of children enrolled with such institution, crèche or centre.',
            'condition' => 'Processing is restricted to tracking the location of such children, in the interests of their safety, during the course of their travel to and from such institution, crèche or centre.',
        ],
        'B1' => [
            'part' => 'B',
            'applies_to' => 'For the exercise of any power, performance of any function or discharge of any duties in the interests of a child, under any law for the time being in force in India.',
            'condition' => 'Processing is restricted to the extent necessary for such exercise, performance or discharge.',
        ],
        'B2' => [
            'part' => 'B',
            'applies_to' => 'For providing or issuing of any subsidy, benefit, service, certificate, licence or permit, by whatever name called, under law or policy or using public funds, in the interests of a child, under clause (b) of section 7 of the Act.',
            'condition' => 'Processing is restricted to the extent necessary for such provision or issuance.',
        ],
        'B3' => [
            'part' => 'B',
            'applies_to' => 'For the creation of a user account for communicating by email.',
            'condition' => 'Processing is restricted to the extent necessary for creating such user account, the use of which is limited to communication by email.',
        ],
        'B4' => [
            'part' => 'B',
            'applies_to' => 'For the determination of real-time location of a child.',
            'condition' => 'Processing is restricted to the tracking of real-time location of such child, in the interest of her safety and protection or security.',
        ],
        'B5' => [
            'part' => 'B',
            'applies_to' => 'For ensuring that any information, service or advertisement likely to cause any detrimental effect on the well-being of a child is not accessible to her.',
            'condition' => 'Processing is restricted to the extent necessary to ensure that such information, service or advertisement is not accessible to the child.',
        ],
        'B6' => [
            'part' => 'B',
            'applies_to' => 'For confirmation by the Data Fiduciary that the Data Principal is not a child and observance of due diligence under rule 10.',
            'condition' => 'Processing is restricted to the extent necessary for such confirmation or observance.',
        ],
    ];

    public static function has(string $key): bool
    {
        return array_key_exists(strtoupper($key), self::ENTRIES);
    }

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::ENTRIES);
    }

    public static function condition(string $key): ?string
    {
        return self::ENTRIES[strtoupper($key)]['condition'] ?? null;
    }

    public static function appliesTo(string $key): ?string
    {
        return self::ENTRIES[strtoupper($key)]['applies_to'] ?? null;
    }

    public static function part(string $key): ?string
    {
        return self::ENTRIES[strtoupper($key)]['part'] ?? null;
    }
}
