<?php

namespace Vipertecpro\DataRightsDesk\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Vipertecpro\DataRightsDesk\Facades\DataRights;
use Vipertecpro\DataRightsDesk\Models\ConsentRecord;
use Vipertecpro\DataRightsDesk\Models\DataRightsRequest;
use Vipertecpro\DataRightsDesk\Models\SubjectProfile;
use Vipertecpro\DataRightsDesk\Support\Subjects;

/**
 * Add to the Data Principal model (usually App\Models\User).
 *
 * @mixin Model
 */
trait HasDataRights
{
    public static function bootHasDataRights(): void
    {
        $markErased = function (Model $model): void {
            $profile = SubjectProfile::for($model);

            if ($profile->erased_at === null) {
                $profile->forceFill(['erased_at' => now()])->save();
            }
        };

        static::deleted($markErased);
        static::registerModelEvent('forceDeleted', $markErased);

        // A soft-deleted person who is restored is no longer erased.
        static::registerModelEvent('restored', function (Model $model): void {
            SubjectProfile::clearErasure($model);
        });
    }

    /** @return MorphMany<ConsentRecord, $this> */
    public function dataRightsConsents(): MorphMany
    {
        return $this->morphMany(ConsentRecord::class, 'subject');
    }

    /** @return MorphMany<DataRightsRequest, $this> */
    public function dataRightsRequests(): MorphMany
    {
        return $this->morphMany(DataRightsRequest::class, 'subject');
    }

    /** @return MorphOne<SubjectProfile, $this> */
    public function dataRightsProfile(): MorphOne
    {
        return $this->morphOne(SubjectProfile::class, 'subject');
    }

    public function consentsTo(string $purpose): bool
    {
        return DataRights::allows($this, $purpose);
    }

    /** Shown only on the dashboard. */
    public function dataRightsLabel(): string
    {
        return Subjects::email($this) ?? (string) $this->getKey();
    }

    /** The person's language for notices and messages; null uses the default. */
    public function dataRightsLocale(): ?string
    {
        return null;
    }
}
