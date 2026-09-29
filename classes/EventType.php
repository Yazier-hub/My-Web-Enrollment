<?php
// classes/EventType.php

class EventType
{
    private const TAG_MAP = [
        'Academic'             => 'academic',
        'Meeting'              => 'meeting',
        'Seminar'              => 'seminar',
        'Institutional Event'  => 'institutional',
        'Cultural Event'       => 'cultural',
        'Sports Event'         => 'sports',
        'Orientation'          => 'orientation',
        'Other'                => 'other',
    ];

    private const ICON_MAP = [
        'Academic'             => 'fa-chalkboard-teacher',
        'Meeting'              => 'fa-users',
        'Seminar'              => 'fa-microphone',
        'Institutional Event'  => 'fa-landmark',
        'Cultural Event'       => 'fa-mask',
        'Sports Event'         => 'fa-running',
        'Orientation'          => 'fa-door-open',
        'Other'                => 'fa-star',
    ];

    private const STATUS_MAP = [
        'upcoming'  => 'status-upcoming',
        'ongoing'   => 'status-ongoing',
        'completed' => 'status-completed',
        'cancelled' => 'status-cancelled',
    ];

    public static function tagClass(string $type): string
    {
        return self::TAG_MAP[$type] ?? 'other';
    }

    public static function icon(string $type): string
    {
        return self::ICON_MAP[$type] ?? 'fa-calendar';
    }

    public static function statusClass(string $status): string
    {
        return self::STATUS_MAP[$status] ?? 'status-upcoming';
    }
}