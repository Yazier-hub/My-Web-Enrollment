<?php
// classes/Course.php

class Course
{
    public int    $id     = 0;
    public string $code   = '';
    public string $name   = '';
    public ?int   $years  = null;

    public function __construct(array $data = [])
    {
        $this->id    = (int) ($data['id']    ?? 0);
        $this->code  = (string) ($data['code']  ?? '');
        $this->name  = (string) ($data['name']  ?? '');
        $this->years = isset($data['years']) ? (int) $data['years'] : null;
    }

    public function exists(): bool
    {
        return $this->id > 0;
    }

    public function label(): string
    {
        return trim($this->code . ($this->name ? ' - ' . $this->name : ''));
    }
}