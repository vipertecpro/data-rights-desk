---
name: data-rights-desk-setup
description: Set up or extend Data Rights Desk (vipertecpro/data-rights-desk) in a Laravel app to help it meet India's DPDP Act - install, purposes, notice, sources (Exporter, Eraser, PurposeEraser), sign-up consent, routes, mobile tokens, and the checker loop.
---

# Data Rights Desk setup

## When to use this skill

Use it when installing Data Rights Desk, adding a table or purpose that holds personal data, wiring consent into a sign-up form or a mobile app, or fixing what `php artisan data-rights:check` reports. The full manual is `vendor/vipertecpro/data-rights-desk/README.md`.

## Ask the owner first

Never decide these yourself. Ask, and write down the answers in the config:

- The purposes: what the app uses personal data for, the data each uses, and for each one whether it needs consent (section 6) or is a legitimate use (section 7, clause a to i).
- The contact person and email or phone (Rule 9), and the business name.
- Whether people under 18, or people with a lawful guardian, can sign up.
- Whether a Fourth Schedule key applies to a purpose. Only the owner can confirm the condition.

## Install

1. `composer require vipertecpro/data-rights-desk`, then `php artisan data-rights:install`. Read its "Your app" and "Before you go on" lines and act on them (UUID or ULID user keys need `morph_key_type` before migrating; no `login` route needs `redirectGuestsTo`).
2. `php artisan migrate`.
3. Add the `HasDataRights` trait to the user model, next to its other traits.
4. Set `APP_URL` and the `DATA_RIGHTS_*` contact keys in `.env`.
5. Replace the example purposes in `config/data-rights.php` with the owner's. Give text for every language in `locales`, or set `'locales' => ['en']`.
6. `php artisan data-rights:notice`.
7. Define the `viewDataRightsDesk` gate for the owner's staff only.
8. Schedule `data-rights:retain` daily in `routes/console.php`.

## Cover the personal data

- For every table with personal data: `php artisan data-rights:make-source Name`. Fill in `name()`, `tables()`, `processing()`, `export()` and `erase()`. For the users table, `erase()` deletes or force-deletes the user row. Skip records where `$context->isHeld($record)` is true. Return `ErasureOutcome::erased($n)`, `ErasureOutcome::retained($n, 'reason', $until)` or `ErasureOutcome::nothing()`.
- For data kept only for one consent purpose (a mailing list for `marketing`): `php artisan data-rights:make-source Newsletter --purpose=marketing`.
- List every source in `sources`. Packages such as spatie/laravel-activitylog, Telescope, Pulse and Sanctum keep personal data too: cover their tables or add them to `checker.ignore_tables` with the owner's reason.

## Consent in the app

- Sign-up (Blade or Livewire): `<x-data-rights::consent-fields :required="['account']" ask-age />` in the form, `IncludesPurpose` and `AnswersAge` rules, then `DataRights::giveFromForm($user, $input)` after creating the user. Use the owner's purpose keys, not `account`, unless that purpose exists.
- React, Vue, Svelte or mobile: read `GET /data-rights/api/notice` for the notice `id` and `consent_purposes`, post `data_rights[purposes][]`, `data_rights[notice]` and `data_rights[locale]` with the app's own sign-up call, then call `giveFromForm()`.
- Mobile or NativePHP: Sanctum tokens, and `'api' => ['enabled' => true, 'middleware' => ['api'], 'auth' => 'auth:sanctum']` in the config. Every call sends `Accept: application/json`. The calls are listed in `vendor/vipertecpro/data-rights-desk/docs/api.md`.
- Before using data for a purpose: `DataRights::allows($user, 'purpose')` or the `consent:purpose` middleware.
- If the app already stores its own consent flag (for example `users.newsletter`), import past consents with `DataRights::consents()->importLegacy($user, 'purpose', $givenAt)` and keep the flag in step through the `ConsentGiven` and `ConsentWithdrawn` events, or replace it with `allows()`.

## Finish with the checker

Run `php artisan data-rights:check --json` and repeat until `errors` is empty. Fix errors in code or config; for each warning, either fix it or tell the owner why it stays. Then run the app's own tests: a registration test must publish the notice first (`DataRights::notices()->publish()`) and post `data_rights` with the required purposes.

## Do not

- Do not call the app "compliant", "certified" or "official", or present anything as legal advice.
- Do not raise `requests.response_days` above 90 or lower `retention.keep_days` below 365.
- Do not pre-tick consent boxes or bundle purposes into one "accept all" control.
- Do not store birth dates; the package stores only the age answer.
