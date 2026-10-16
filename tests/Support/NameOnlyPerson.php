<?php

namespace Vipertecpro\DataRightsDesk\Tests\Support;

use Illuminate\Database\Eloquent\Model;

/** A user model whose table holds only a name, for the checker test. */
class NameOnlyPerson extends Model
{
    protected $table = 'drd_people';
}
