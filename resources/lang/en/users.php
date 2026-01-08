<?php

return [
    'index' => [
        'title' => 'Users Management',
        'subtitle' => 'Invite teammates, assign roles, and audit effective permissions.',
        'empty' => 'No users found.',
        'create_button' => 'Add New User',
        'table' => [
            'id' => '#',
            'name' => 'Name',
            'email' => 'Email',
            'role' => 'Role',
            'roles' => 'Roles',
            'permissions' => 'Permissions',
            'actions' => 'Actions',
            'role_badge' => 'Role:',
            'extra_badge' => 'Extra:',
            'details' => 'View details',
            'manage_role' => 'Manage role permissions',
            'super_admin' => 'Super Admin',
            'no_roles' => '—',
            'edit' => 'Edit',
            'delete' => 'Delete',
        ],
        'confirm' => [
            'title' => 'Delete User',
            'message' => 'Are you sure you want to delete this user? This action cannot be undone.',
        ],
    ],

    'form' => [
        'create_title' => 'Add New User',
        'edit_title' => 'Edit User',
        'fields' => [
            'name' => 'Name',
            'email' => 'Email',
            'role' => 'Role',
            'password' => 'Password',
            'password_new' => 'New Password',
            'password_confirm' => 'Confirm Password',
            'password_new_confirm' => 'Confirm New Password',
            'permissions_preview' => 'Role Permissions Preview',
            'additional_permissions' => 'Additional Permissions (optional)',
            'per_role_hint' => 'User receives role permissions plus any selected here.',
            'role_placeholder' => 'Select Role',
            'password_hint' => 'Leave blank to keep the current password.',
        ],
        'buttons' => [
            'create' => 'Create User',
            'update' => 'Update User',
        ],
    ],
];
