<?php
// classes/Applicant.php

class Applicant
{
    public int    $applicantId = 0;
    public array  $data        = [];

    public function __construct(array $data = [])
    {
        $this->data = $data;
        if (isset($data['applicant_id'])) {
            $this->applicantId = (int) $data['applicant_id'];
        }
    }

    public function exists(): bool
    {
        return !empty($this->data);
    }

    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function getFullName(): string
    {
        $first   = $this->get('first_name', '');
        $surname = $this->get('surname', '');
        return trim("$first $surname");
    }

    public function getFormattedId(): string
    {
        return '#' . str_pad((string) $this->applicantId, 6, '0', STR_PAD_LEFT);
    }

    public function getStatus(): string
    {
        return ucfirst($this->get('status', 'Pending'));
    }

    public function getCourseLabel(): string
    {
        $code = $this->get('code', 'N/A');
        $name = $this->get('course_name', '');
        return trim($code . ($name ? ' - ' . $name : ''));
    }

    public function toArray(): array
    {
        return $this->data;
    }
}