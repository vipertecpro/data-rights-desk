<?php

namespace Vipertecpro\DataRightsDesk\Support;

use Illuminate\Database\Schema\Blueprint;

/**
 * The package's columns that point at a person or a staff member. Their id type follows
 * data-rights.morph_key_type, so an app with UUID or ULID user keys does not have to change
 * Laravel's morph type for its whole schema. Null keeps Laravel's default.
 */
final class Morphs
{
    public static function add(Blueprint $table, string $name, bool $nullable = false): void
    {
        $type = config('data-rights.morph_key_type');

        match ([$type, $nullable]) {
            ['uuid', false] => $table->uuidMorphs($name),
            ['uuid', true] => $table->nullableUuidMorphs($name),
            ['ulid', false] => $table->ulidMorphs($name),
            ['ulid', true] => $table->nullableUlidMorphs($name),
            ['int', false] => $table->numericMorphs($name),
            ['int', true] => $table->nullableNumericMorphs($name),
            default => $nullable ? $table->nullableMorphs($name) : $table->morphs($name),
        };
    }
}
