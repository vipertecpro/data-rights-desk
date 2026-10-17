<?php

namespace Vipertecpro\DataRightsDesk\Tests\Support;

/** A person whose model has no mail route, as when the email lives in another column. */
class NoMailRouteUser extends User
{
    protected $table = 'users';

    public function routeNotificationForMail(): ?string
    {
        return null;
    }
}
