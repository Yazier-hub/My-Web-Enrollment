<?php
// classes/Event.php

require_once __DIR__ . '/EventType.php';

class Event
{
    public int    $eventId        = 0;
    public int    $templateId     = 0;
    public string $eventTitle     = '';
    public string $eventType      = 'Other';
    public string $eventDate      = '';
    public string $startTime      = '';
    public string $endTime        = '';
    public string $description    = '';
    public string $location       = '';
    public string $targetAudience = '';
    public string $status         = 'upcoming';
    public string $templateName   = '';
    public int    $priority       = 0;

    public function __construct(array $row = [])
    {
        $this->eventId        = (int)    ($row['event_id']        ?? 0);
        $this->templateId     = (int)    ($row['template_id']     ?? 0);
        $this->eventTitle     = (string) ($row['event_title']     ?? '');
        $this->eventType      = (string) ($row['event_type']      ?? 'Other');
        $this->eventDate      = (string) ($row['event_date']      ?? '');
        $this->startTime      = (string) ($row['start_time']      ?? '');
        $this->endTime        = (string) ($row['end_time']        ?? '');
        $this->description    = (string) ($row['description']     ?? '');
        $this->location       = (string) ($row['location']        ?? '');
        $this->targetAudience = (string) ($row['target_audience'] ?? '');
        $this->status         = (string) ($row['status']          ?? 'upcoming');
        $this->templateName   = (string) ($row['template_name']   ?? '');
        $this->priority       = (int)    ($row['priority']        ?? 0);
    }

    // --------------------------------------------------------
    // DISPLAY HELPERS
    // --------------------------------------------------------

    public function monthKey(): string
    {
        return $this->eventDate ? date('F Y', strtotime($this->eventDate)) : 'Unknown';
    }

    public function formattedDate(): string
    {
        return $this->eventDate ? date('F d, Y', strtotime($this->eventDate)) : '';
    }

    public function timeRange(): string
    {
        if (!$this->startTime) return '';
        $start = date('g:i A', strtotime($this->startTime));
        $end   = $this->endTime ? date('g:i A', strtotime($this->endTime)) : '';
        return $end ? "$start – $end" : $start;
    }

    public function tagClass(): string
    {
        return EventType::tagClass($this->eventType);
    }

    public function icon(): string
    {
        return EventType::icon($this->eventType);
    }

    public function statusClass(): string
    {
        return EventType::statusClass($this->status);
    }

    public function statusLabel(): string
    {
        return ucfirst($this->status);
    }
}