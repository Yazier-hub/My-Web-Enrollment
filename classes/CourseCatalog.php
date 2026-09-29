<?php
// classes/CourseCatalog.php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Course.php';

class CourseCatalog
{
    private ?PDO $pdo;

    /** @var Course[] */
    private array $courses = [];

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function load(): self
    {
        $this->courses = [];

        if (!$this->pdo) {
            return $this;
        }

        try {
            $stmt = $this->pdo->query(
                "SELECT id, code, name, years FROM rgr_courses ORDER BY code"
            );
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $this->courses[] = new Course($row);
            }
        } catch (PDOException $e) {
            error_log('CourseCatalog load failed: ' . $e->getMessage());
        }

        return $this;
    }

    /** @return Course[] */
    public function all(): array
    {
        return $this->courses;
    }

    public function isEmpty(): bool
    {
        return empty($this->courses);
    }

    public function count(): int
    {
        return count($this->courses);
    }

    public function find(int $id): ?Course
    {
        foreach ($this->courses as $course) {
            if ($course->id === $id) {
                return $course;
            }
        }
        return null;
    }
}