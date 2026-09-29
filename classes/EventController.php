<?php
// classes/EventController.php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Event.php';
require_once __DIR__ . '/EventType.php';
require_once __DIR__ . '/EventRepository.php';

class EventController
{
    public EventRepository $repository;

    public function __construct()
    {
        $this->repository = (new EventRepository())->load();
    }

    public function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}