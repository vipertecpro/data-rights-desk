<?php

namespace Vipertecpro\DataRightsDesk\Nominations;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Vipertecpro\DataRightsDesk\Models\Nomination;

/**
 * s.14, Rule 14(4): a person may nominate one or more individuals to exercise
 * her rights in the event of death or incapacity.
 */
class NominationBook
{
    public function nominate(Model $subject, string $name, string $contact, ?string $relationship = null): Nomination
    {
        return Nomination::query()->create([
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'nominee_name' => $name,
            'nominee_contact' => $contact,
            'relationship' => filled($relationship) ? $relationship : null,
        ]);
    }

    /** Removing a nomination deletes it, so the nominee's details are not kept (data minimisation). */
    public function revoke(Nomination $nomination): void
    {
        $nomination->delete();
    }

    /** @return Collection<int, Nomination> */
    public function for(Model $subject): Collection
    {
        return Nomination::query()->forSubject($subject)->active()->orderBy('created_at')->get();
    }
}
