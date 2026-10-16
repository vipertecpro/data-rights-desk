<?php

/*
|--------------------------------------------------------------------------
| Data Rights Desk
|--------------------------------------------------------------------------
|
| Helps a Laravel app meet the duties of a Data Fiduciary under India's
| Digital Personal Data Protection Act, 2023 and the DPDP Rules, 2025.
| Business duties start on 13 May 2027. This file is not legal advice:
| check every setting with someone who knows your business.
|
*/

return [

    // Data Fiduciary shown on notices, the rights page and every reply.
    'business' => [
        'name' => env('DATA_RIGHTS_BUSINESS_NAME', env('APP_NAME')),
        'website' => env('APP_URL'),
    ],

    // s.8(9), Rule 9, s.6(3): business contact of the Data Protection Officer
    // (if any) or a person who can answer questions about the processing.
    'contact' => [
        'name' => env('DATA_RIGHTS_CONTACT_NAME'),
        'role' => env('DATA_RIGHTS_CONTACT_ROLE', 'Data protection contact'),
        'email' => env('DATA_RIGHTS_CONTACT_EMAIL'),
        'phone' => env('DATA_RIGHTS_CONTACT_PHONE'),
        'address' => env('DATA_RIGHTS_CONTACT_ADDRESS'),
    ],

    // Rule 3(c)(iii), s.5(1)(iii): how to complain to the Board. The Board had no
    // published complaint channel when this was written (2026-10-16), so the URL
    // is empty by default.
    'board' => [
        'url' => env('DATA_RIGHTS_BOARD_URL'),
    ],

    // s.5(3), s.6(3): languages offered. 'en' is English; others are Eighth Schedule codes.
    'default_locale' => 'en',
    'locales' => ['en', 'hi'],

    // The Data Principal model (used by commands and the dashboard lookup).
    'subject_model' => env('DATA_RIGHTS_SUBJECT_MODEL', 'App\\Models\\User'),
    'subject_email_column' => 'email',

    /*
    | Purposes: key => definition. Text fields take a string (default locale) or
    | ['en' => '...', 'hi' => '...']. 'data' takes a list or ['en' => [...], 'hi' => [...]].
    |
    | The three purposes below are EXAMPLES. Replace them with your own; the
    | checker (php artisan data-rights:check) warns while they are unchanged.
    |
    | basis: 'consent' (s.6) or 'legitimate_use' (s.7, with 'legitimate_use' => 'a'..'i').
    | tracking: true when the purpose involves tracking, behavioural monitoring
    | or targeted advertising (s.9(3)); it is never allowed for children.
    | fourth_schedule: a key from Support\FourthSchedule (A1-A5, B1-B6) when you
    | have checked that the Schedule's condition is met for this purpose.
    */
    'purposes' => [
        'account' => [
            'title' => [
                'en' => 'Your account',
                'hi' => 'आपका खाता',
            ],
            'description' => [
                'en' => 'To create your account, let you sign in and keep it secure.',
                'hi' => 'आपका खाता बनाने, आपको साइन इन करने देने और उसे सुरक्षित रखने के लिए।',
            ],
            'goods_or_services' => [
                'en' => 'Sign-in, account settings and access to the features you use.',
                'hi' => 'साइन-इन, खाते की सेटिंग और आप जिन सुविधाओं का उपयोग करते हैं उन तक पहुँच।',
            ],
            'data' => [
                'en' => ['Name', 'Email address', 'Password (stored as a hash)'],
                'hi' => ['नाम', 'ईमेल पता', 'पासवर्ड (हैश के रूप में सहेजा गया)'],
            ],
            'basis' => 'consent',
            'legitimate_use' => null,
            'tracking' => false,
            'fourth_schedule' => null,
            'withdrawal_effect' => [
                'en' => 'We can no longer keep your account open.',
                'hi' => 'हम आपका खाता खुला नहीं रख पाएँगे।',
            ],
        ],

        'marketing' => [
            'title' => [
                'en' => 'Product news by email',
                'hi' => 'ईमेल से उत्पाद समाचार',
            ],
            'description' => [
                'en' => 'To send you emails about new features and offers.',
                'hi' => 'नई सुविधाओं और ऑफ़र के बारे में आपको ईमेल भेजने के लिए।',
            ],
            'goods_or_services' => [
                'en' => 'A newsletter at most twice a month.',
                'hi' => 'महीने में अधिकतम दो बार न्यूज़लेटर।',
            ],
            'data' => [
                'en' => ['Email address', 'Name'],
                'hi' => ['ईमेल पता', 'नाम'],
            ],
            'basis' => 'consent',
            'legitimate_use' => null,
            'tracking' => false,
            'fourth_schedule' => null,
            'withdrawal_effect' => null,
        ],

        'analytics' => [
            'title' => [
                'en' => 'Usage analytics',
                'hi' => 'उपयोग विश्लेषण (usage analytics)',
            ],
            'description' => [
                'en' => 'To understand which pages and features you use, so we can improve them.',
                'hi' => 'यह समझने के लिए कि आप कौन-से पेज और सुविधाएँ उपयोग करते हैं, ताकि हम उन्हें बेहतर बना सकें।',
            ],
            'goods_or_services' => [
                'en' => 'Reports about how the app is used.',
                'hi' => 'ऐप के उपयोग के बारे में रिपोर्ट।',
            ],
            'data' => [
                'en' => ['Pages you visit', 'Buttons you press', 'Device type'],
                'hi' => ['आप जो पेज देखते हैं', 'आप जो बटन दबाते हैं', 'डिवाइस का प्रकार'],
            ],
            'basis' => 'consent',
            'legitimate_use' => null,
            'tracking' => true,
            'fourth_schedule' => null,
            'withdrawal_effect' => null,
        ],
    ],

    'consent' => [
        'record_ip' => false,          // data minimisation: off by default
        'record_user_agent' => false,  // data minimisation: off by default
    ],

    'requests' => [
        'response_days' => 90,         // clamped to 90 (Rule 14(3) ceiling; the package uses it for every request type)
        'download_days' => 30,         // access summary download window after completion
        'identifier' => null,          // Rule 14(1)(b) text; null = "the email address you sign in with"
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
