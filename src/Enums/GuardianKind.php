<?php

namespace Vipertecpro\DataRightsDesk\Enums;

enum GuardianKind: string
{
    /** s.9(1), Rule 10: parent of a child. */
    case Parent = 'parent';

    /** s.9(1), Rule 11: lawful guardian of a person with disability. */
    case LawfulGuardian = 'lawful_guardian';

    public function givenBy(): GivenBy
    {
        return match ($this) {
            self::Parent => GivenBy::Parent,
            self::LawfulGuardian => GivenBy::LawfulGuardian,
        };
    }
}
