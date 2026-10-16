# Data Rights Desk for Laravel: frozen spec (v1.0.0)

**Status: FROZEN 2026-10-16.** Change it only by adding a dated line to "Spec changes" at the bottom, with the reason. Agents read this file, never the conversation.

Brief: the owner-approved plan (`vipertecpro.com/.ai/work/plans/data-rights-desk-plan.md`). Official text used: Act No. 22 of 2023; DPDP Rules 2025, G.S.R. 846(E) (13 Nov 2025); corrigendum G.S.R. 892(E) (10 Dec 2025); Removal of Difficulties Order S.O. 5458(E) (5 Oct 2026); commencement G.S.R. 843(E). Plain-text extracts of these are in the shared notes folder `dpdp-official-text/` (path given in each agent brief). The research table with quotes and "unverified" marks is `vipertecpro.com/.ai/work/research/dpdp-rules-and-competitors-2026-10-16.md`, Part B.

## 1. What it is, and the wording rule

A free MIT Laravel package that gives an app the parts India's DPDP Act 2023 and the DPDP Rules 2025 ask a Data Fiduciary to have: purposes, notices, a consent ledger, data rights requests, retention with legal holds, breach drafts, children and guardian checks, contact details, a processor register, a dashboard and a checker.

- It **helps an app meet** the Act. Never "makes you compliant", never "DPDP compliant", never legal advice, never "official", "certified", "approved" or "partner". This applies to code comments, views, lang strings, README and docs.
- Business duties start **13 May 2027** (G.S.R. 843(E), 18 months; some sources say the 14th: plan for the 13th). Consent Manager registration starts 13 Nov 2026. Nothing in the package may claim a duty is in force today.
- Where the Rules set no number, the package says so (example: the Rules set 90 days only for grievances, Rule 14(3); the package uses 90 days as the ceiling for every request type and says that is its choice).

## 2. Versions and dependencies (checked 2026-10-16 with `composer show` and the Laravel support table)

| | Constraint | Why |
| --- | --- | --- |
| PHP | `^8.3` | Laravel 13 needs 8.3+; Laravel 12 supports 8.2–8.5, we drop 8.2 |
| laravel/framework | `^12.0\|^13.0` | 12: security fixes until 24 Feb 2027; 13: until 17 Mar 2028. Add 14 when it ships |
| dev: orchestra/testbench | `^10.12\|^11.3` | 10.x = Laravel 12, 11.x = Laravel 13 |
| dev: pestphp/pest | `^4.7\|^5.3` | Pest 5 needs PHP 8.4; Pest 4 covers 8.3 |
| dev: pestphp/pest-plugin-laravel | `^4.1\|^5.0` | |
| dev: larastan/larastan | `^3.13` | PHPStan level **6** |
| dev: laravel/pint | `^1.25` | `laravel` preset |

No other runtime dependency. `Str::markdown()` (league/commonmark ships with Laravel) renders notices. **No outbound network calls anywhere** (no Http client, no CDN, no fonts, no analytics).

## 3. Names and conventions

