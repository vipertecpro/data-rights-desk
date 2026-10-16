<?php

namespace Vipertecpro\DataRightsDesk\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * s.11(1)(a): gives the personal data one part of the app holds about a person,
 * for the access summary.
 */
interface Exporter
{
    /** Plain name shown in the summary, e.g. "Orders". */
    public function name(): string;

    /**
     * Tables this source covers, for the checker.
     *
     * @return list<string>
     */
    public function tables(): array;

    /** Plain description of the processing activities. */
    public function processing(): string;

    /**
     * Rows of the person's data, JSON-safe.
     *
     * @return array<int|string, mixed>
     */
    public function export(Model $subject): array;
}
