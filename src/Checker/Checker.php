<?php

namespace Vipertecpro\DataRightsDesk\Checker;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Throwable;
use Vipertecpro\DataRightsDesk\Concerns\HasRetention;
use Vipertecpro\DataRightsDesk\DataRightsDeskServiceProvider;
use Vipertecpro\DataRightsDesk\Enums\LanguageStatus;
use Vipertecpro\DataRightsDesk\Exceptions\DataRightsException;
use Vipertecpro\DataRightsDesk\Models\Breach;
use Vipertecpro\DataRightsDesk\Models\DataRightsRequest;
use Vipertecpro\DataRightsDesk\Models\Processor;
use Vipertecpro\DataRightsDesk\Notices\NoticeRepository;
use Vipertecpro\DataRightsDesk\Purposes\PurposeRegistry;
use Vipertecpro\DataRightsDesk\Support\CheckReport;
use Vipertecpro\DataRightsDesk\Support\Contact;
use Vipertecpro\DataRightsDesk\Support\Languages;
use Vipertecpro\DataRightsDesk\Support\Limits;
use Vipertecpro\DataRightsDesk\Support\Purpose;
use Vipertecpro\DataRightsDesk\Support\Sources;
use Vipertecpro\DataRightsDesk\Verification\NoParentalConsentVerifier;

/**
 * Looks for gaps in the setup. It cannot tell whether the app meets the Act;
 * it points at the things this package can see.
 */
class Checker
{
    /** @var list<array{code: string, message: string}> */
    private array $errors = [];

    /** @var list<array{code: string, message: string}> */
    private array $warnings = [];

    public function __construct(
        private PurposeRegistry $purposes,
        private NoticeRepository $notices,
    ) {}

    public function run(): CheckReport
    {
        $this->errors = [];
        $this->warnings = [];

        $purposes = $this->checkPurposes();
        $this->checkContact();
        $this->checkLanguages();

        if ($purposes !== null) {
            $this->checkNotices();
        }

        $this->checkRequests();
        $this->checkRetention();
        $this->checkBreaches();
        $this->checkBoard();
        $this->checkProcessors();
        $this->checkChildren();
        $this->checkGate();

        if ($purposes !== null) {
            $this->checkExamplePurposes();
            $this->checkPersonalColumns();
            $this->checkConsentChecks($purposes);
        }

        return new CheckReport($this->errors, $this->warnings);
    }

    /** @return array<string, Purpose>|null */
    private function checkPurposes(): ?array
    {
        try {
            $purposes = $this->purposes->all();
        } catch (DataRightsException $e) {
            $this->error('purposes_invalid', $e->getMessage());

            return null;
        }

        if ($purposes === []) {
            $this->warning('no_purposes', 'No purposes are configured in config/data-rights.php.');
        }

        return $purposes;
    }

    private function checkContact(): void
    {
        if (! Contact::isComplete()) {
            $this->error('contact_missing', 'Rule 9: set DATA_RIGHTS_CONTACT_EMAIL or DATA_RIGHTS_CONTACT_PHONE so people can reach someone who answers questions about the processing.');
        }
    }

    private function checkLanguages(): void
    {
        foreach (Languages::configured() as $locale) {
            if (Languages::status($locale) === LanguageStatus::Needed) {
                $this->error('locale_needed', "s.5(3): locale [{$locale}] is listed in data-rights.locales but has no translation of the package text (status \"needed\"), so it cannot be offered.");
            }
        }

        if (! in_array('hi', Languages::offered(), true)) {
            $this->warning('hindi_not_offered', 's.5(3): Hindi is not offered. People may ask for the notice in any Eighth Schedule language.');
        }
    }

    private function checkNotices(): void
    {
        if ($this->notices->currentVersion() === null) {
            $this->error('notice_missing', 's.5(1): no notice is published. Run php artisan data-rights:notice.');

            return;
        }

        foreach ($this->notices->offeredLocales() as $locale) {
            if ($this->notices->current($locale) === null) {
                $this->error('notice_missing', "s.5(1): no notice is published in [{$locale}]. Run php artisan data-rights:notice.");
            } elseif ($this->notices->isStale($locale)) {
                $this->error('notice_stale', "The published notice in [{$locale}] differs from the current config. Run php artisan data-rights:notice.");
            }
        }
    }

    private function checkRequests(): void
    {
        if ((int) config('data-rights.requests.response_days', Limits::MAX_RESPONSE_DAYS) > Limits::MAX_RESPONSE_DAYS) {
            $this->error('response_days_over_90', 'Rule 14(3): requests.response_days is above 90. The package uses 90.');
        }

        $overdue = DataRightsRequest::query()->overdue()->count();

        if ($overdue > 0) {
            $this->error('requests_overdue', "Rule 14(3): {$overdue} open request(s) are past their due date.");
        }
    }

    private function checkRetention(): void
    {
        if ((int) config('data-rights.retention.keep_days', Limits::MIN_KEEP_DAYS) < Limits::MIN_KEEP_DAYS) {
            $this->warning('keep_days_below_minimum', 'Rule 8(3): retention.keep_days is below 365. The package keeps records for 365 days.');
        }
    }

