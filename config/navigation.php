<?php

return [
    [
        'label' => 'navigation.items.dashboard',
        'route' => 'dashboard',
        'icon'  => 'home',
        'perm'  => null,
    ],
    [
        'label' => 'navigation.items.customers',
        'route' => 'customers.index',
        'icon'  => 'users',
        'perm'  => 'bookings.view',
    ],
    [
        'label' => 'navigation.items.projects',
        'route' => 'projects.index',
        'icon'  => 'layers',
        'perm'  => 'projects.view',
    ],
    [
        'label' => 'navigation.items.installment_alerts',
        'route' => 'installments.notifications',
        'icon'  => 'bell',
        'perm'  => 'payments.view',
    ],
    [
        'label' => 'navigation.items.total_statement',
        'route' => 'reports.totalStatement',
        'icon'  => 'chart-bar',
        'perm'  => 'reports.view',
    ],
    [
        'heading' => 'navigation.sections.administration',
        'children' => [
            [
                'label' => 'navigation.items.users',
                'route' => 'users.index',
                'icon'  => 'users',
                'perm'  => 'users.view',
            ],
            [
                'label' => 'navigation.items.roles_permissions',
                'route' => 'admin.authorization.index',
                'icon'  => 'shield',
                'perm'  => 'roles.manage',
            ],
            [
                'label' => 'navigation.items.backup_center',
                'route' => 'admin.backup-center.index',
                'icon'  => 'shield-check',
                'super_only' => true,
            ],
            [
                'label' => 'navigation.items.backup_log',
                'route' => 'backup-log.index',
                'icon'  => 'database',
                'perm'  => 'reports.totalStatement.view',
            ],
            [
                'label' => 'navigation.items.storage_manager',
                'route' => 'storage.files.index',
                'icon'  => 'cloud',
                'perm'  => null,
                'super_only' => true,
            ],
        ],
    ],
];
