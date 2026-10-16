# Changelog

All notable changes to this package are written here. It follows Semantic Versioning.

## 1.1.0 - Unreleased

### Added

- **Token sign-in for the JSON feed**: `routes.api.middleware` and `routes.api.auth` in the config. Mobile apps, including NativePHP apps, use `['api']` and `'auth:sanctum'` with Laravel Sanctum. The defaults keep the 1.0 behaviour (browser session).
- **More databases in CI**: MariaDB 11 and SQL Server 2022 join SQLite, MySQL 8.0 and PostgreSQL 16.
- **Consent at sign-up**: the `<x-data-rights::consent-fields>` component (the notice link and one unticked box per consent purpose), `DataRights::giveFromForm()` (takes the request or a Fortify input array, and records the notice version the form showed), `DataRights::consents()->giveMany()` and the `IncludesPurpose` validation rule.
- **A smarter install command**: `data-rights:install` names the app's database, front end (Livewire, Blade, or Inertia with React, Vue or Svelte) and whether Sanctum is installed, then prints the next steps that fit. It never edits your code.

### Changed

- The JSON feed is its own route group with the same addresses and route names. It now works even when the public pages are switched off.

### Fixed

- **SQL Server**: the `created_at` column of `data_rights_consents` now keeps microseconds like `occurred_at`. SQL Server refused the old column type. This applies to new installs. Apps already on 1.0 with SQLite, MySQL, MariaDB or PostgreSQL need nothing.

## 1.0.0 - 2026-10-16

First release. Needs PHP 8.3 or newer and Laravel 12 or 13.

### Added

- **Purposes** in `config/data-rights.php`, each with a title, a description, the goods or services, the itemised data, a basis (consent under section 6 or a legitimate use under section 7), a tracking flag, an optional Fourth Schedule key and an optional withdrawal effect. Text can be given per language.
- **The notice** (section 5, Rule 3): built per language as standalone Markdown, with every published version kept and hashed. `php artisan data-rights:notice` publishes a new version only when the text changed or a language was added.
- **The consent ledger** (section 6): append-only rows for every give and withdraw, tied to the notice version and hash the person saw. Consent recorded through a Consent Manager, a legacy import for older consents, and a proof file per person (`php artisan data-rights:proof`).
- **Public pages**: the rights page, the notice (current and past versions), the consent choices, the person's requests, and nominations. A JSON API for apps with their own screens.
- **Data rights requests** (sections 11 to 14): a summary of the person's data, correction, completion, updating, erasure and grievances. Each has a due date of at most 90 days, an identity check, a reply and a notification to the person (email by default). A completed access request can be downloaded as JSON, for 30 days by default.
- **Nominations** (section 14), with the nominee's details encrypted.
- **Breach register** (section 8(6), Rule 7): drafts of the alert to the Board, the 72-hour report and the message to each affected person. The package drafts and the business sends.
- **Retention keeper** (Rule 8, Rule 6(1)(e)): a 365-day minimum, legal holds, the `HasRetention` trait for your own models, and an optional inactivity erasure with a 48-hour warning. `php artisan data-rights:retain` runs it, and `--pretend` counts only.
- **Children and persons with disability** (section 9, Rules 10 to 12): an age answer, parent or lawful guardian consent through a `ParentalConsentVerifier` that the app provides, no tracking of children, and Fourth Schedule keys.
- **A processor register** (section 8(2), Rule 6(1)(f)).
- **A contact block** (Rule 9) on the rights page, in the notice and in every email.
- **A dashboard** at `/data-rights-desk` with an overview, requests, consents, notices, breaches, retention, processors, children and the checker, plus read-only JSON. It is behind the `viewDataRightsDesk` gate.
- **A checker**: `php artisan data-rights:check` reports errors and warnings and exits with code 1 on errors.
- **Safeguards on erasure**: an erasure request can only be closed by erasing, erasure refuses to run when no Eraser is configured (a checker error), the person's other open requests are cancelled, and the keeper deletes only records dated up to the erasure, so a person who comes back keeps her newer records.
- **Age answer and contact**: a person's age answer is locked once given, and staff correct it on the Children page with an encrypted note. Staff can record that a person got in touch (Rule 8(2)), which clears a pending erasure warning.
- **Children**: for a person under 18, every purpose waits for a parent or lawful guardian unless it carries a Fourth Schedule key (section 9(1) covers "any personal data of a child").
- **Commands**: `data-rights:install`, `data-rights:notice`, `data-rights:notify-existing`, `data-rights:retain`, `data-rights:proof`, `data-rights:breach-draft`, `data-rights:check` and `data-rights:make-source`.
- **In your code**: the `DataRights` facade, the `HasDataRights` trait, the `consent:{purpose}` and `data-rights.consent:{purpose}` middleware, the `data-rights.activity` middleware, the `@dataRightsAllows` and `@dataRightsTracking` Blade directives, and nine events from `ConsentGiven` to `GuardianConsentRecorded`.
- **Languages**: English is ready, Hindi is written and set to `needs_review`, and the other 21 languages of the Eighth Schedule each have a `meta.php` file with the status `needed`.
- **Privacy of the package itself**: free-text fields are stored encrypted, the IP address and the user agent of a consent are off by default, only the age answer is stored (never a birth date), no personal data goes to your logs, and the package makes no outbound HTTP calls.
- **Documentation**: the README, `docs/rules.md` (each feature mapped to its section or rule, with a short quote and what is unverified) and `docs/translations.md` (how to add a language, and the Hindi strings that need review).
- **Tests**: every duty has at least one test whose name starts with the section or rule it covers. Pint, PHPStan (level 6) and Pest run in `composer ci`, and the suite runs a second time with immutable dates.

### Not in this release

A Consent Manager client or webhook, the duties of a Significant Data Fiduciary, the rules on transfers outside India, a parent check provider (DigiLocker or any other), a Filament plugin, a NativePHP bridge, sending anything to the Board, and translations beyond English and Hindi.
