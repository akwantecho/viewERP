<?php

return [
    'index' => [
        'hero' => [
            'badge' => 'Portfolio',
            'title' => 'Projects Overview',
            'description' => 'Review current project performance, sales percentages, and remaining inventory ready for marketing.',
            'stats' => [
                'projects' => 'Projects',
                'units' => 'Units',
                'sold' => 'Sold %',
            ],
        ],
        'list' => [
            'title' => 'Projects List',
            'subtitle' => 'Detailed view of every project and its current sales status.',
            'cta' => 'Add New Project',
            'sorted_hint' => 'Projects are sorted by sales performance and recency.',
        ],
        'table' => [
            'project' => 'Project',
            'code' => 'Code',
            'floors' => 'Floors',
            'units' => 'Units',
            'sales_progress' => 'Sales Progress',
            'sold' => 'Sold',
            'available' => 'Available',
            'actions' => 'Actions',
            'updated' => 'Updated :time',
            'sold_label' => 'Sold',
            'view' => 'View',
            'edit' => 'Edit',
        ],
        'legend' => [
            'high' => 'High traction',
            'mid' => 'On track',
            'early' => 'Early stage',
        ],
    ],

    'form' => [
        'create_title' => 'Create Project',
        'edit_title' => 'Edit Project',
        'sections' => [
            'info' => 'Project Information',
            'structure' => 'Define Floors and Units',
        ],
        'fields' => [
            'name' => 'Project name',
            'code' => 'Project code',
            'description' => 'Description',
            'address' => 'Address',
            'city' => 'City',
            'status' => 'Status',
            'handover_date' => 'Expected handover date',
            'notes' => 'Notes',
            'floor_name' => 'Floor Name',
            'naming_type' => 'Naming Type',
            'units_number' => 'Number of Units',
            'auto' => 'Auto',
            'manual' => 'Manual',
        ],
        'buttons' => [
            'save' => 'Save Project',
            'update' => 'Update Project',
            'add_floor' => 'Add Floor',
            'add_unit' => 'Add Unit',
        ],
        'labels' => [
            'floors_units' => 'Floors & Units',
            'delete_floor' => 'Delete floor',
            'units' => 'Units',
        ],
        'messages' => [
            'structure_hint' => 'Floors can auto-generate unit codes or accept manual entries.',
        ],
    ],

    'show' => [
        'badge' => 'Project',
        'back' => 'Back to Projects',
        'statement' => 'Statement',
        'code' => 'Code',
        'total_floors' => 'Total Floors',
        'units_table' => [
            'title' => 'Units',
            'per_page' => 'Per page',
            'headers' => [
                'unit_code' => 'Unit Code',
                'floor' => 'Floor',
                'status' => 'Status',
                'price' => 'Price (OMR)',
                'actions' => 'Actions',
            ],
            'view' => 'View',
            'empty' => 'No units found in this project.',
        ],
    ],
];
