<?php

namespace Vipertecpro\DataRightsDesk\Enums;

enum GuardianMethod: string
{
    /** Rule 10(1)(a): reliable details of identity and age already held. */
    case ReliableDetailsHeld = 'reliable_details_held';

    /** Rule 10(1)(b)(i): details of identity and age voluntarily provided by the individual. */
    case DetailsProvided = 'details_provided';

    /** Rule 10(1)(b)(ii): a virtual token mapped to such details, issued by an authorised entity. */
    case VirtualToken = 'virtual_token';

    /** Rule 11(1): guardian appointed by a court of law. */
    case CourtAppointed = 'court_appointed';

    /** Rule 11(1): guardian appointed by a designated authority. */
    case DesignatedAuthority = 'designated_authority';

    /** Rule 11(1): guardian appointed by a local level committee. */
    case LocalLevelCommittee = 'local_level_committee';

    public function kind(): GuardianKind
    {
        return match ($this) {
            self::ReliableDetailsHeld, self::DetailsProvided, self::VirtualToken => GuardianKind::Parent,
            self::CourtAppointed, self::DesignatedAuthority, self::LocalLevelCommittee => GuardianKind::LawfulGuardian,
        };
    }
}
