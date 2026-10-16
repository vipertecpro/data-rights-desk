# The JSON feed

This file lists every call of the package's JSON feed, for apps that build their own screens: React, Vue or Svelte pages, a mobile app, or a NativePHP app. The paths use the default prefix, `data-rights`. The examples are real responses from the test suite, shortened where marked.

Send `Accept: application/json` with every call. Without it, Laravel answers a missing sign-in with a redirect to your sign-in page instead of a 401.

## Signing in

By default the feed uses the browser session (`'api' => ['middleware' => ['web'], 'auth' => 'auth']`). That suits pages served by the same Laravel app. Send the CSRF token with each `POST` and `DELETE`, as your front end already does for your own forms.

A mobile app signs in with a token instead:

1. Install Laravel Sanctum with `php artisan install:api`, and add `HasApiTokens` to your user model.
2. In `config/data-rights.php`, set `'api' => ['enabled' => true, 'middleware' => ['api'], 'auth' => 'auth:sanctum']`.
3. Give the app a token when the person signs in. The package has no sign-in call of its own. This is the usual Sanctum route for it:

```php
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

Route::post('/mobile/sign-in', function (Request $request) {
    $request->validate(['email' => 'required|email', 'password' => 'required', 'device' => 'required']);

    $user = User::where('email', $request->email)->first();

    if (! $user || ! Hash::check($request->password, $user->password)) {
        throw ValidationException::withMessages(['email' => 'These details do not match our records.']);
    }

    return ['token' => $user->createToken($request->device)->plainTextToken];
});
```

4. The app sends `Authorization: Bearer <token>` with every call to the feed.

In token mode the feed expects a token on every call that needs sign-in, including calls from your own web pages.

## Calls

| Method and path | Sign-in | What it does |
| --- | --- | --- |
| `GET /data-rights/api/notice` | no | The current notice, with its id and the purposes that need consent. `?lang=hi` picks the language |
| `GET /data-rights/api/purposes` | yes | Every purpose with the person's state, and whether she is a child |
| `POST /data-rights/api/consents/{purpose}` | yes | Gives consent for one purpose |
| `DELETE /data-rights/api/consents/{purpose}` | yes | Withdraws consent for one purpose |
| `GET /data-rights/api/requests` | yes | The person's requests |
| `POST /data-rights/api/requests` | yes | Makes a request |
| `GET /data-rights/api/requests/{id}` | yes, own | One request, with the reply |
| `GET /data-rights/api/requests/{id}/download` | yes, own | The data summary of a completed access request |

Consent calls are limited to 30 a minute for each person, and new requests to 6 a minute.

### GET notice

```json
{
    "id": "01m533mr0m0js1ve9mjy0pv2p0",
    "version": 1,
    "locale": "en",
    "hash": "8d6f0f8d689c6d87068ad6f007d9f8368cc2f42eca988616373f37b23bc0605c",
    "published_at": "2026-10-16T19:39:53+00:00",
    "url": "https://your-site.example/data-rights/notice/1",
    "markdown": "# How Example Shop uses your personal da... (shortened)",
    "html": "<h1>How Example Shop uses your personal ... (shortened)",
    "consent_purposes": [
        {
            "key": "account",
            "title": "Your account",
            "description": "To create your account, let you sign in and keep it secure.",
            "goods_or_services": "Sign-in, account settings and access to the features you use.",
            "data": ["Name", "Email address", "Password (stored as a hash)"],
            "basis": "consent",
            "legitimate_use": null,
            "tracking": false,
            "fourth_schedule": null,
            "withdrawal_effect": "We can no longer keep your account open.",
            "can_withdraw": true
        }
    ],
    "languages": [
        { "code": "en", "name": "English", "english_name": "English", "direction": "ltr" }
    ]
}
```

It returns 404 until a notice is published.

**A sign-up screen** shows the notice (or links to `url`) and one unticked box per entry in `consent_purposes`. Your own sign-up call then sends the ticked keys as `data_rights[purposes][]`, the notice `id` as `data_rights[notice]` and the `locale` as `data_rights[locale]`, and your code calls `DataRights::giveFromForm($user, $request)` after it creates the user. The record then names the notice version the person saw.

### GET purposes

```json
{
    "locale": "en",
    "notice": { "id": "01m533mr0m0js1ve9mjy0pv2p0", "version": 1, "locale": "en", "hash": "8d6f…", "published_at": "2026-10-16T19:39:53+00:00", "url": "https://your-site.example/data-rights/notice/1" },
    "purposes": [
        {
            "key": "marketing",
            "title": "Product news by email",
            "description": "To send you emails about new features and offers.",
            "goods_or_services": "A newsletter at most twice a month.",
            "data": ["Email address", "Name"],
            "basis": "consent",
            "legitimate_use": null,
            "tracking": false,
            "fourth_schedule": null,
            "withdrawal_effect": null,
            "can_withdraw": true,
            "allowed": false
        }
    ],
    "children": { "is_child": false, "needs_guardian_consent": false, "may_track": true },
    "languages": [{ "code": "en", "name": "English", "english_name": "English", "direction": "ltr" }]
}
```

The example shows one purpose. The response lists every purpose.

### POST and DELETE consents/{purpose}

No body. `POST` answers 201 and `DELETE` answers 200:

```json
{ "purpose": "marketing", "action": "given", "occurred_at": "2026-10-16T19:39:53+00:00", "notice_version": 1, "allowed": true }
```

The other answers:

- 404 with `{"message": "Unknown purpose.", "purpose": "..."}` for a purpose that is not in your config.
- 403 with `{"message": "A parent or guardian must confirm first.", "purpose": "..."}` when the person is a child (or has a lawful guardian) and nobody has confirmed yet. Send her to `/data-rights/preferences`, where the parent or guardian confirms.
- 409 when no notice is published in her language.

### POST requests

The body is `type` and an optional `details` (up to 5,000 characters). `type` is one of `access`, `correction`, `completion`, `updating`, `erasure` and `grievance`. The answer is 201:

```json
{
    "data": {
        "id": "01m533mr2s3je85q11swnkbbhx",
        "type": "access",
        "status": "received",
        "received_at": "2026-10-16T19:39:53+00:00",
        "due_at": "2027-01-14T19:39:53+00:00",
        "closed_at": null,
        "downloadable": false,
        "details": "Please send me my data.",
        "response": null,
        "download_url": null
    }
}
```

A missing or unknown `type` answers 422 with Laravel's usual validation errors.

### GET requests and GET requests/{id}

`GET requests` returns `{"data": [...]}` with the fields above, without `details`, `response` and `download_url`. `GET requests/{id}` returns one request with all fields. When an access request is completed, `downloadable` is `true` and `download_url` points at the download call:

```json
{
    "data": {
        "id": "01m533mr2s3je85q11swnkbbhx",
        "type": "access",
        "status": "completed",
        "closed_at": "2026-10-16T19:39:53+00:00",
        "downloadable": true,
        "response": "Your summary is ready.",
        "download_url": "https://your-site.example/data-rights/api/requests/01m533mr2s3je85q11swnkbbhx/download"
    }
}
```

The example leaves out some fields. Another person's request answers 403.

### GET requests/{id}/download

The data summary, as JSON with the keys `generated_at`, `business`, `personal_data`, `processing_activities`, `consents`, `shared_with` and `contact`. It answers 404 unless the request is a completed access request within `requests.download_days` (30 by default).

## The status values

A request's `status` is `received`, `in_progress`, `completed`, `rejected` or `cancelled`. `due_at` is at most 90 days after `received_at`.
