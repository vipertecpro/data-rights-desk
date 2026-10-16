# Data Rights Desk for Laravel

![Latest version](https://img.shields.io/packagist/v/vipertecpro/data-rights-desk) ![Tests](https://img.shields.io/github/actions/workflow/status/vipertecpro/data-rights-desk/tests.yml?branch=main&label=tests) ![PHP](https://img.shields.io/packagist/dependency-v/vipertecpro/data-rights-desk/php) ![Laravel](https://img.shields.io/packagist/dependency-v/vipertecpro/data-rights-desk/laravel%2Fframework) ![Platforms](https://img.shields.io/badge/platforms-web-informational) ![Licence](https://img.shields.io/packagist/l/vipertecpro/data-rights-desk) ![Downloads](https://img.shields.io/packagist/dt/vipertecpro/data-rights-desk)

Notices, consent records, data rights requests, retention, breach drafts and a dashboard that help a Laravel app meet India's Digital Personal Data Protection Act, 2023 and the DPDP Rules, 2025.

For any Laravel app with users in India, wherever your business is based (section 3 of the Act).

I built Data Rights Desk and I maintain it. It is free and MIT licensed.

**Dates.** The duties of a business (a Data Fiduciary) under the Rules start on 13 May 2027: Rule 1(4) of the DPDP Rules, 2025 (G.S.R. 846(E), published 13 Nov 2025) says Rules 3, 5 to 16, 22 and 23 "shall come into force eighteen months after the date of publication". The commencement notification for the Act's sections, G.S.R. 843(E), gives the same date (from my research notes; I have not checked it against the gazette text). Some sources give the 14th, because they date the gazette a day later. Plan for the 13th. Consent Manager registration starts on 13 Nov 2026 (Rule 1(3): "one year after the date of publication"). Before 13 May 2027, nothing is in force for a normal business app.

## What you get

- **Purposes** that you define in config, with plain-language text in each language you offer.
- **A notice** built from those purposes, in each language, with every version kept and hashed.
- **A consent ledger.** Append-only, one row for every give or withdraw, tied to the notice version the person saw. A proof file per person.
- **Consent pages** with one click to give and one click to withdraw, and a JSON API if you build your own screens.
- **Data rights requests**: a summary of the person's data, correction, completion, updating, erasure and grievances. Each has a clock, an identity check and a reply.
- **Nominations**, so a person can name someone to use her rights "in the event of death or incapacity" (section 14(1)).
- **A retention keeper** with legal holds. Logs and records are kept for at least 365 days. Erasure after three years of inactivity, with a 48-hour warning, is there as a switch.
- **A breach register** with drafts of the alert to the Board, the 72-hour report, and the message to each affected person.
- **Children**: an age question, parent or guardian consent through a check that you provide, and no tracking of children.
- **A contact block** on the rights page, in the notice and in every email.
- **A processor register.**
- **A dashboard** for staff and **a checker** that looks for gaps.

The package makes no outbound HTTP calls of its own. It loads no fonts, scripts or analytics. Emails go out through your app's own mail setup.

## Requirements

- PHP 8.3 or newer.
- Laravel 12 or 13. Laravel 12 gets security fixes until 24 Feb 2027, which is before the duties start. Laravel 13 gets them until 17 Mar 2028 (Laravel's support table, checked on 16 Oct 2026).
- A database. Every release is tested on SQLite, MySQL 8.0, MariaDB 11, PostgreSQL 16 and SQL Server 2022.
- Any front end. The package's own pages are plain Blade, and its JSON feed serves screens you build yourself. I tested it end to end with Laravel's Livewire, React, Vue and Svelte starter kits.
- A mail channel (or another notification channel) for the emails. The package's notifications are queued, so a queue worker must be running (unless your queue connection is `sync`).

## Install

Follow the steps in order.

**1. Add the package.**

```
composer require vipertecpro/data-rights-desk
```

The service provider and the `DataRights` facade are found automatically.

**2. Publish the config and the migrations.**

```
php artisan data-rights:install
```

This writes `config/data-rights.php` and the migrations into your app, then prints the next steps. It also reads your app (its database, its front end and whether Laravel Sanctum is installed) and prints the steps that fit it. It never edits your code.

**3. Migrate.**

```
php artisan migrate
```

Every table starts with `data_rights_` and uses ULID primary keys. The columns that point at a person use Laravel's `morphs`. If your user table uses UUID or ULID keys, call `Schema::morphUsingUuids()` (or `Schema::morphUsingUlids()`) in a service provider before you migrate.

**4. Add the trait to your user model.**

```php
use Vipertecpro\DataRightsDesk\Concerns\HasDataRights;

class User extends Authenticatable
{
    use HasDataRights;
}
```

If your people are not `App\Models\User`, set `DATA_RIGHTS_SUBJECT_MODEL` in `.env`. The email column is `email` unless you change `subject_email_column`.

The trait gives the model the `dataRightsConsents()`, `dataRightsRequests()` and `dataRightsProfile()` relations and a `consentsTo($purpose)` method. When the model is deleted, the trait marks the person as erased. When a soft-deleted model is restored, it clears that mark.

**5. Set the contact details in `.env`.**

```
DATA_RIGHTS_BUSINESS_NAME="Your business"
DATA_RIGHTS_CONTACT_NAME="Name of the person who answers"
DATA_RIGHTS_CONTACT_ROLE="Grievance officer"
DATA_RIGHTS_CONTACT_EMAIL=
DATA_RIGHTS_CONTACT_PHONE=
DATA_RIGHTS_CONTACT_ADDRESS=
DATA_RIGHTS_BOARD_URL=
```

Fill in the email or the phone, or both. The checker reports an error without one. The business name falls back to `APP_NAME`. Leave `DATA_RIGHTS_BOARD_URL` empty until the Board publishes a way to complain. The notice then describes the route without a link.

**6. Replace the example purposes.**

Open `config/data-rights.php`. It ships three example purposes, in English and Hindi. Replace them with your own. The checker warns while the examples are unchanged. One purpose looks like this:

```php
'orders' => [
    'title' => ['en' => 'Your orders'],
    'description' => ['en' => 'To take your order, deliver it and send you updates about it.'],
    'goods_or_services' => ['en' => 'Delivery of what you ordered.'],
    'data' => ['en' => ['Name', 'Delivery address', 'Phone number']],
    'basis' => 'consent',
    'legitimate_use' => null,
    'tracking' => false,
    'fourth_schedule' => null,
    'withdrawal_effect' => ['en' => 'Your open orders cannot be delivered.'],
],
```

- `basis` is `consent` (section 6) or `legitimate_use` (section 7). For `legitimate_use`, set `legitimate_use` to the clause, `a` to `i`.
- `tracking` is `true` when the purpose tracks people, monitors their behaviour or targets advertising. Without a Fourth Schedule key, it is never allowed for children.
- `fourth_schedule` takes a key from `A1` to `A5` or `B1` to `B6`. Use it only after you have checked that the Schedule's condition is met for that purpose.
- A text field takes a plain string (for your default language) or an array with one entry per language.

The package does not choose your purposes or your legal basis. That decision is yours.

The shipped config offers English and Hindi (`'locales' => ['en', 'hi']`). The notice command stops until every purpose has text in every offered language. Write the Hindi text too, or set `'locales' => ['en']`. The checker then warns that Hindi is not offered. That is a warning, not an error.

**7. Publish the notice.**

```
php artisan data-rights:notice
```

This builds the notice in every offered language and publishes version 1. Run it again whenever you change a purpose, the contact details or the Board URL. The checker reports an out-of-date notice as an error. `--pretend` shows what would change without publishing. Consent cannot be recorded until a notice is published in the person's language.

**8. Define the dashboard gate.**

```php
use Illuminate\Support\Facades\Gate;

Gate::define('viewDataRightsDesk', fn ($user) => $user->is_admin);
```

Put it in a service provider's `boot` method. Until you define it, the dashboard opens only when the app runs in the `local` environment, like Horizon. The checker warns if production still uses that default.

**9. Write a source for each part of your app that holds personal data.**

```
php artisan data-rights:make-source Orders
```

This creates `app/DataRights/Orders.php`. It implements two contracts:

- `Exporter` gives the rows you hold about a person. They go into the data summary.
- `Eraser` erases them, and says what it kept and why.

Fill in `tables()`, `processing()`, `export()` and `erase()`. Skip records where `$context->isHeld($record)` is true, because a legal hold covers them. Report what another law makes you keep with `ErasureOutcome::retained($count, 'reason', $until)`. The reason is shown to the person and stored in the request result, so keep personal data out of it. Without at least one Eraser in `sources`, an erasure request cannot be carried out: Erase refuses and tells the person nothing, and the checker reports an error. Then add the class to `sources` in the config:

```php
'sources' => [
    App\DataRights\Orders::class,
],
```

**10. Schedule the retention keeper.**

In `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('data-rights:retain')->daily();
```

**11. Run the checker.**

```
php artisan data-rights:check
```

It exits with code 1 when it finds an error, so you can run it in CI. `--json` prints the report as JSON.

- **Errors**: no contact, no notice or an out-of-date one, a listed language that has no translation, `response_days` above 90, requests past their due date, a breach past its 72-hour report time with no report marked as sent, no Eraser in `sources`.
- **Warnings**: `keep_days` below 365, Hindi not offered, an offered language still marked `needs_review` (the shipped Hindi is, until a fluent speaker has read it), an empty Board URL, the example purposes unchanged, a table with personal columns that no source or retention model covers, a purpose that needs consent but that your code never checks, a processor with no contract reference or security clause, children checks on with no parent check bound, and the default gate in production.

The checker looks at what the package can see. It cannot tell whether your app meets the Act.

**Optional steps.**

- Add the `data-rights.activity` middleware to your signed-in routes if you turn on inactivity erasure. It notes when a person was last seen (at most once a day), clears a pending erasure warning, and clears a past erasure when a person whose account was kept comes back.
- Bind your own parent check if your app has users under 18 (see "Using it in code").
- Run `php artisan data-rights:notify-existing` to send the notice to people who joined before the duties start. If you already hold consents from before you installed the package, record each one with `DataRights::consents()->importLegacy($user, 'purpose', $givenAt)`.

## Using it in code

Check consent before you use a person's data for a purpose:

```php
use Vipertecpro\DataRightsDesk\Facades\DataRights;

if (DataRights::allows($user, 'marketing')) {
    // send the newsletter
}

// A short label, never personal data. This feeds the data summary and the one-year log.
DataRights::logProcessing($user, 'marketing', 'newsletter.sent');
```

`allows()` answers per purpose. A consent purpose needs a "given" row as the latest entry. A legitimate use under section 7(b) to (i) is allowed, and section 7(a) is allowed until the person says she does not want the use.

For a person who has said she is under 18, or who has a lawful guardian, no purpose is allowed until a parent or the guardian has confirmed. That includes legitimate uses. Section 9(1) asks for that consent "before processing any personal data of a child", and the Fourth Schedule has to exempt section 7(b) processing by name, so the package reads section 9(1) as covering every purpose. After the confirmation, a consent purpose also needs a "given" row from the parent, and a tracking purpose stays denied for a child. A purpose with a Fourth Schedule key skips these checks.

`denies()` is the opposite. On your user model, `$user->consentsTo('marketing')` does the same.

If you build your own screens, `DataRights::give($user, 'marketing', [...])` and `DataRights::withdraw(...)` write ledger rows. The options are `channel`, `locale`, `ip`, `user_agent`, `given_by` and `guardian_consent`. An unknown purpose throws `UnknownPurpose`. Giving consent without a published notice throws `NoticeMissing`. `DataRights::proof($user, 'marketing')` returns the proof as an array.

**At sign-up.** Put the consent box in your registration form. It shows the notice link and one unticked box per purpose that needs consent:

```blade
<x-data-rights::consent-fields :required="['account']" />
```

Then record the ticked purposes when you create the user. The record names the notice version the form showed. In a Fortify app this goes in `app/Actions/Fortify/CreateNewUser.php`:

```php
use Vipertecpro\DataRightsDesk\Facades\DataRights;
use Vipertecpro\DataRightsDesk\Rules\IncludesPurpose;

Validator::make($input, [
    // your other rules
    'data_rights.purposes' => ['required', 'array', new IncludesPurpose('account')],
])->validate();

$user = User::create([/* ... */]);

DataRights::giveFromForm($user, $input); // or pass the Request
```

Which purposes your sign-up needs is your decision. Leave out `:required` and the rule if none is needed. With React, Vue or Svelte, post `data_rights[purposes][]`, `data_rights[notice]` and `data_rights[locale]` from your own form (the notice comes from `/data-rights/api/notice`), then call the same `giveFromForm()`.

**On a route:**

```php
Route::middleware(['auth', 'consent:marketing'])->get('/newsletter', NewsletterController::class);
```

A guest is sent to sign in, as Laravel does it. A signed-in person without consent gets a JSON 403 (`message`, `purpose` and `preferences_url`) for a JSON request, or a redirect to the consent choices page. The `consent` alias is registered only if your app has not already used that name. `data-rights.consent` is always registered.

**In Blade:**

```blade
@dataRightsAllows('analytics')
    {{-- shown only when the signed-in person has allowed this purpose --}}
@endDataRightsAllows

@dataRightsTracking
    {{-- shown only when the person may be tracked --}}
@endDataRightsTracking
```

`@dataRightsTracking` checks the rule for children only, not consent. Use it together with `@dataRightsAllows`. For a guest, the age is unknown, so `children.unknown_age_is_child` decides.

**Events.** Listen to them to stop work and to tell your processors:

```php
use Illuminate\Support\Facades\Event;
use Vipertecpro\DataRightsDesk\Events\ConsentWithdrawn;

Event::listen(ConsentWithdrawn::class, function (ConsentWithdrawn $event) {
    // $event->subject, $event->purpose, $event->record
    // Stop the processing, and tell the processors that work for you.
});
```

The events are `ConsentGiven`, `ConsentWithdrawn`, `NoticePublished`, `RequestReceived`, `RequestStatusChanged`, `PersonalDataErased`, `BreachRecorded`, `ErasureWarningSent` and `GuardianConsentRecorded`. Stopping processing after a withdrawal is your app's job. The package tells you and keeps the record.

**Children.** When `children.enabled` is true (the default), the consent choices page asks whether the person is 18 or older and stores only that answer. The answer is locked once given; staff can correct it on the Children page of the dashboard, with a note that is stored encrypted. For someone under 18, a parent must confirm before any purpose is allowed. The package cannot check a parent. Write a class that implements `ParentalConsentVerifier`, and bind it:

```php
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Vipertecpro\DataRightsDesk\Contracts\ParentalConsentVerifier;
use Vipertecpro\DataRightsDesk\Enums\GuardianKind;
use Vipertecpro\DataRightsDesk\Enums\GuardianMethod;
use Vipertecpro\DataRightsDesk\Support\GuardianVerification;

class MyParentalVerifier implements ParentalConsentVerifier
{
    public function verify(Model $subject, Request $request): GuardianVerification
    {
        // Check that the person is an adult parent (Rule 10).
        // Throw Illuminate\Validation\ValidationException when the check fails.

        return new GuardianVerification(GuardianKind::Parent, GuardianMethod::DetailsProvided, null, 'Parent name', 'your reference');
    }
}
```

Bind it in the `register()` method of a service provider:

```php
$this->app->bind(ParentalConsentVerifier::class, MyParentalVerifier::class);
```

The methods for a parent are `reliable_details_held`, `details_provided` and `virtual_token`. The methods for a lawful guardian of a person with disability are `court_appointed`, `designated_authority` and `local_level_committee`. The same class handles both. Until you bind your own class, the default refuses every parent check, and the checker warns. There is no screen to mark a person as having a lawful guardian. Set it in code with `Vipertecpro\DataRightsDesk\Models\SubjectProfile::for($user)->update(['has_lawful_guardian' => true])`.

**Retention for your own models.** Add `HasRetention` to a model and list the model in `retention.models`. The keeper then erases records older than `max(retentionDays(), keep_days)`:

```php
use Vipertecpro\DataRightsDesk\Concerns\HasRetention;

class Order extends Model
{
    use HasRetention;

    public function retentionPurpose(): string
    {
        return 'orders';
    }
}
```

You can also override `retentionDays()`, `retentionDateColumn()` (default `updated_at`), `retentionQuery()` and `eraseForRetention()` (default `delete()`).

**Legal holds.** Place a hold on a person from the Retention page of the dashboard, or on any model in code with `DataRights::retention()->placeHold($model, 'reason', 'law reference', $until)`. A held record is not erased by the keeper, and a held person is not erased by a request. A hold on a person does not protect the records of your `HasRetention` models: to keep one of those, hold the record itself, for example `placeHold($order, ...)`.

## The pages it adds

All paths below use the default prefixes. Change them, or switch a group off, under `routes` in the config.

**Public pages** (prefix `data-rights`, middleware `web`):

| Path | Sign-in | What it is |
| --- | --- | --- |
| `/data-rights` | no | The rights page: the contact, how to make a request, the identifier needed, the reply time, how to complain to the Board |
| `/data-rights/notice` | no | The current notice. `?lang=hi` switches the language |
| `/data-rights/notice/{version}` | no | A past version of the notice |
| `/data-rights/preferences` | yes | Consent per purpose, the age question, the parent or guardian status |
| `/data-rights/requests` | yes | The person's requests, a new request form and nominations |
| `/data-rights/requests/{request}` | yes, own | One request: its status, the reply, a cancel button, and a download of the data summary for a completed access request |

JSON for apps with their own screens is under `/data-rights/api`. The notice needs no sign-in. The purposes, consent, withdrawal and requests need one. Creating a request is limited to six a minute for each person.

By default the JSON feed uses the browser session, which suits Blade, Livewire and Inertia pages on the same site. A mobile app, including one built with NativePHP, signs in with a token instead. Install Laravel Sanctum (`php artisan install:api`, then add `HasApiTokens` to your user model) and set this in `config/data-rights.php`:

```php
'routes' => [
    // ...
    'api' => ['enabled' => true, 'middleware' => ['api'], 'auth' => 'auth:sanctum'],
],
```

The app then sends `Authorization: Bearer <token>` with every call, and consent given this way is recorded with the channel `api`. In this mode the feed expects a token on every call, including from your own web pages. The JSON feed works even when the public pages are switched off.

**Dashboard** (prefix `data-rights-desk`, middleware `web` and `auth`, plus the `viewDataRightsDesk` gate on every route):

| Page | What it does |
| --- | --- |
| Overview | Open requests and the nearest due date, overdue requests, breaches waiting for the Board report, consents per purpose, the last retention run, checker errors |
| Requests | List with filters. Record a request that came by email or phone. Check identity, change the status, preview the data summary, complete or reject with a reply, erase. An erasure request is closed only by Erase, which also cancels the person's other open requests |
| Consents | Look up a person by email. See her ledger. Download the proof |
| Notices | See every version. Publish a new one |
| Breaches | Record a breach, add affected people by email, get the three drafts, mark the Board alert and report as sent, notify people |
| Retention | Past runs. Place or release a legal hold on a person. Record contact from a person (a call, a letter or an email), which clears a pending erasure warning (Rule 8(2)) |
| Processors | The register of processors and other fiduciaries |
| Children | Counts of people who said they are under 18, the guardian consents, and a form to correct a person's age answer with a note |
| Check | The checker report |

The dashboard also has read-only JSON under `/data-rights-desk/api`. It is English only.

The public pages are plain Blade with one hand-written stylesheet. They follow the visitor's light or dark setting. To change them, publish the views with `php artisan vendor:publish --tag=data-rights-views`. The other publish tags are `data-rights-config`, `data-rights-migrations` and `data-rights-lang`.

## Commands

| Command | What it does |
| --- | --- |
| `php artisan data-rights:install` | Publishes the config and the migrations, and prints the next steps |
| `php artisan data-rights:notice` | Builds the notice in every offered language and publishes a new version when any of them changed. It refuses and names what is missing when a language or a purpose text is incomplete. `--pretend` changes nothing |
| `php artisan data-rights:notify-existing` | Sends the current notice once to each person created before `--before` (default 2027-05-13) who has not had it. `--chunk` sets the batch size. `--pretend` only counts |
| `php artisan data-rights:retain` | Runs the retention keeper and records the run. `--pretend` counts and changes nothing |
| `php artisan data-rights:proof {email}` | Writes the consent proof for one person as JSON. `--purpose=` limits it to one purpose. `--output=` writes it to a file |
| `php artisan data-rights:breach-draft {breach} {kind=report}` | Prints the `alert`, `report` or `person` draft for a breach (use the breach id). `--lang=` sets the language of the person draft |
| `php artisan data-rights:check` | Runs the checker. Exit code 1 on errors. `--json` prints JSON |
| `php artisan data-rights:make-source {name}` | Creates an Exporter and Eraser class in `app/DataRights` |

The retention keeper works in this order:

1. It raises `keep_days` to 365 if it is lower.
2. It deletes processing logs older than `keep_days`, unless the person is on legal hold.
3. It erases records of the models in `retention.models`, skipping held ones.
4. If inactivity erasure is on, it warns people who are about to reach the mark, then erases those who were warned and did not come back (only when at least one Eraser is configured).
5. It deletes the package's records of people erased more than `keep_days` ago, but only those dated on or before the erasure. A person who comes back (signs in through `data-rights.activity`, gives consent, makes a request, is restored, or is recorded as having contacted you) is no longer treated as erased, and her newer records stay.
6. It deletes requests that closed more than `keep_days` ago.
7. It records the run with counts only.

Inactivity erasure is off by default. It is for the three classes that the Third Schedule names: e-commerce entities with at least two crore registered users in India, online gaming intermediaries with at least fifty lakh, and social media intermediaries with at least two crore. The package cannot tell whether you are one of them. You turn it on.

## What it stores, and for how long

The package does not copy your users' names or emails. It stores the type and id of the person (your user model) and reads the rest from your tables. The exceptions are the fields marked encrypted below, such as a nominee's name and contact, a guardian's name, and the text a person types into a request. These are the tables it creates. "Encrypted" means Laravel's encrypted cast, so the raw value in the database differs from the text.

`keep_days` is 365 unless you set it higher. It is never lower.

| Table | What it holds | Encrypted | Kept for |
| --- | --- | --- | --- |
| `data_rights_subjects` | Per person: the age answer (adult or under 18), when and why staff changed it and who did, whether she has a lawful guardian, when she was last seen, the erasure warning and the erased time | `age_note` | Deleted `keep_days` after the person is erased, once none of her package records is left |
| `data_rights_notices` | Every published version of the notice: the text, its hash, a snapshot of the purposes | none | Kept. It shows what a person saw |
| `data_rights_notice_deliveries` | Which person was sent which notice version, by which channel, and when | none | Until `keep_days` after the person is erased |
| `data_rights_consents` | The ledger: purpose, given or withdrawn, the notice version and hash, language, channel, who gave it, Consent Manager details, the time. The IP address and the user agent only if you turn them on | none | While the person exists. Rows are never changed. They are deleted `keep_days` after she is erased |
| `data_rights_requests` | Type, status, dates, how identity was checked, a result summary (counts, and the reasons your Erasers give) | `details`, `response`, `identity_method` | Open requests stay. A closed request is deleted `keep_days` after it closed |
| `data_rights_request_events` | The status changes of a request | `note` | With its request |
| `data_rights_nominations` | The nominee's name, contact and relationship | all three | Deleted at once when erasure runs for the person (a request or inactivity). If the user model is deleted some other way, they go `keep_days` later. Removing a nomination deletes it at once |
| `data_rights_guardian_consents` | Which kind of guardian, which method, when, and which verifier class | `guardian_name`, `verification_reference` | Until `keep_days` after the person is erased |
| `data_rights_processors` | Your register of processors and other fiduciaries: purposes, data shared, contract reference, security clause | none | Until you remove it. You can mark a processor inactive |
| `data_rights_breaches` | The breach: title, description, dates, the Board alert and report times | `nature`, `extent`, `location`, `likely_impact`, `facts`, `mitigation`, `findings`, `remedial`, `consequences_for_people`, `safety_steps` | The package never deletes breaches |
| `data_rights_breach_subjects` | Which people a breach affected, whether and when each was told | none | Until `keep_days` after the person is erased |
| `data_rights_legal_holds` | What is held, why, the law reference, who placed it, dates | `reason` | The package never deletes holds. A released hold stays as a record |
| `data_rights_retention_runs` | When the keeper ran and the counts | none | The package never deletes them |
| `data_rights_processing_logs` | The person, the purpose, a short activity label (up to 120 characters) and the time | none | Deleted when older than `keep_days`, unless the person is on legal hold |

The IP address and the user agent of a consent are off by default (`consent.record_ip` and `consent.record_user_agent`). When you turn them on they are stored in plain text. The package never stores a birth date, and it writes no personal data to your logs.

The emails are queued. Until a queued email is sent, the job in your queue (and in `failed_jobs`, if it fails) holds the person's address, and for an erasure reply the address captured before erasure. Clear failed jobs you no longer need. If you use the `database` notification channel, the `notifications` table holds only the type, a reference and dates.

Your own tables are not touched. The Exporters and Erasers that you write decide what happens to them.

## Where it fits

- **An online store**: marketing email consent, orders and addresses in a source, and an Eraser that keeps what another law makes you keep.
- **A school or edtech app with users under 18**: the age question, a parent check that you provide, no tracking of children, and a Fourth Schedule key for the purposes an educational institution may use once it has checked the condition.
- **A clinic booking app**: consent per purpose, the encrypted request and breach text, and a Fourth Schedule key for health services once the condition is checked.
- **A lending or fintech app**: purposes and legitimate uses side by side, a register of the processors you share data with, and legal holds on records that other laws make you keep.
- **A SaaS tool**: a data summary built from your sources, a one-year processing log, and breach drafts.
- **A job portal**: candidate data in a source, erasure on request, and retention rules for old applications.
- **A food delivery app**: location and marketing as separate consents, a processor register for the payment and delivery companies you share data with, and order records that the keeper erases only after the one-year minimum.
- **An online game**: the age question, no tracking of children, and the inactivity erasure switch if you are in the large-game class.

## What it does not do

- It does not decide your purposes or your legal basis. You do.
- It does not make your app compliant on its own. It gives you parts. Your own code, contracts and processes are still needed.
- It does not verify a person's age. It stores her answer.
- It does not verify a parent or a lawful guardian. You write the check and bind it. It has no link to DigiLocker or to any other provider.
- It does not send anything to the Board. It drafts the alert and the report, and you check them and send them.
- It does not act as a Consent Manager, and it has no client for a Consent Manager's API. It can record consent that arrived through one.
- It does not cover the extra duties of a Significant Data Fiduciary, such as impact assessments and audits.
- It does not cover the rules on transfers of data outside India.
- It does not erase data from places it cannot see. Your Erasers must cover your tables, backups, logs, search indexes and the third parties you use.
- It does not stop your processors. It fires `ConsentWithdrawn`, and you act on it.
- It does not find personal data on its own. The checker looks at column names and at calls in your code, never at the data.
- It has no Filament plugin and no NativePHP bridge.
- It ships no translations beyond English and Hindi.

## Languages

English is ready. Hindi is written and under review. The other 21 languages of the Eighth Schedule (Assamese, Bengali, Bodo, Dogri, Gujarati, Kannada, Kashmiri, Konkani, Maithili, Malayalam, Manipuri, Marathi, Nepali, Odia, Punjabi, Sanskrit, Santali, Sindhi, Tamil, Telugu and Urdu) need translation. Each has a `meta.php` file with the status `needed`. A language with that status cannot be offered.

To add one, publish the language files with `php artisan vendor:publish --tag=data-rights-lang`. In `lang/vendor/data-rights/{code}/`, translate `notice.php`, `public.php` and `mail.php`, and set `status` in `meta.php`. Then add the code to `locales` in the config, add the language to your purposes, and run `php artisan data-rights:notice`. The `docs/translations.md` file included in the package has the steps and the list of Hindi strings that still need a fluent reader.

## Testing

In a clone of the package, run `composer install` first. Then:

```
composer test      # the Pest suite, on SQLite in memory
composer analyse   # PHPStan level 6 with Larastan
composer format    # Pint, laravel preset
composer ci        # Pint check, PHPStan, Pest, and Pest again with immutable dates
```

Every duty has at least one test, and the test name starts with the section or rule it covers, for example `Rule 7(2):` or `s.6(10):`. The `docs/rules.md` file included in the package maps each feature to its section or rule, with a short quote from the gazette text. It also marks what could not be verified.

The repository has a GitHub Actions file for PHP 8.3, 8.4 and 8.5 with Laravel 12 and 13 on SQLite, MySQL 8 and PostgreSQL 16.

## Not legal advice

Data Rights Desk is software, not legal advice. It helps an app meet the DPDP Act 2023 and the DPDP Rules 2025. It does not make an app compliant by itself, and it does not guarantee any outcome. The dates, the sections and the rules in this package are my reading of the gazette text, and some points are marked unverified in the `docs/rules.md` file. Check your own duties, your purposes and your legal basis with someone who knows your business and the law.

## Independence

Data Rights Desk is an independent open-source project. It is not affiliated with or endorsed by Laravel, the Government of India, MeitY or the Data Protection Board of India.

## Licence

MIT. See the `LICENSE` file included.
