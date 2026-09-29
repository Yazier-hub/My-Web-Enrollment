<?php
// classes/EventRepository.php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Event.php';

class EventRepository
{
    private ?PDO $pdo;

    /** @var Event[] */
    private array $events = [];

    /** @var array<string, Event[]> */
    private array $byMonth = [];

    private int $total     = 0;
    private int $upcoming  = 0;
    private int $ongoing   = 0;
    private int $completed = 0;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    /**
     * Load events from DB, or fallback to demo data if none.
     */
    public function load(): self
    {
        if ($this->pdo) {
            $this->loadFromDatabase();
        }

        if (empty($this->events)) {
            $this->loadFallbackDemo();
        }

        $this->groupByMonth();
        $this->computeStats();

        return $this;
    }

    // ==================================================
    // DB
    // ==================================================
    private function loadFromDatabase(): void
    {
        try {
            $sql = "SELECT
                        e.event_id,
                        e.template_id,
                        e.event_title,
                        e.event_type,
                        e.event_date,
                        e.start_time,
                        e.end_time,
                        e.description,
                        e.location,
                        e.target_audience,
                        e.status,
                        e.created_at,
                        t.template_name,
                        t.priority
                    FROM cc_events e
                    LEFT JOIN cc_event_templates t ON e.template_id = t.template_id
                    WHERE e.status IN ('upcoming', 'ongoing', 'completed')
                    ORDER BY e.event_date DESC, e.start_time ASC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $this->events[] = new Event($row);
            }
        } catch (Throwable $e) {
            error_log('EventRepository load failed: ' . $e->getMessage());
        }
    }

    // ==================================================
    // FALLBACK DEMO
    // ==================================================
    private function loadFallbackDemo(): void
    {
        $demo = [
            [
                'event_title'     => 'IT Career Talk: Tech Industry Trends',
                'event_type'      => 'Seminar',
                'event_date'      => '2026-09-25',
                'start_time'      => '13:00:00',
                'end_time'        => '17:00:00',
                'description'     => 'Industry experts share insights on IT career paths, emerging technologies, and job market trends.',
                'location'        => 'AVR Building Room 201',
                'target_audience' => 'BSIS Students',
                'status'          => 'upcoming',
            ],
            [
                'event_title'     => 'Annual Fire and Earthquake Drill',
                'event_type'      => 'Other',
                'event_date'      => '2026-09-18',
                'start_time'      => '09:00:00',
                'end_time'        => '11:00:00',
                'description'     => 'Mandatory drill to prepare students and staff for emergency situations.',
                'location'        => 'Main Building',
                'target_audience' => 'All Students, Faculty, Staff',
                'status'          => 'ongoing',
            ],
            [
                'event_title'     => 'BSIS Freshmen Orientation 2026',
                'event_type'      => 'Orientation',
                'event_date'      => '2026-08-15',
                'start_time'      => '08:00:00',
                'end_time'        => '12:00:00',
                'description'     => 'Welcome orientation for incoming BSIS freshmen. Includes campus tour, faculty introduction, and enrollment guidelines.',
                'location'        => 'University Auditorium',
                'target_audience' => 'BSIS Freshmen',
                'status'          => 'completed',
            ],
            [
                'event_title'     => 'Buwan ng Wika Cultural Festival',
                'event_type'      => 'Cultural Event',
                'event_date'      => '2026-08-30',
                'start_time'      => '08:00:00',
                'end_time'        => '20:00:00',
                'description'     => 'Celebration of Filipino language and culture with traditional performances, food fair, and exhibits.',
                'location'        => 'Cultural Center',
                'target_audience' => 'All Students',
                'status'          => 'completed',
            ],
        ];

        foreach ($demo as $row) {
            $this->events[] = new Event($row);
        }
    }

    // ==================================================
    // GROUPING
    // ==================================================
    private function groupByMonth(): void
    {
        $this->byMonth = [];
        foreach ($this->events as $event) {
            $key = $event->monthKey();
            $this->byMonth[$key][] = $event;
        }
    }

    // ==================================================
    // STATS
    // ==================================================
    private function computeStats(): void
    {
        $this->total     = count($this->events);
        $this->upcoming  = 0;
        $this->ongoing   = 0;
        $this->completed = 0;

        foreach ($this->events as $event) {
            switch ($event->status) {
                case 'upcoming':  $this->upcoming++;  break;
                case 'ongoing':   $this->ongoing++;   break;
                case 'completed': $this->completed++; break;
            }
        }
    }

    // ==================================================
    // ACCESSORS
    // ==================================================

    /** @return Event[] */
    public function all(): array
    {
        return $this->events;
    }

    /** @return array<string, Event[]> */
    public function byMonth(): array
    {
        return $this->byMonth;
    }

    public function total(): int
    {
        return $this->total;
    }

    public function upcomingCount(): int
    {
        return $this->upcoming;
    }

    public function ongoingCount(): int
    {
        return $this->ongoing;
    }

    public function completedCount(): int
    {
        return $this->completed;
    }

    public function isEmpty(): bool
    {
        return empty($this->byMonth);
    }
}