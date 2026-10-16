<?php

namespace Vipertecpro\DataRightsDesk\Exceptions;

/** The consent ledger is append-only (s.6(10)). */
class ConsentRecordImmutable extends DataRightsException
{
    public static function update(mixed $id): self
    {
        return new self('Consent record ['.(is_scalar($id) ? $id : '?').'] cannot be changed. Record a new given or withdrawn row instead.');
    }

    public static function delete(mixed $id): self
    {
        return new self('Consent record ['.(is_scalar($id) ? $id : '?').'] cannot be deleted. Only the retention keeper removes ledger rows.');
    }
}
