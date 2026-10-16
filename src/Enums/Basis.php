<?php

namespace Vipertecpro\DataRightsDesk\Enums;

enum Basis: string
{
    /** s.6: consent of the Data Principal. */
    case Consent = 'consent';

    /** s.7: certain legitimate uses (clauses a to i). */
    case LegitimateUse = 'legitimate_use';
}
