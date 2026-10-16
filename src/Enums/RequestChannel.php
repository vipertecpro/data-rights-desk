<?php

namespace Vipertecpro\DataRightsDesk\Enums;

enum RequestChannel: string
{
    case Web = 'web';
    case Api = 'api';
    case Admin = 'admin';
}
