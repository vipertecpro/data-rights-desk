<?php

namespace Vipertecpro\DataRightsDesk\Enums;

enum GivenBy: string
{
    /** The Data Principal herself. */
    case DataPrincipal = 'self';

    case Parent = 'parent';
    case LawfulGuardian = 'lawful_guardian';
}
