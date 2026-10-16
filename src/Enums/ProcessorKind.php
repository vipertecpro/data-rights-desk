<?php

namespace Vipertecpro\DataRightsDesk\Enums;

enum ProcessorKind: string
{
    /** s.8(2): a Data Processor engaged under a valid contract. */
    case Processor = 'processor';

    /** s.11(1)(b): another Data Fiduciary the data is shared with. */
    case Fiduciary = 'fiduciary';

    public function label(): string
    {
        return match ($this) {
            self::Processor => 'Data Processor',
            self::Fiduciary => 'Data Fiduciary',
        };
    }
}
