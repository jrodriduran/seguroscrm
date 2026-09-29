<?php

return [
    /**
     * Agency time zone, shown under Configuration > General > General.
     */
    [
        'key' => 'general.general.timezone',
        'name' => 'teamwork::app.configuration.timezone.title',
        'info' => 'teamwork::app.configuration.timezone.info',
        'sort' => 3,
        'fields' => [
            [
                'name' => 'timezone',
                'title' => 'teamwork::app.configuration.timezone.timezone',
                'type' => 'select',
                'default' => 'America/New_York',
                'options' => 'Webkul\Teamwork\Support\Timezones@options',
            ], [
                'name' => 'follow_browser',
                'title' => 'teamwork::app.configuration.timezone.follow-browser',
                'info' => 'teamwork::app.configuration.timezone.follow-browser-info',
                'type' => 'boolean',
                'default' => '0',
            ],
        ],
    ], [
        'key' => 'general.teamwork',
        'name' => 'teamwork::app.configuration.title',
        'info' => 'teamwork::app.configuration.info',
        'icon' => 'icon-activity',
        'sort' => 4,
    ], [
        'key' => 'general.teamwork.business_hours',
        'name' => 'teamwork::app.configuration.business-hours.title',
        'info' => 'teamwork::app.configuration.business-hours.info',
        'sort' => 1,
        'fields' => [
            [
                'name' => 'work_days',
                'title' => 'teamwork::app.configuration.business-hours.work-days',
                'info' => 'teamwork::app.configuration.business-hours.work-days-info',
                'type' => 'text',
                'default' => '1,2,3,4,5',
            ], [
                'name' => 'day_start',
                'title' => 'teamwork::app.configuration.business-hours.day-start',
                'type' => 'text',
                'default' => '09:00',
            ], [
                'name' => 'day_end',
                'title' => 'teamwork::app.configuration.business-hours.day-end',
                'type' => 'text',
                'default' => '18:00',
            ],
        ],
    ], [
        'key' => 'general.teamwork.rules',
        'name' => 'teamwork::app.configuration.rules.title',
        'info' => 'teamwork::app.configuration.rules.info',
        'sort' => 2,
        'fields' => [
            [
                'name' => 'lead_warning_hours',
                'title' => 'teamwork::app.configuration.rules.lead-warning',
                'type' => 'number',
                'default' => '8',
            ], [
                'name' => 'lead_overdue_hours',
                'title' => 'teamwork::app.configuration.rules.lead-overdue',
                'type' => 'number',
                'default' => '16',
            ], [
                'name' => 'follow_up_overdue_hours',
                'title' => 'teamwork::app.configuration.rules.follow-up-overdue',
                'info' => 'teamwork::app.configuration.rules.follow-up-overdue-info',
                'type' => 'number',
                'default' => '16',
            ], [
                'name' => 'urgent_overdue_hours',
                'title' => 'teamwork::app.configuration.rules.urgent-overdue',
                'type' => 'number',
                'default' => '4',
            ],
        ],
    ],
];
