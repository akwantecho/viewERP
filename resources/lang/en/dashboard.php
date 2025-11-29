<?php

return [
    'hero' => [
        'badge' => 'Performance Snapshot',
        'welcome' => 'Welcome Back',
        'title' => 'Dashboard Overview',
        'description' => 'Monitor sales and project metrics at a glance, then jump straight into daily actions such as creating projects or registering new customers.',
        'actions' => [
            'projects' => 'Manage Projects',
            'customers' => 'View Customers',
        ],
    ],

    'cards' => [
        'projects' => [
            'label' => 'Projects',
            'hint' => 'Active developments',
        ],
        'customers' => [
            'label' => 'Customers',
            'hint' => 'Registered buyers',
        ],
        'units_sold' => [
            'label' => 'Units Sold',
            'hint' => 'Confirmed sales',
        ],
        'available_units' => [
            'label' => 'Available Units',
            'hint' => 'Ready to sell',
        ],
    ],

    'quick_actions' => [
        'title' => 'Quick Actions',
        'subtitle' => 'Reach your most common workflows in a single click.',
        'items' => [
            'new_project' => [
                'title' => 'Create a new project',
                'description' => 'Launch a new development and define its floors and units.',
                'icon' => '➕',
            ],
            'register_customer' => [
                'title' => 'Register a customer',
                'description' => 'Capture buyer details before booking or direct-sale flows.',
                'icon' => '👤',
            ],
            'project_pipeline' => [
                'title' => 'Project pipeline',
                'description' => 'Review sales activity and progress for every project.',
                'icon' => '📊',
            ],
            'upcoming_installments' => [
                'title' => 'Upcoming installments',
                'description' => 'Check upcoming payments and plan follow-ups with customers.',
                'icon' => '🔔',
            ],
        ],
    ],

    'sales_health' => [
        'title' => 'Sales Health',
        'units_sold' => 'Units sold',
        'progress_hint' => ':value% of tracked units have been sold.',
        'tip_label' => 'Tip',
        'tip_body' => 'Keep project data updated so reports stay accurate for weekly meetings.',
    ],
];
