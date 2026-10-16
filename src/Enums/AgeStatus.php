<?php

namespace Vipertecpro\DataRightsDesk\Enums;

/** Only the age status is stored, never a birth date (data minimisation). */
enum AgeStatus: string
{
    case Adult = 'adult';
    case Child = 'child';
}