    private function checkBreaches(): void
    {
        $overdue = Breach::query()->whereNull('board_reported_at')->get()->filter(fn (Breach $breach): bool => $breach->isReportOverdue())->count();

        if ($overdue > 0) {
            $this->error('breach_report_overdue', "Rule 7(2)(b): {$overdue} breach(es) are past the 72-hour report deadline without a report marked as sent.");
        }
    }

    private function checkBoard(): void
    {
        if (blank(config('data-rights.board.url'))) {
            $this->warning('board_url_empty', 'Rule 3(c)(iii): DATA_RIGHTS_BOARD_URL is empty, so notices describe the complaint route without a link.');
        }
    }

    private function checkProcessors(): void
    {
        foreach (Processor::query()->active()->orderBy('name')->get() as $processor) {
            if ($processor->missingContractTerms()) {
                $this->warning('processor_contract', "s.8(2), Rule 6(1)(f): processor [{$processor->name}] has no contract reference or no security clause recorded.");
            }
        }
    }

    private function checkChildren(): void
    {
        if ((bool) config('data-rights.children.enabled', true) && NoParentalConsentVerifier::inUse()) {
            $this->warning('parental_verifier_missing', 's.9(1), Rule 10: no ParentalConsentVerifier is bound, so a parent cannot confirm consent for a child.');
        }
    }

    private function checkGate(): void
    {
        if (app()->environment('production') && DataRightsDeskServiceProvider::usesDefaultGate()) {
            $this->warning('default_gate', 'The viewDataRightsDesk gate is the default, which closes the dashboard outside local. Define your own gate.');
        }
    }

    private function checkExamplePurposes(): void
    {
        try {
            $shipped = require __DIR__.'/../../config/data-rights.php';
        } catch (Throwable) {
            return;
        }

        if (is_array($shipped) && ($shipped['purposes'] ?? null) == config('data-rights.purposes')) {
            $this->warning('example_purposes', 'The purposes in config/data-rights.php are still the shipped examples. Replace them with your own.');
        }
    }

    private function checkPersonalColumns(): void
    {
        $personal = array_map('strval', (array) config('data-rights.checker.personal_columns', []));
        $ignore = array_map('strval', (array) config('data-rights.checker.ignore_tables', []));
        $exported = [];
        $erased = [];

        foreach (Sources::exporters() as $exporter) {
            array_push($exported, ...$exporter->tables());
        }

        foreach (Sources::erasers() as $eraser) {
            array_push($erased, ...$eraser->tables());
        }

        foreach ((array) config('data-rights.retention.models', []) as $class) {
            if (is_string($class) && class_exists($class) && in_array(HasRetention::class, class_uses_recursive($class), true)) {
                $erased[] = (new $class)->getTable();
            }
        }

        try {
            $tables = array_unique(array_column(Schema::getTables(), 'name'));
        } catch (Throwable) {
            return;
        }

        foreach ($tables as $table) {
            if (in_array($table, $ignore, true) || str_starts_with($table, 'data_rights_')) {
                continue;
            }

            $columns = array_values(array_intersect(Schema::getColumnListing($table), $personal));

            if ($columns === []) {
                continue;
            }

            $missing = [];

            if (! in_array($table, $exported, true)) {
                $missing[] = 'an Exporter';
            }

            if (! in_array($table, $erased, true)) {
                $missing[] = 'an Eraser or a HasRetention model';
            }

            if ($missing !== []) {
                $this->warning('uncovered_personal_columns', "Table [{$table}] has personal columns (".implode(', ', $columns).') with no '.implode(' and no ', $missing).'.');
            }
        }
    }

    /** @param  array<string, Purpose>  $purposes */
    private function checkConsentChecks(array $purposes): void
    {
        $consentKeys = array_keys(array_filter($purposes, fn (Purpose $p): bool => $p->needsConsent()));

        if ($consentKeys === []) {
            return;
        }

        $code = $this->scannedCode();

        if ($code === null) {
            return;
        }

        foreach ($consentKeys as $key) {
            $quoted = preg_quote($key, '/');
            $call = '/(?:allows|denies|consentsTo|dataRightsAllows)\s*\([^;]*?[\'"]'.$quoted.'[\'"]/';
            $middleware = '/consent:'.$quoted.'(?![a-z0-9_.-])/';

            if (preg_match($call, $code) !== 1 && preg_match($middleware, $code) !== 1) {
                $this->warning('consent_unchecked', "Purpose [{$key}] needs consent but the code never checks it (DataRights::allows, consentsTo, the consent:{$key} middleware or @dataRightsAllows).");
            }
        }
    }

    private function scannedCode(): ?string
    {
        $paths = config('data-rights.checker.scan_paths') ?? [app_path(), base_path('routes'), resource_path('views')];
        $code = '';
        $scanned = false;

        foreach ((array) $paths as $path) {
            if (! is_string($path) || ! is_dir($path)) {
                continue;
            }

            $scanned = true;

            foreach (File::allFiles($path) as $file) {
                if (str_ends_with($file->getFilename(), '.php')) {
                    $code .= "\n".$file->getContents();
                }
            }
        }

        return $scanned ? $code : null;
    }

    private function error(string $code, string $message): void
    {
        $this->errors[] = ['code' => $code, 'message' => $message];
    }

    private function warning(string $code, string $message): void
    {
        $this->warnings[] = ['code' => $code, 'message' => $message];
    }
}