| Thing | Name |
| --- | --- |
| Composer | `vipertecpro/data-rights-desk` |
| Namespace | `Vipertecpro\DataRightsDesk\` (`src/`), tests `Vipertecpro\DataRightsDesk\Tests\` |
| Service provider | `DataRightsDeskServiceProvider` (auto-discovered) |
| Facade | `Vipertecpro\DataRightsDesk\Facades\DataRights` (alias `DataRights`), root `DataRightsManager` (singleton) |
| Config | `config/data-rights.php`, key `data-rights` |
| Tables | prefix `data_rights_` |
| Primary keys | ULIDs (`HasUlids`) on every package table, so no database numbers appear in any URL |
| Subject (Data Principal) columns | `$table->morphs('subject')` (respects `Schema::morphUsingUuids()` / `morphUsingUlids()` set by the app) |
| Views | namespace `data-rights` (`resources/views`), publishable tag `data-rights-views` |
| Translations | namespace `data-rights` (`lang/{locale}/*.php`), publishable tag `data-rights-lang` |
| Commands | `data-rights:*` |
| Public routes | prefix `data-rights`, names `data-rights.*` |
| Dashboard routes | prefix `data-rights-desk`, names `data-rights-desk.*` |
| Gate | `viewDataRightsDesk` |
| Publish tags | `data-rights-config`, `data-rights-migrations`, `data-rights-views`, `data-rights-lang` |
| Trait for the user model | `Vipertecpro\DataRightsDesk\Concerns\HasDataRights` |
| Trait for retention | `Vipertecpro\DataRightsDesk\Concerns\HasRetention` |

PHP style: strict types not required; typed properties and return types everywhere; enums are backed string enums in `src/Enums`; value objects are `final readonly` classes in `src/Support`; Eloquent models in `src/Models`; one action per class is fine but not required. Follow Laravel conventions; no repositories layer.

## 4. Config (`config/data-rights.php`), full shape and defaults

```php
return [
    // Data Fiduciary shown on notices, the rights page and every reply.
    'business' => [
        'name' => env('DATA_RIGHTS_BUSINESS_NAME', env('APP_NAME')),
        'website' => env('APP_URL'),
    ],

    // s.8(9), Rule 9, s.6(3): business contact of the DPO (if any) or a person who can answer.
    'contact' => [
        'name' => env('DATA_RIGHTS_CONTACT_NAME'),
        'role' => env('DATA_RIGHTS_CONTACT_ROLE', 'Data protection contact'),
        'email' => env('DATA_RIGHTS_CONTACT_EMAIL'),
        'phone' => env('DATA_RIGHTS_CONTACT_PHONE'),
        'address' => env('DATA_RIGHTS_CONTACT_ADDRESS'),
    ],

    // Rule 3(c)(iii), s.5(1)(iii): how to complain to the Board. The Board had no published
    // complaint channel when this was written (2026-10-16), so the URL is empty by default.
    'board' => [
        'url' => env('DATA_RIGHTS_BOARD_URL'),
    ],

    // s.5(3), s.6(3): languages offered. 'en' is English; others are Eighth Schedule codes.
    'default_locale' => 'en',
    'locales' => ['en', 'hi'],

    // The Data Principal model (used by commands and the dashboard lookup).
    'subject_model' => env('DATA_RIGHTS_SUBJECT_MODEL', 'App\\Models\\User'),
    'subject_email_column' => 'email',

    // Purposes: key => definition. Text fields take a string (default locale) or
    // ['en' => '...', 'hi' => '...']. 'data' takes a list or ['en' => [...], 'hi' => [...]].
    'purposes' => [ /* see §4.1 */ ],

    'consent' => [
        'record_ip' => false,          // data minimisation: off by default
        'record_user_agent' => false,  // data minimisation: off by default
    ],

    'requests' => [
        'response_days' => 90,         // clamped to 90 (Rule 14(3) ceiling)
        'download_days' => 30,         // access summary download window after completion
        'identifier' => null,          // Rule 14(1)(b) text; null = lang default ("the email address you sign in with")
        'notify' => true,              // acknowledgement + closing notification to the Data Principal
    ],

    // Rule 8(3), Rule 6(1)(e): logs and package records are kept at least 365 days, then deleted.
    'retention' => [
        'keep_days' => 365,            // never below 365; lower values are raised to 365 and flagged by the checker
        'models' => [],                // app models using HasRetention
        'inactivity' => [              // Rule 8(1)-(2), Third Schedule: only for the three classes
            'enabled' => false,
            'years' => 3,
            'warning_hours' => 48,
            'counting_from' => '2025-11-13', // "commencement of the Rules": 13 Nov 2025 or 13 May 2027 is unclear; earlier date = erase no later than either reading
        ],
    ],

    // Classes implementing Contracts\Exporter and/or Contracts\Eraser.
    'sources' => [],

    'children' => [
        'enabled' => true,
        'unknown_age_is_child' => false,
    ],

    'notifications' => [
        'channels' => ['mail'],
    ],

    'routes' => [
        'public' => ['enabled' => true, 'prefix' => 'data-rights', 'middleware' => ['web']],
        'dashboard' => ['enabled' => true, 'prefix' => 'data-rights-desk', 'middleware' => ['web', 'auth']],
        'api' => ['enabled' => true],
    ],

    'checker' => [
        'scan_paths' => null, // null = [app_path(), base_path('routes'), resource_path('views')]
        'personal_columns' => ['email', 'phone', 'mobile', 'name', 'first_name', 'last_name', 'full_name',
            'address', 'address_line_1', 'address_line_2', 'dob', 'date_of_birth', 'birth_date', 'ip_address',
            'user_agent', 'aadhaar', 'aadhaar_number', 'pan', 'pan_number', 'passport_number', 'latitude', 'longitude'],
        'ignore_tables' => ['migrations', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs',
            'sessions', 'password_reset_tokens'],
    ],
];
```

### 4.1 Purpose definition

```php
'marketing' => [
    'title' => ['en' => 'Product news by email', 'hi' => '...'],
    'description' => [...],          // what the processing is for (Rule 3(b)(ii) "specified purpose")
    'goods_or_services' => [...],    // Rule 3(b)(ii) "specific description of the goods or services ... or uses"
    'data' => ['en' => ['Email address', 'Name'], 'hi' => [...]], // Rule 3(b)(i) itemised description
    'basis' => 'consent',            // 'consent' (s.6) or 'legitimate_use' (s.7)
    'legitimate_use' => null,        // when basis = legitimate_use: one of 'a'..'i' (s.7 clause)
    'tracking' => false,             // true if tracking, behavioural monitoring or targeted advertising (s.9(3))
    'fourth_schedule' => null,       // children exemption key, see §11.3
    'withdrawal_effect' => null,     // optional text shown before withdrawing (s.6(5) consequences)
],
```

Keys match `/^[a-z0-9_.-]{1,64}$/`. The shipped config holds three **example** purposes (`account` consent, `marketing` consent, `analytics` consent with `tracking => true`) in English and Hindi; the checker warns while the examples are unchanged.

## 5. Tables (all ULID primary keys, timestamps where noted)

| Table | Columns |
| --- | --- |
| `data_rights_subjects` | subject morphs (unique pair); `age_status` nullable (`adult`/`child`); `age_declared_at`; `has_lawful_guardian` bool default false (Rule 11); `last_seen_at`; `erasure_warned_at`; `erasure_due_at`; `erased_at`; timestamps |
| `data_rights_notices` | `version` uint; `locale` string(12); `body` longText (Markdown); `hash` char(64) sha256 of body; `purposes` json (snapshot used to build it); `published_at`; unique(`version`,`locale`) |
| `data_rights_notice_deliveries` | `notice_id` FK; subject morphs; `channel` string (`legacy`, `update`); `delivered_at` |
| `data_rights_consents` (append-only) | subject morphs; `purpose` string(64); `action` (`given`/`withdrawn`); `notice_id` FK nullable; `notice_hash` char(64) nullable; `locale` nullable; `channel` (`web`,`api`,`admin`,`consent_manager`,`legacy`,`import`); `given_by` (`self`,`parent`,`lawful_guardian`) default `self`; `guardian_consent_id` nullable; `consent_manager` nullable; `external_reference` nullable; `ip_address` nullable; `user_agent` nullable; `occurred_at` timestamp(6); `created_at`. Index (subject_type, subject_id, purpose, occurred_at). The model throws on update and on delete except through the retention keeper |
| `data_rights_requests` | subject nullableMorphs; `type`; `status`; `details` text **encrypted** nullable; `response` text **encrypted** nullable; `result` json nullable (erasure report: counts and reasons, no personal data); `channel` (`web`,`api`,`admin`); `locale`; `received_at`; `due_at`; `identity_verified_at`; `identity_method`; `closed_at`; timestamps. Index (`status`,`due_at`) |
| `data_rights_request_events` | `request_id` FK cascade; `from_status` nullable; `to_status`; actor nullableMorphs; `note` text **encrypted** nullable; `created_at` |
| `data_rights_nominations` | subject morphs; `nominee_name` **encrypted**; `nominee_contact` **encrypted**; `relationship` **encrypted** nullable; `created_at`; `revoked_at` |
| `data_rights_guardian_consents` | subject morphs (child or person with disability); `kind` (`parent`/`lawful_guardian`); `method` (see §11.2); guardian nullableMorphs (when the guardian is a registered user); `guardian_name` **encrypted** nullable; `verification_reference` **encrypted** nullable; `verified_at`; `verified_by` string (verifier class); `revoked_at`; timestamps |
| `data_rights_processors` | `name`; `kind` (`processor`/`fiduciary`); `description` nullable; `purposes` json; `data_shared` json; `contract_reference` nullable; `contract_date` date nullable; `security_clause` bool default false (Rule 6(1)(f)); `country` nullable; `hidden_from_access_summary` bool default false (s.11(2)); `active` bool default true; timestamps |
| `data_rights_breaches` | `title`; **encrypted** text: `nature`, `extent`, `location`, `likely_impact`, `facts`, `mitigation`, `findings`, `remedial`, `consequences_for_people`, `safety_steps` (all nullable except `nature`); `occurred_at` nullable; `became_aware_at`; `board_alerted_at`; `board_report_due_at` (= aware + 72 h); `board_extension_until` nullable; `board_reported_at`; `closed_at`; timestamps |
| `data_rights_breach_subjects` | `breach_id` FK cascade; subject morphs; `notified_at`; `channel`; unique(breach_id, subject_type, subject_id) |
| `data_rights_legal_holds` | holdable morphs; `reason` **encrypted**; `law_reference` nullable; placed_by nullableMorphs; `placed_at`; `expires_at` nullable; `released_at` nullable |
| `data_rights_retention_runs` | `started_at`; `finished_at`; `pretend` bool; `summary` json (counts only) |
| `data_rights_processing_logs` | subject morphs; `purpose`; `activity` string(120); `occurred_at`. No free text, no personal data |

Migrations are published with `publishesMigrations()` (tag `data-rights-migrations`), not auto-loaded. One migration file per table is fine; keep them in `database/migrations`.

## 6. PHP API (public names are fixed; README depends on them)

```php
use Vipertecpro\DataRightsDesk\Facades\DataRights;

