<?php

// Notifications to the Data Principal. Each one ends with the contact block (Rule 9).

return [
    'greeting' => 'Hello,',
    'contact_heading' => 'Questions? Contact:',
    'salutation' => 'Regards, :business',

    'notice' => [
        'subject' => 'How :business uses your personal data',
        'intro' => 'We have published a notice that explains what personal data we use, why, and how you can withdraw consent or use your rights.',
        'action' => 'Read the notice',
        'withdraw' => 'You can withdraw your consent at any time, as easily as you gave it.',
    ],

    'request_received' => [
        'subject' => 'We received your request',
        'intro' => 'We received your request: :type.',
        'reference' => 'Reference: :id',
        'due' => 'We will reply by :date.',
        'action' => 'See your request',
    ],

    'request_closed' => [
        'subject_completed' => 'Your request is complete',
        'subject_rejected' => 'About your request',
        'intro_completed' => 'We completed your request: :type.',
        'intro_rejected' => 'We could not complete your request: :type.',
        'response' => 'Our reply:',
        'download' => 'Download your data summary',
        'download_until' => 'You can download it until :date.',
        'reference' => 'Reference: :id',
        'grievance' => 'If you are not satisfied, you can raise a grievance with us, and then complain to the Data Protection Board of India.',
    ],

    'erasure' => [
        'done' => 'We erased your personal data.',
        'retained' => 'We kept some records because a law needs them kept: :reasons',
        'logs' => 'Logs of processing are kept for at least one year, then deleted.',
    ],

    'breach' => [
        'subject' => 'Important: a personal data breach at :business',
        'intro' => 'We are writing to tell you about a personal data breach that affects your personal data.',
        'what' => 'What happened',
        'extent' => 'Extent: :value',
        'timing' => 'When: :value',
        'timing_unknown' => 'When: we are still finding out when it happened.',
        'aware' => 'We became aware of it on :date.',
        'consequences' => 'What it may mean for you',
        'mitigation' => 'What we are doing',
        'safety' => 'What you can do to protect yourself',
        'contact' => 'Who to contact',
        'not_known' => 'We are still finding this out and will tell you more.',
    ],

    'erasure_warning' => [
        'subject' => 'Your data will be erased on :date',
        'intro' => 'You have not used your account with :business for a long time. Your personal data will be erased on :date.',
        'keep' => 'To keep your account, sign in or contact us before then.',
        'action' => 'Sign in',
    ],
];
