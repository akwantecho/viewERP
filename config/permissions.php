<?php

return [
    'users' => ['view', 'create', 'edit', 'delete'],
    'roles' => ['manage'],
    'customers' => ['view', 'manage'],
    'projects' => ['view', 'create', 'update', 'delete', 'structure'],
    'bookings' => ['view', 'create', 'delete'],
    'payments' => ['view', 'create', 'update', 'delete'],
    'documents' => ['view', 'upload', 'delete'],
    'reports.totalStatement' => ['view', 'backup'],
    'sync' => ['view', 'run'],
];
