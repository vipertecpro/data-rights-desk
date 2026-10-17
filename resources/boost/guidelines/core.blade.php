## Data Rights Desk (vipertecpro/data-rights-desk)

This app uses Data Rights Desk, a package that helps a Laravel app meet India's Digital Personal Data Protection Act, 2023 and the DPDP Rules, 2025: purposes, a versioned notice, a consent ledger, data rights requests, retention, breach drafts, children's consent and a checker. It helps an app meet the law; never describe the app as "compliant", "certified" or "official", and never give legal advice.

### Rules for working in this app

- The purposes, their legal basis (consent or a section 7 legitimate use), the contact details and any Fourth Schedule key are the owner's decisions. Ask; never invent them.
- Check consent before using data for a purpose: `DataRights::allows($user, 'purpose')`, the `consent:purpose` middleware, or `@@dataRightsAllows('purpose')`.
- Every table with personal data needs an Exporter and an Eraser (or a `HasRetention` model). Data kept only for one consent purpose also needs a PurposeEraser. Create them with `php artisan data-rights:make-source Name` (add `--purpose=key` for a PurposeEraser) and list them in `sources` in `config/data-rights.php`.
- After changing purposes, the contact details, `APP_URL` or the package version, run `php artisan data-rights:notice` to publish a new notice version.
- Run `php artisan data-rights:check --json` after setup changes. It must show no errors; explain each warning to the owner instead of silencing it.
- Do not lower the legal floors: replies within 90 days at most (`requests.response_days`), records kept at least 365 days (`retention.keep_days`).
- Do not copy names or emails into the package's tables; it stores the person's model type and id.

### Common code

@verbatim
<code-snippet name="Check consent and log processing" lang="php">
use Vipertecpro\DataRightsDesk\Facades\DataRights;

if (DataRights::allows($user, 'marketing')) {
    // send the newsletter
    DataRights::logProcessing($user, 'marketing', 'newsletter.sent');
}
</code-snippet>

<code-snippet name="Consent at sign-up (Blade form, then the Fortify action)" lang="php">
// resources/views/.../register.blade.php
<x-data-rights::consent-fields :required="['account']" ask-age />

// app/Actions/Fortify/CreateNewUser.php
use Vipertecpro\DataRightsDesk\Rules\AnswersAge;
use Vipertecpro\DataRightsDesk\Rules\IncludesPurpose;

'data_rights.purposes' => [new IncludesPurpose('account')],
'data_rights.age' => [new AnswersAge],
// after User::create(...):
DataRights::giveFromForm($user, $input);
</code-snippet>
@endverbatim

The README, `docs/api.md` (the JSON feed) and `docs/rules.md` (each feature mapped to its section or rule) are in `vendor/vipertecpro/data-rights-desk/`.