DataRights::allows(Model $subject, string $purpose): bool;
DataRights::denies(Model $subject, string $purpose): bool;
DataRights::give(Model $subject, string $purpose, array $options = []): ConsentRecord;     // options: channel, locale, ip, user_agent, given_by, guardian_consent
DataRights::withdraw(Model $subject, string $purpose, array $options = []): ConsentRecord;
DataRights::logProcessing(Model $subject, string $purpose, string $activity): void;       // feeds s.11 summary; Rule 8(3) log
DataRights::proof(Model $subject, ?string $purpose = null): array;                          // s.6(10)
DataRights::contact(): array;                                                               // Rule 9 block

DataRights::purposes(): PurposeRegistry;   // all(), get($key), consentBased(), forLocale($locale)
DataRights::consents(): ConsentLedger;     // give, withdraw, current($subject): array<purpose,bool>, history($subject, ?$purpose),
                                           // recordFromConsentManager($subject, $purpose, ConsentAction $action, string $consentManager, string $reference, ?CarbonInterface $at),
                                           // importLegacy($subject, $purpose, CarbonInterface $givenAt)
DataRights::notices(): NoticeRepository;   // offeredLocales(), build($locale): BuiltNotice, publish(): ?int (new version or null if unchanged), current($locale), find($version, $locale)
DataRights::requests(): RequestDesk;       // open(RequestType $type, ?Model $subject, ?string $details, array $options = []), verifyIdentity($request, string $method, ?Model $actor),
                                           // transition($request, RequestStatus $to, ?Model $actor, ?string $note), complete($request, string $response, ?Model $actor),
                                           // reject($request, string $response, ?Model $actor), accessSummary(Model $subject): array, erase($request, ?Model $actor): ErasureReport
