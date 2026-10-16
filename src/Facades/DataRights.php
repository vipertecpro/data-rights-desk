<?php

namespace Vipertecpro\DataRightsDesk\Facades;

use Illuminate\Support\Facades\Facade;
use Vipertecpro\DataRightsDesk\DataRightsManager;

/**
 * @method static bool allows(\Illuminate\Database\Eloquent\Model $subject, string $purpose)
 * @method static bool denies(\Illuminate\Database\Eloquent\Model $subject, string $purpose)
 * @method static \Vipertecpro\DataRightsDesk\Models\ConsentRecord give(\Illuminate\Database\Eloquent\Model $subject, string $purpose, array<string, mixed> $options = [])
 * @method static \Vipertecpro\DataRightsDesk\Models\ConsentRecord withdraw(\Illuminate\Database\Eloquent\Model $subject, string $purpose, array<string, mixed> $options = [])
 * @method static void logProcessing(\Illuminate\Database\Eloquent\Model $subject, string $purpose, string $activity)
 * @method static array<string, mixed> proof(\Illuminate\Database\Eloquent\Model $subject, ?string $purpose = null)
 * @method static array<string, string|null> contact()
 * @method static \Vipertecpro\DataRightsDesk\Purposes\PurposeRegistry purposes()
 * @method static \Vipertecpro\DataRightsDesk\Consents\ConsentLedger consents()
 * @method static \Vipertecpro\DataRightsDesk\Notices\NoticeRepository notices()
 * @method static \Vipertecpro\DataRightsDesk\Requests\RequestDesk requests()
 * @method static \Vipertecpro\DataRightsDesk\Nominations\NominationBook nominations()
 * @method static \Vipertecpro\DataRightsDesk\Breaches\BreachDesk breaches()
 * @method static \Vipertecpro\DataRightsDesk\Children\ChildGuard children()
 * @method static \Vipertecpro\DataRightsDesk\Retention\RetentionKeeper retention()
 * @method static \Vipertecpro\DataRightsDesk\Support\CheckReport check()
 *
 * @see DataRightsManager
 */
class DataRights extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DataRightsManager::class;
    }
}
