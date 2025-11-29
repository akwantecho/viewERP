<?php

return [
    'notifications' => [
        'emails' => array_values(array_filter(array_map('trim', explode(',', env('BACKUP_ALERT_EMAILS', env('MAIL_FROM_ADDRESS', ''))))))
    ],
];