DataRights::nominations(): NominationBook; // nominate($subject, string $name, string $contact, ?string $relationship), revoke($nomination)
DataRights::breaches(): BreachDesk;        // record(array $attributes), addAffected($breach, iterable $subjects), boardAlertDraft($breach): string,
                                           // boardReportDraft($breach): string, personMessage($breach, ?string $locale): string,
                                           // markBoardAlerted($breach), markBoardReported($breach), notifyAffected($breach): int
DataRights::children(): ChildGuard;        // recordAge($subject, bool $isAdult), isChild($subject), needsGuardianConsent($subject),
                                           // recordGuardianConsent($subject, GuardianVerification $v), revokeGuardianConsent($consent), mayTrack(?Model $subject): bool
DataRights::retention(): RetentionKeeper;  // run(bool $pretend = false): RetentionReport, placeHold(Model $holdable, string $reason, ?string $lawReference, ?CarbonInterface $until, ?Model $by), releaseHold($hold)
DataRights::check(): CheckReport;          // errors(), warnings(), passed(), toArray()
```

`HasDataRights` trait (on the user model): relations `dataRightsConsents()`, `dataRightsRequests()`, `dataRightsProfile()` (morphOne to `data_rights_subjects`); `consentsTo(string $purpose): bool`; `dataRightsLabel(): string` (default: the email column, else the key; shown only in the dashboard); `dataRightsLocale(): ?string` (default null). On the model's `deleted` event (and `forceDeleted`) it sets `erased_at` on the profile.

Unknown purpose keys throw `Exceptions\UnknownPurpose`. Giving consent with no published notice for the locale throws `Exceptions\NoticeMissing` (s.5(1): the notice comes first).

## 7. Contracts the app implements (`src/Contracts`)

```php
interface Exporter {                       // s.11(1)(a)
    public function name(): string;        // "Orders"
    public function tables(): array;       // tables it covers, for the checker
    public function processing(): string;  // plain description of the processing activities
    public function export(Model $subject): array; // rows of the person's data, JSON-safe
}

interface Eraser {                         // s.8(7), s.12(3), Rule 8
    public function name(): string;
    public function tables(): array;
    public function erase(Model $subject, ErasureContext $context): ErasureOutcome;
}

interface IdentityVerifier {               // Rule 14(1)(b), Rule 14(5)
    public function verify(DataRightsRequest $request, ?Model $signedInUser): ?string; // method name when verified, null when a person must check
}

interface ParentalConsentVerifier {        // s.9(1), Rules 10-11
    public function verify(Model $subject, Request $request): GuardianVerification; // throws ValidationException on failure
}

