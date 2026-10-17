<?php

namespace Vipertecpro\DataRightsDesk\Enums;

enum ErasureReason: string
{
    /** s.12(3): the person asked for erasure. */
    case Request = 'request';

    /** Rule 8(1), Third Schedule: the purpose is deemed no longer served. */
    case Inactivity = 'inactivity';
    /** s.8(7)(a): the person withdrew consent for one purpose (PurposeEraser sources only). */
    case Withdrawal = 'withdrawal';
}
