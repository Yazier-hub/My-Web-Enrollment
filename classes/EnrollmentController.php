<?php
// classes/EnrollmentController.php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Applicant.php';

class EnrollmentController
{
    private ?PDO $pdo;
    public Applicant $applicant;

    public function __construct()
    {
        $this->pdo       = Database::getConnection();
        $this->applicant = new Applicant();
    }

    /**
     * Read applicant_id from GET (or POST) and load the record.
     */
    public function handleRequest(): void
    {
        $applicantId = isset($_GET['applicant_id'])
            ? (int) $_GET['applicant_id']
            : 0;

        if ($applicantId <= 0 || !$this->pdo) {
            return;
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT a.*, r.code, r.name AS course_name
                FROM enr_applicants a
                LEFT JOIN rgr_courses r ON a.course_id = r.id
                WHERE a.applicant_id = ?
                LIMIT 1
            ");
            $stmt->execute([$applicantId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $this->applicant = new Applicant($row);
            }
        } catch (PDOException $e) {
            error_log('EnrollmentController load failed: ' . $e->getMessage());
        }
    }

    public function hasApplicant(): bool
    {
        return $this->applicant->exists();
    }

    public function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}