interface ConsentManagerGateway {          // s.6(7)-(9); experimental, no Board standard published yet
    public function identifier(): string;  // registration number or name of the Consent Manager
}
```

- `ErasureContext`: `reason` (`request`, `inactivity`), `subject()`, `isHeld(Model $record): bool`, `subjectIsHeld(): bool`, `keepUntil(CarbonInterface $processedAt): CarbonInterface` (processedAt + keep_days, Rule 8(3)). For `inactivity` it also tells the eraser to keep what the person needs to access her account and any stored-value virtual token (Third Schedule exceptions (a) and (b)): `keepsAccountAccess(): bool`.
- `ErasureOutcome`: `::erased(int $count)`, `::retained(int $count, string $reason, ?CarbonInterface $until = null)`, `->and(ErasureOutcome $other)`. `ErasureReport` collects outcomes per source, plus `held` when a legal hold blocked the subject.
- `GuardianVerification`: kind, method, optional guardian model, guardian name, verification reference.
- Defaults bound in the container: `IdentityVerifier` → `Verification\SignedInSubjectVerifier` (verified with method `signed_in` when the signed-in user is the subject; else null); `ParentalConsentVerifier` → `Verification\NoParentalConsentVerifier` (always fails with a message that the app must bind its own verifier).
- `php artisan data-rights:make-source {Name}` writes `app/DataRights/{Name}.php` implementing both `Exporter` and `Eraser` with TODOs.

## 8. Routes

Public (`routes.public`, middleware from config):

| Method | URI | Name | Auth | Purpose |
| --- | --- | --- | --- | --- |
| GET | `/data-rights` | `data-rights.index` | no | Rights page: contact (Rule 9), how to make a request and the identifier needed (Rule 14(1)), response period (Rule 14(3)), Board complaint (Rule 3(c)(iii)), links to notice and preferences |
| GET | `/data-rights/notice` | `data-rights.notice` | no | Current notice, `?lang=` |
| GET | `/data-rights/notice/{version}` | `data-rights.notice.version` | no | A past version |
| GET | `/data-rights/assets/{file}` | `data-rights.asset` | no | The one stylesheet, whitelisted, long cache |
| GET | `/data-rights/preferences` | `data-rights.preferences` | yes | Consent per purpose; age question; guardian status |
| POST | `/data-rights/consents/{purpose}` | `data-rights.consents.give` | yes | One click |
| DELETE | `/data-rights/consents/{purpose}` | `data-rights.consents.withdraw` | yes | One click |
| POST | `/data-rights/age` | `data-rights.age` | yes | Adult or under 18 |
| POST | `/data-rights/guardian` | `data-rights.guardian` | yes | Delegates to `ParentalConsentVerifier` |
| GET | `/data-rights/requests` | `data-rights.requests.index` | yes | My requests + new request form + nominations |
| POST | `/data-rights/requests` | `data-rights.requests.store` | yes | throttle 6/min |
| GET | `/data-rights/requests/{request}` | `data-rights.requests.show` | yes, own | Status, clock, response |
| POST | `/data-rights/requests/{request}/cancel` | `data-rights.requests.cancel` | yes, own | |
| GET | `/data-rights/requests/{request}/download` | `data-rights.requests.download` | yes, own | Access summary JSON, only completed access requests within `download_days` |
| POST | `/data-rights/nominations` | `data-rights.nominations.store` | yes | |
| DELETE | `/data-rights/nominations/{nomination}` | `data-rights.nominations.destroy` | yes, own | |

JSON for apps with their own screens (`routes.api.enabled`): `GET /data-rights/api/notice` (no auth); with auth: `GET /data-rights/api/purposes` (purposes + this user's state + notice meta), `POST|DELETE /data-rights/api/consents/{purpose}`, `GET|POST /data-rights/api/requests`, `GET /data-rights/api/requests/{request}`. Names `data-rights.api.*`.

Dashboard (`routes.dashboard`, plus the `viewDataRightsDesk` gate on every route; 403 otherwise):

`GET /` overview · `GET /requests` (filters status, type, overdue) · `GET /requests/create` · `POST /requests` (record a request received by email or phone; lookup by subject email) · `GET /requests/{request}` · `POST /requests/{request}/verify` · `POST /requests/{request}/status` · `POST /requests/{request}/complete` · `POST /requests/{request}/reject` · `POST /requests/{request}/erase` (needs a confirm checkbox) · `GET /requests/{request}/summary` (access summary preview) · `GET /consents` (counts per purpose, lookup by email) · `POST /consents/proof` (download proof JSON) · `GET /notices` · `POST /notices/publish` · `GET /notices/{notice}` · `GET /breaches` · `GET /breaches/create` · `POST /breaches` · `GET /breaches/{breach}` · `PUT /breaches/{breach}` · `POST /breaches/{breach}/affected` (subject emails, one per line) · `POST /breaches/{breach}/board-alerted` · `POST /breaches/{breach}/board-reported` · `POST /breaches/{breach}/notify` · `GET /breaches/{breach}/draft/{kind}` (`alert`, `report`, `person`; text download) · `GET /retention` (runs, holds) · `POST /retention/holds` · `POST /retention/holds/{hold}/release` · `GET /processors` · `GET /processors/create` · `POST /processors` · `GET /processors/{processor}/edit` · `PUT /processors/{processor}` · `GET /children` · `GET /check`.
Dashboard JSON (read-only, same gate): `GET /api/summary`, `/api/requests`, `/api/requests/{request}`, `/api/consents`, `/api/breaches`, `/api/breaches/{breach}`, `/api/notices`, `/api/retention`, `/api/check`. Names `data-rights-desk.*` and `data-rights-desk.api.*`.

Gate default: defined in the package's `boot` only if not already defined: allow when `app()->environment('local')`, deny otherwise (like Horizon). The app overrides it with its own `Gate::define('viewDataRightsDesk', ...)`.

## 9. Middleware and Blade

- `consent:{purpose}` (alias registered only if `consent` is not already taken) and always `data-rights.consent:{purpose}`: guest → 401/redirect to login per Laravel's auth handling; denied → JSON 403 `{message, purpose, preferences_url}` or redirect to `data-rights.preferences`.
- `data-rights.activity`: sets `last_seen_at` at most once per day per subject; clears a pending erasure warning (Rule 8(2)).
- Blade: `@dataRightsAllows('purpose') ... @endDataRightsAllows` (signed-in user allowed), `@dataRightsTracking ... @endDataRightsTracking` (renders only when `children()->mayTrack(auth()->user())`).

## 10. Commands

| Command | Does |
| --- | --- |
| `data-rights:install` | Publishes config and migrations, prints the next steps |
| `data-rights:notice {--pretend}` | Builds every offered locale; publishes version N+1 for all offered locales when any locale's hash changed or a locale was added; refuses (exit 1, names the missing fields) when a locale is missing purpose text or its lang status is `needed` |
| `data-rights:notify-existing {--before=2027-05-13} {--chunk=500} {--pretend}` | s.5(2): sends `NoticeNotification` to subjects created before `--before` who have no delivery row for the current notice version, records a `legacy` delivery; idempotent |
| `data-rights:retain {--pretend}` | Runs the keeper (§12.6); records a run |
| `data-rights:proof {email} {--purpose=} {--output=}` | s.6(10): writes the proof JSON (stdout or file) |
| `data-rights:breach-draft {breach} {kind=report} {--lang=en}` | Prints the alert, report or person draft |
| `data-rights:check {--json}` | Checker; exit 1 when any error |
| `data-rights:make-source {name}` | Stub generator |

## 11. Notifications, events, languages

### 11.1 Notifications (queued, channels from config, locale = subject's `dataRightsLocale()` or the request locale or default; each includes the contact block, Rule 9)
`NoticeNotification` (s.5(2) and notice updates), `RequestReceivedNotification`, `RequestClosedNotification` (completed or rejected, with the response; for erasure it is sent after erasure to the address captured before erasure, via an on-demand route), `BreachNotification` (Rule 7(1)(a)–(e)), `ErasureWarningNotification` (Rule 8(2)).

### 11.2 Events (`src/Events`)
`ConsentGiven`, `ConsentWithdrawn` (app listeners stop processors, s.6(6)), `NoticePublished`, `RequestReceived`, `RequestStatusChanged`, `PersonalDataErased`, `BreachRecorded`, `ErasureWarningSent`, `GuardianConsentRecorded`.
Guardian `method` values: `reliable_details_held` (Rule 10(1)(a)), `details_provided` (10(1)(b)(i)), `virtual_token` (10(1)(b)(ii)), `court_appointed`, `designated_authority`, `local_level_committee` (Rule 11(1)).

### 11.3 Fourth Schedule keys (`purposes.*.fourth_schedule`)
Part A (class of Data Fiduciary, with the condition): `A1` clinical establishment, mental health establishment or healthcare professional; `A2` allied healthcare professional; `A3` educational institution; `A4` crèche or child day care centre; `A5` transport for an educational institution, crèche or centre. Part B (purpose): `B1` legal function in the interests of a child; `B2` subsidy, benefit or service under s.7(b); `B3` creating an email-only user account; `B4` real-time location of a child for safety; `B5` keeping detrimental information, services or ads away from a child; `B6` confirming a person is not a child (Rule 10 due diligence). A purpose with a key skips s.9(1) and s.9(3) checks for children. The developer declares that the Schedule's condition is met; the package cannot check it. The keys and conditions live in `src/Support/FourthSchedule.php` with the official condition text.

### 11.4 Languages
`lang/{locale}/` holds `meta.php` (`status` = `ready` | `needs_review` | `needed`, `name` native name, `english_name`, `direction` `ltr`/`rtl`), `notice.php`, `public.php` (rights page, preferences, requests), `mail.php`. Locales shipped: `en` (ready), `hi` (needs_review until the owner reviews it), and the other 21 Eighth Schedule languages with `meta.php` only and `status => 'needed'`: `as, bn, brx, doi, gu, kn, ks, kok, mai, ml, mni, mr, ne, or, pa, sa, sat, sd, ta, te, ur` (22 scheduled languages in all, Hindi included). A locale can be offered only when its status is not `needed` (an app that publishes its own translation into `lang/vendor/data-rights/{locale}/` and sets the status makes it available). The dashboard is English only.

## 12. Behaviour per duty (each row = at least one Pest test with the exact name shown)

Test files live in `tests/Feature/Duties/` and each test name starts with the reference, e.g. `it('Rule 3: the notice itemises the data and states each purpose and use')`.

| # | Ref | Behaviour | Test name starts with |
| --- | --- | --- | --- |
| 1 | s.4, s.7 | `allows()` true for a legitimate-use purpose (clauses b–i always; clause a until the person objects via `withdraw`); consent purposes need a `given` latest row | `s.7:` |
| 2 | s.5(1), Rule 3(a)–(c) | Notice built per locale is standalone Markdown with: business name; per purpose the title, description, goods or services, itemised data, basis; the link to withdraw (preferences URL) with "as easily as you gave it"; the rights page link; how to complain to the Board (URL when set, else the text without a link); contact block; available languages; version and date | `Rule 3:` |
| 3 | s.5(2) | `notify-existing` sends once per subject per version, records deliveries, respects `--before` and `--pretend` | `s.5(2):` |
| 4 | s.5(3), s.6(3) | Notice and preferences offered in each offered locale; `?lang=` switches; a `needed` locale cannot be offered or published; contact block on the consent page | `s.5(3):` |
| 5 | s.6(1) | Consent is per purpose, recorded only from an explicit POST by the person; nothing pre-ticked; no bundled "accept all" control | `s.6(1):` |
| 6 | s.6(4) | Withdraw is one request, same shape as give, on the same page | `s.6(4):` |
| 7 | s.6(6) | `ConsentWithdrawn` fires with subject and purpose | `s.6(6):` |
| 8 | s.6(7)–(9) | Consent Manager records carry channel `consent_manager`, the manager id and reference, and count in `allows()` | `s.6(7):` |
| 9 | s.6(10) | Proof JSON holds every ledger row for the subject (and purpose), the notice text and hash each row references, and deliveries | `s.6(10):` |
| 10 | s.8(2), Rule 6(1)(f) | Processor register; checker warns on a processor without a contract reference or security clause | `s.8(2):` |
| 11 | s.8(5), Rule 6(1)(a) | Raw DB values of encrypted columns differ from the plaintext | `Rule 6:` |
| 12 | s.8(6), Rule 7(1) | Person message has (a) description, nature, extent, timing; (b) consequences; (c) mitigation; (d) safety steps; (e) contact; `notifyAffected` sends once per subject and records `notified_at` | `Rule 7(1):` |
| 13 | Rule 7(2) | Alert draft has nature, extent, timing, location, likely impact; report draft has (i)–(vi) incl. the count of people told; due = aware + 72 h; extension respected; overdue shown | `Rule 7(2):` |
| 14 | s.8(7), s.12(3) | Erasure request runs every Eraser, stores the report, sends the closing notice to the captured address, marks the profile erased; a subject-level legal hold stops it and the report says so | `s.12(3):` |
| 15 | Rule 8(1)–(2), Third Schedule | Off by default; when on: warning ≥48 h before the 3-year mark, activity clears it, erasure after the mark with context `inactivity`; clock starts no earlier than `counting_from` | `Rule 8(1):` and `Rule 8(2):` |
| 16 | Rule 8(3), Rule 6(1)(e) | Keeper deletes processing logs and closed package records older than `keep_days`; never less than 365 days even if configured lower; `HasRetention` models erase after max(model days, 365); legal holds skip | `Rule 8(3):` |
| 17 | s.8(9), Rule 9 | Contact block on the rights page, every notice, every request notification | `Rule 9:` |
| 18 | s.8(10), s.13, Rule 14(3) | Grievance request due in ≤90 days; `response_days` > 90 clamps to 90; overdue requests flagged in dashboard and checker | `Rule 14(3):` |
| 19 | s.9(1), Rule 10 | Declared child: `allows()` false until an active guardian consent and a `given` row with `given_by` parent; default verifier refuses | `s.9(1):` |
| 20 | s.9(3) | `tracking` purposes false for children even with guardian consent; `mayTrack()` false for children; Blade directive hides content | `s.9(3):` |
| 21 | Rule 11 | `has_lawful_guardian` subjects need a `lawful_guardian` consent with a Rule 11 method | `Rule 11:` |
| 22 | Rule 12, Fourth Schedule | A purpose with a Fourth Schedule key skips s.9(1) and (3) | `Rule 12:` |
| 23 | s.11(1)–(2) | Access summary: exporter data, processing activities from logs in the last `keep_days`, consents, processors and fiduciaries for the person's purposes (minus `hidden_from_access_summary`), contact | `s.11:` |
| 24 | s.12(1)–(2) | Correction, completion and updating requests move through statuses with events and a response | `s.12(2):` |
| 25 | s.14, Rule 14(4) | Nominate and revoke; nominee fields encrypted; erased with the subject | `s.14:` |
| 26 | Rule 14(1), (5) | Rights page shows how to ask and the identifier needed, without sign-in | `Rule 14(1):` |
| 27 | security | Dashboard and dashboard JSON: guest redirected, signed-in user without the gate 403, gate passes 200; a person cannot see another person's request or download | `Security:` |
| 28 | checker | Errors: missing contact, no published or stale notice, offered `needed` locale, `response_days` > 90, `keep_days` < 365 (warning), overdue requests (error), breach past 72 h without report (error); warnings: Hindi not offered, Board URL empty, example purposes unchanged, uncovered personal columns (exporter, eraser, retention), consent purpose never checked in code, default gate in production, no parental verifier bound while children enabled; exit codes | `Checker:` |
| 29 | minimisation | IP and user agent not stored unless enabled; no package log lines contain personal data (`Log::spy`); only age status stored, never a birth date | `Minimisation:` |
| 30 | network | Whole suite runs with `Http::preventStrayRequests()` | (TestCase setUp) |

## 12.6 Retention keeper (`data-rights:retain`), in order
1. Raise `keep_days` to 365 if lower.
2. Delete `data_rights_processing_logs` older than `keep_days` (unless the subject is held).
3. For each `retention.models` class using `HasRetention`: query records with `retentionDateColumn() < now - max(retentionDays(), keep_days)`, apply `retentionQuery()`, skip held records (no `whereHas` on morphs across connections; use a `whereNotIn` on held ids per class), call `eraseForRetention()` (default: `delete()`), count.
4. Inactivity (only when enabled): warn subjects whose clock reaches the mark within `warning_hours` and who were not warned; erase subjects warned ≥ `warning_hours` ago, past the mark, with no activity since the warning, not held (all Erasers, context `inactivity`).
5. Delete package records of subjects with `erased_at` older than `keep_days` (consents, deliveries, nominations, guardian consents, processing logs, closed requests and their events, breach-subject rows), unless held.
6. Delete requests (and events) closed more than `keep_days` ago, unless held.
7. Record the run with counts. `--pretend` changes nothing and prints the counts.

`HasRetention` trait: model implements `retentionPurpose(): string`; optional overrides `retentionDays(): int` (default `keep_days`), `retentionDateColumn(): string` (default `updated_at`), `retentionQuery(Builder $query): Builder` (default unchanged), `eraseForRetention(): void` (default delete).

## 13. The package's own privacy and security

- Encrypted casts on every free-text field listed in §5. Show decrypted text only on the dashboard and to the person who wrote it.
- Never log personal data; no `Log::` calls with subject data. Exceptions carry ids, not emails.
- Dashboard behind auth + gate on every route including JSON; CSRF on every form (web middleware); route model binding by ULID; a person can only reach their own requests, downloads and nominations (403 otherwise).
- Mass-assignment: explicit `$fillable`.
- Erasure really erases: after an erasure request the eraser ran, nominations for the subject are deleted at once, and the keeper deletes the rest after `keep_days`. README documents exactly what the package stores and for how long.
- Throttle request creation (6 per minute per user).
- Output escaping everywhere; notice Markdown rendered with `html_input => 'escape'` and `allow_unsafe_links => false`.

## 14. Dashboard and public UI

- Plain Blade, one hand-written stylesheet `resources/dist/data-rights.css` (no build step, no CDN, no JavaScript needed), served by `data-rights.asset` with `?v={hash}` and a one-year immutable cache header.
- Light and dark via `prefers-color-scheme`. Neutral greys with one accent `#F26A3D` (coral) used sparingly; text contrast WCAG AA.
- Mobile first: works at 375 px with no horizontal page scroll; dashboard nav is a wrapping tab row; tables turn into stacked rows under 640 px; tap targets ≥ 44 px.
- Overview shows the key number first: open requests and the nearest due date; overdue in red; breaches with hours left to the 72-hour report; consents given and withdrawn per purpose; last retention run; current notice version; checker error count with a link.
- Every page has a plain heading and one primary action. Times shown in the app timezone with the date, e.g. "Due 14 Jan 2027 (41 days left)".
- Public pages use a minimal standalone layout (`data-rights::layouts.public`) the app can replace by publishing views. Language switcher on notice, rights and preferences pages lists only offered locales by native name. `lang` and `dir` attributes set from `meta.php`.

