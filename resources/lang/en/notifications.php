<?php

return [
    'installments' => [
        'title' => 'Installment Notifications',
        'subtitle' => 'Reminders scheduled three days before each due date.',
        'filters' => [
            'year' => 'Year',
            'month' => 'Month',
            'today_only' => 'Today only',
            'button' => 'Filter',
        ],
        'table' => [
            'id' => '#',
            'notify_date' => 'Notify (−3)',
            'due_date' => 'Due Date',
            'project' => 'Project',
            'unit' => 'Unit',
            'customer' => 'Customer',
            'total' => 'Total',
            'paid' => 'Paid',
            'remaining' => 'Remaining',
            'status' => 'Status',
            'actions' => 'Actions',
            'empty' => 'No notifications in the selected period.',
            'due_in' => 'in :days day(s)',
            'overdue_by' => 'overdue by :days day(s)',
        ],
        'actions' => [
            'pay' => 'Pay',
            'view' => 'View',
        ],
        'footer_note' => 'This page shows installments whose notification date equals (due date − 3 days) based on the selected filters, across all units and projects.',
    ],
];
