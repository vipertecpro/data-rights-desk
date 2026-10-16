<?php

// Fixture scanned by the checker: the code checks the 'marketing' purpose only.

use Vipertecpro\DataRightsDesk\Facades\DataRights;

class NewsletterController
{
    public function send($user): bool
    {
        return DataRights::allows($user, 'marketing');
    }
}