## 15. Quality bar

- `vendor/bin/pint --test`, `vendor/bin/phpstan analyse` (level 6, larastan), `vendor/bin/pest` all green locally (SQLite in memory).
- `.github/workflows/tests.yml`: matrix PHP 8.3, 8.4, 8.5 × Laravel 12, 13 × sqlite, mysql 8, postgres 16 (service containers), plus a pint + phpstan job. It cannot run until a GitHub repo exists.
- `composer.json` scripts: `test`, `analyse`, `format`, `ci` (`pint --test`, `phpstan`, `pest`).
- `.gitattributes` export-ignores `tests`, `docs`, `marketplace`, `.github`, `phpunit.xml`, `phpstan.neon.dist`, `.gitattributes`, `.gitignore`.

## 16. File ownership (no two agents touch the same file)

| Owner | Files |
| --- | --- |
| Main session | `docs/spec.md`, `composer.json` (agents may add autoload/scripts/requires-dev with a note in their report), `marketplace/`, final README rule checks |
| Agent A (core, Opus) | `src/**`, `config/**`, `database/**`, `routes/**`, `resources/**`, `tests/**`, `lang/en/**` (complete, functional English), `lang/hi/meta.php` placeholder only, `.github/**`, `phpunit.xml`, `phpstan.neon.dist`, `pint.json`, `stubs/**` |
| Agent B (docs and text, Sonnet, after A lands) | `README.md`, `CHANGELOG.md`, `docs/rules.md`, `docs/translations.md`, `lang/hi/**`, the 21 `lang/{code}/meta.php` stubs, wording edits to `lang/en/*.php` **values only** (never keys), the Hindi strings of the example purposes in `config/data-rights.php` (values only) |

## 17. Definition of done (v1.0.0, before the owner preview)

1. Every row of §12 has a passing test; pint, phpstan and pest green with output tails in the report.
2. Fresh Laravel 13 app (`~/Herd/drd-sandbox`) installs the package from a path repository and every feature works once end to end following only the README.
3. Independent review (Opus) findings fixed.
4. README rules: one person's voice ("I"), no links of any kind, "What it does not do", requirements, the not-legal-advice notice, the independence line, no "official/partner/certified", a "What it stores and for how long" table, a "Where it fits" list (5–10 app types by industry).
5. `docs/rules.md` maps every feature to its section or rule with a short official quote and the G.S.R. or Act reference; "unverified" where the research says so.

## 18. Out of v1 (do not build)

Acting as a Consent Manager; a Consent Manager API client or webhook route; Significant Data Fiduciary duties (DPIA, audit, Rule 13); cross-border rules; DigiLocker or any parental-verification provider; Filament plugin; translations beyond English and Hindi; a NativePHP bridge; sending anything to the Board (the package drafts, the business sends).

## Spec changes

- 2026-10-16: frozen.
