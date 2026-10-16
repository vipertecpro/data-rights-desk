<?php

namespace Vipertecpro\DataRightsDesk\Support;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Vipertecpro\DataRightsDesk\Enums\GuardianKind;
use Vipertecpro\DataRightsDesk\Enums\GuardianMethod;

/**
 * The result of a successful parent or lawful guardian check (Rules 10 and 11).
 */
final readonly class GuardianVerification
{
    public function __construct(
        public GuardianKind $kind,
        public GuardianMethod $method,
        public ?Model $guardian = null,
        public ?string $guardianName = null,
        public ?string $verificationReference = null,
    ) {
        if ($method->kind() !== $kind) {
            throw new InvalidArgumentException("Method [{$method->value}] cannot verify a [{$kind->value}]: Rule 10 methods are for parents, Rule 11 methods for lawful guardians.");
        }
    }
}
