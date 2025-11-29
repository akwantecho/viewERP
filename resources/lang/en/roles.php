<?php

return [
    'index' => [
        'title' => 'Roles Management',
        'subtitle' => 'Group permissions into reusable profiles for faster provisioning.',
        'create_button' => 'Add Role',
        'table' => [
            'name' => 'Role',
            'permissions' => 'Permissions',
            'actions' => 'Actions',
            'empty' => 'No permissions',
        ],
    ],

    'form' => [
        'create_title' => 'Add New Role',
        'edit_title' => 'Edit Role',
        'name' => 'Role Name',
        'assign_permissions' => 'Assign Permissions',
        'select_all' => 'Select All',
        'clear_all' => 'Clear All',
        'group_select_all' => 'All',
        'group_clear' => 'None',
        'columns' => [
            'action' => 'Action',
            'allow' => 'Allow',
        ],
        'buttons' => [
            'create' => 'Create Role',
            'update' => 'Update Role',
        ],
    ],
];
