<?php

namespace Vipertecpro\DataRightsDesk\Tests\Support;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Vipertecpro\DataRightsDesk\Concerns\HasDataRights;

class User extends Authenticatable
{
    use HasDataRights;
    use Notifiable;

    protected $table = 'users';

    protected $guarded = [];

    public ?string $preferredDataRightsLocale = null;

    public function dataRightsLocale(): ?string
    {
        return $this->preferredDataRightsLocale;
    }
}
