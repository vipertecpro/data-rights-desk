<?php

namespace Vipertecpro\DataRightsDesk\Contracts;

/**
 * s.6(7)-(9): experimental. The Board has published no technical standard for
 * Consent Managers yet, so this only names the Consent Manager.
 */
interface ConsentManagerGateway
{
    /** Registration number or name of the Consent Manager. */
    public function identifier(): string;
}
