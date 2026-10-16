<?php

namespace Vipertecpro\DataRightsDesk\Enums;

enum BreachDraftKind: string
{
    /** Rule 7(2)(a): the first intimation to the Board, without delay. */
    case Alert = 'alert';

    /** Rule 7(2)(b): the detailed report within 72 hours. */
    case Report = 'report';

    /** Rule 7(1): the message to each affected person. */
    case Person = 'person';
}
