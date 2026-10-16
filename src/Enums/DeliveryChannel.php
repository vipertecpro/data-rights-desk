<?php

namespace Vipertecpro\DataRightsDesk\Enums;

enum DeliveryChannel: string
{
    /** s.5(2): the notice to a person who consented before the Act's duties began. */
    case Legacy = 'legacy';

    /** A later version of the notice sent to a person who already had one. */
    case Update = 'update';
}
