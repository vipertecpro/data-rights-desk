<?php

namespace Vipertecpro\DataRightsDesk\Enums;

enum RequestType: string
{
    /** s.11: summary of personal data and processing. */
    case Access = 'access';

    /** s.12(2)(a): correction of inaccurate or misleading data. */
    case Correction = 'correction';

    /** s.12(2)(b): completion of incomplete data. */
    case Completion = 'completion';

    /** s.12(2)(c): updating of data. */
    case Updating = 'updating';

    /** s.12(3): erasure. */
    case Erasure = 'erasure';

    /** s.13: grievance redressal. */
    case Grievance = 'grievance';

    public function label(): string
    {
        return match ($this) {
            self::Access => 'Access',
            self::Correction => 'Correction',
            self::Completion => 'Completion',
            self::Updating => 'Updating',
            self::Erasure => 'Erasure',
            self::Grievance => 'Grievance',
        };
    }

    public function reference(): string
    {
        return match ($this) {
            self::Access => 's.11',
            self::Correction => 's.12(2)(a)',
            self::Completion => 's.12(2)(b)',
            self::Updating => 's.12(2)(c)',
            self::Erasure => 's.12(3)',
            self::Grievance => 's.13',
        };
    }
}
