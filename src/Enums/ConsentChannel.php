<?php

namespace Vipertecpro\DataRightsDesk\Enums;

enum ConsentChannel: string
{
    case Web = 'web';
    case Api = 'api';
    case Admin = 'admin';
    case ConsentManager = 'consent_manager';
    case Legacy = 'legacy';
    case Import = 'import';
}
