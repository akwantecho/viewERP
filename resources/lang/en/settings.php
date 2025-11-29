<?php

return [
    'title' => 'Settings',
    'subtitle' => 'Update company details, localization, alert preferences, and integrations.',

    'sections' => [
        'company' => 'Company profile',
        'branding' => 'Branding & identity',
        'localization' => 'Localization',
        'notifications' => 'Notifications',
        'security' => 'Security',
        'integrations' => 'Integrations',
    ],

    'fields' => [
        'company_name' => 'Company name',
        'company_email' => 'Company email',
        'company_phone' => 'Company phone',
        'company_address' => 'Company address',
        'logo' => 'Logo',
        'favicon' => 'Favicon',
        'primary_color' => 'Primary color',
        'locale' => 'Default language',
        'timezone' => 'Default timezone',
        'currency' => 'Default currency',
        'reminder_days' => 'Reminder window (days)',
        'daily_digest' => 'Daily digest',
        'weekly_summary' => 'Weekly summary',
        'two_factor' => 'Two-factor authentication',
        'session_timeout' => 'Session timeout (minutes)',
        'webhook_url' => 'Webhook URL',
    ],

    'actions' => [
        'update' => 'Save settings',
        'test_webhook' => 'Send test payload',
    ],

    'messages' => [
        'logo_hint' => 'Use PNG/SVG up to 1MB for best rendering.',
        'color_hint' => 'The branding color is used for charts and highlights.',
        'reminder_hint' => 'We send reminders before installments reach their due dates.',
        'two_factor_hint' => 'Require OTP verification for privileged accounts.',
    ],
];
