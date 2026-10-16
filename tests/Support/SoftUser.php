<?php

namespace Vipertecpro\DataRightsDesk\Tests\Support;

use Illuminate\Database\Eloquent\SoftDeletes;

/** A person model that soft-deletes, for the restore case. */
class SoftUser extends User
{
    use SoftDeletes;
}
