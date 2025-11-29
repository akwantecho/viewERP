<?php

return [
    'success' => [
        'created' => ':item has been created successfully.',
        'updated' => ':item has been updated successfully.',
        'deleted' => ':item has been deleted successfully.',
        'restored' => ':item has been restored successfully.',
        'synced' => 'Data synchronized successfully.',
    ],

    'error' => [
        'generic' => 'Something went wrong. Please try again.',
        'forbidden' => 'You are not authorized to perform this action.',
        'not_found' => 'The requested record could not be found.',
        'validation' => 'Some fields need your attention.',
    ],

    'warnings' => [
        'unsaved' => 'You have unsaved changes. Do you want to leave this page?',
        'delete_confirm' => 'This action cannot be undone. Do you want to continue?',
    ],

    'info' => [
        'empty_state' => 'Start by creating a new record using the button above.',
        'filters' => 'Use the filters to refine the results.',
    ],

    'modules' => [
        'users' => [
            'created' => 'User created successfully.',
            'updated' => 'User updated successfully.',
            'deleted' => 'User deleted successfully.',
        ],
        'roles' => [
            'created' => 'Role created successfully.',
            'updated' => 'Role updated successfully.',
            'deleted' => 'Role deleted successfully.',
        ],
        'projects' => [
            'created' => 'Project created successfully.',
            'updated' => 'Project updated successfully.',
            'deleted' => 'Project deleted successfully.',
        ],
        'customers' => [
            'created' => 'Customer created successfully.',
            'updated' => 'Customer updated successfully.',
            'deleted' => 'Customer deleted successfully.',
        ],
        'units' => [
            'created' => 'Unit created successfully.',
            'updated' => 'Unit updated successfully.',
            'deleted' => 'Unit deleted successfully.',
        ],
        'bookings' => [
            'created' => 'Booking registered successfully.',
            'updated' => 'Booking updated successfully.',
            'deleted' => 'Booking deleted successfully.',
        ],
        'payments' => [
            'created' => 'Payment recorded successfully.',
            'updated' => 'Payment updated successfully.',
            'deleted' => 'Payment deleted successfully.',
        ],
        'installments' => [
            'created' => 'Installment created successfully.',
            'updated' => 'Installment updated successfully.',
            'deleted' => 'Installment deleted successfully.',
        ],
        'documents' => [
            'uploaded' => 'Document uploaded successfully.',
            'deleted' => 'Document deleted successfully.',
        ],
        'reports' => [
            'generated' => 'Report generated successfully.',
        ],
        'notifications' => [
            'sent' => 'Notification sent to the selected recipients.',
        ],
        'settings' => [
            'updated' => 'Settings updated successfully.',
        ],
    ],
];
