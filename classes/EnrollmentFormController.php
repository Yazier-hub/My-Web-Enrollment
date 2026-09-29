<?php
// classes/EnrollmentFormController.php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Applicant.php';
require_once __DIR__ . '/Course.php';
require_once __DIR__ . '/EnrollmentForm.php';

class EnrollmentFormController
{
    private ?PDO $pdo;

    public int      $courseId    = 0;
    public int      $applicantId = 0;
    public ?Course  $course      = null;
    public ?Applicant $applicant = null;

    public EnrollmentForm $form;

    public string $message     = '';
    public string $messageType = ''; // success | warning | error
    public bool   $shouldRedirect = false;
    public string $redirectUrl    = '';

    public function __construct()
    {
        $this->pdo = Database::getConnection();
        $this->form = new EnrollmentForm();
    }

    /**
     * Full request lifecycle:
     *   1. Load course_id (session / GET applicant)
     *   2. Load existing applicant if editing
     *   3. Redirect if no course & no applicant
     *   4. Load course info
     *   5. Handle POST submission
     *   6. Refresh applicant data
     */
    public function handleRequest(): void
    {
        if (!$this->pdo) {
            $this->message     = 'Database connection unavailable.';
            $this->messageType = 'error';
            return;
        }

        // 1. course_id from session
        $this->courseId = isset($_SESSION['selected_course_id'])
            ? (int) $_SESSION['selected_course_id']
            : 0;

        // 2. applicant_id from GET
        $this->applicantId = isset($_GET['applicant_id'])
            ? (int) $_GET['applicant_id']
            : 0;

        if ($this->applicantId > 0) {
            $this->loadApplicant($this->applicantId);
        }

        // 3. redirect if nothing selected
        if ($this->courseId === 0 && !$this->applicant) {
            $this->redirectUrl    = 'selecting-course.php';
            $this->shouldRedirect = true;
            return;
        }

        // 4. load course info
        if ($this->courseId > 0) {
            $this->course = $this->fetchCourse($this->courseId);
        }

        // 5. handle POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST'
            && ($_POST['action'] ?? '') === 'submit') {
            $this->handlePost();
        }

        // 6. refresh applicant after POST
        if ($this->applicantId > 0) {
            $this->loadApplicant($this->applicantId);
        }
    }

    // =====================================================
    // INTERNAL HELPERS
    // =====================================================

    private function loadApplicant(int $id): void
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM enr_applicants WHERE enr_applicant_id = ? LIMIT 1"
            );
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $this->applicant = new Applicant($row);
                if (!empty($row['course_id'])) {
                    $this->courseId = (int) $row['course_id'];
                }
                // Pre-fill form with existing data
                $this->form->fillFrom($row);
            }
        } catch (PDOException $e) {
            error_log('loadApplicant failed: ' . $e->getMessage());
        }
    }

    private function fetchCourse(int $id): ?Course
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT id, code, name, years FROM rgr_courses WHERE id = ? LIMIT 1"
            );
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? new Course($row) : null;
        } catch (PDOException $e) {
            error_log('fetchCourse failed: ' . $e->getMessage());
            return null;
        }
    }

    private function handlePost(): void
    {
        $this->form = new EnrollmentForm($_POST, $this->courseId);

        if (!$this->form->validate()) {
            $this->message     = $this->form->getErrorHtml();
            $this->messageType = 'error';
            return;
        }

        try {
            if ($this->applicantId > 0) {
                $this->updateApplicant();
            } else {
                $this->insertApplicant();
            }
        } catch (PDOException $e) {
            error_log('EnrollmentFormController DB error: ' . $e->getMessage());
            $this->message     = 'Database Error: ' . $e->getMessage();
            $this->messageType = 'error';
        }
    }

    private function insertApplicant(): void
    {
        $sql = "INSERT INTO enr_applicants (
                    surname, first_name, middle_name, suffix,
                    admission_type, working_student,
                    sex, civil_status, religion,
                    date_of_birth, place_of_birth, age,
                    email, contact_number,
                    facebook, messenger,
                    address_complete, address_barangay, address_city, address_province,
                    parent_full_name, parent_contact, parent_address,
                    school_last_attended, year_graduated,
                    how_hear, course_id, status, submitted_at
                ) VALUES (
                    :surname, :first_name, :middle_name, :suffix,
                    :admission_type, :working_student,
                    :sex, :civil_status, :religion,
                    :date_of_birth, :place_of_birth, :age,
                    :email, :contact_number,
                    :facebook, :messenger,
                    :address_complete, :address_barangay, :address_city, :address_province,
                    :parent_full_name, :parent_contact, :parent_address,
                    :school_last_attended, :year_graduated,
                    :how_hear, :course_id, 'pending', NOW()
                )";

        $stmt = $this->pdo->prepare($sql);
        $ok   = $stmt->execute($this->buildBindings());

        if ($ok) {
            $this->applicantId = (int) $this->pdo->lastInsertId();
            $this->message     = 'Application submitted successfully! Applicant ID: ' . $this->applicantId;
            $this->messageType = 'success';

            unset($_SESSION['selected_course_id']);
            $this->scheduleRedirect('enroll.php?applicant_id=' . $this->applicantId);
        } else {
            $this->message     = 'Failed to submit application. Please try again.';
            $this->messageType = 'error';
        }
    }

    private function updateApplicant(): void
    {
        $sql = "UPDATE enr_applicants SET
                    surname = :surname,
                    first_name = :first_name,
                    middle_name = :middle_name,
                    suffix = :suffix,
                    admission_type = :admission_type,
                    working_student = :working_student,
                    sex = :sex,
                    civil_status = :civil_status,
                    religion = :religion,
                    date_of_birth = :date_of_birth,
                    place_of_birth = :place_of_birth,
                    age = :age,
                    email = :email,
                    contact_number = :contact_number,
                    facebook = :facebook,
                    messenger = :messenger,
                    address_complete = :address_complete,
                    address_barangay = :address_barangay,
                    address_city = :address_city,
                    address_province = :address_province,
                    parent_full_name = :parent_full_name,
                    parent_contact = :parent_contact,
                    parent_address = :parent_address,
                    school_last_attended = :school_last_attended,
                    year_graduated = :year_graduated,
                    how_hear = :how_hear,
                    course_id = :course_id,
                    updated_at = NOW()
                WHERE applicant_id = :applicant_id";

        $bindings = $this->buildBindings();
        $bindings[':applicant_id'] = $this->applicantId;

        $stmt = $this->pdo->prepare($sql);
        $ok   = $stmt->execute($bindings);

        if ($ok) {
            $this->message     = 'Application updated successfully! Applicant ID: ' . $this->applicantId;
            $this->messageType = 'success';

            unset($_SESSION['selected_course_id']);
            $this->scheduleRedirect('enroll.php?applicant_id=' . $this->applicantId);
        } else {
            $this->message     = 'No changes were made. The data might be the same.';
            $this->messageType = 'warning';
        }
    }

    private function buildBindings(): array
    {
        return [
            ':surname'              => $this->form->get('surname'),
            ':first_name'           => $this->form->get('first_name'),
            ':middle_name'          => $this->form->get('middle_name'),
            ':suffix'               => $this->form->get('suffix'),
            ':admission_type'       => $this->form->get('admission_type'),
            ':working_student'      => $this->form->get('working_student'),
            ':sex'                  => $this->form->get('sex'),
            ':civil_status'         => $this->form->get('civil_status'),
            ':religion'             => $this->form->get('religion'),
            ':date_of_birth'        => $this->form->get('date_of_birth'),
            ':place_of_birth'       => $this->form->get('place_of_birth'),
            ':age'                  => (int) $this->form->get('age', 0),
            ':email'                => $this->form->get('email'),
            ':contact_number'       => $this->form->get('contact_number'),
            ':facebook'             => $this->form->get('facebook'),
            ':messenger'            => $this->form->get('messenger'),
            ':address_complete'     => $this->form->get('address_complete'),
            ':address_barangay'     => $this->form->get('address_barangay'),
            ':address_city'         => $this->form->get('address_city'),
            ':address_province'     => $this->form->get('address_province'),
            ':parent_full_name'     => $this->form->get('parent_full_name'),
            ':parent_contact'       => $this->form->get('parent_contact'),
            ':parent_address'       => $this->form->get('parent_address'),
            ':school_last_attended' => $this->form->get('school_last_attended'),
            ':year_graduated'       => $this->form->get('year_graduated'),
            ':how_hear'             => $this->form->get('how_hear'),
            ':course_id'            => (int) $this->form->get('course_id'),
        ];
    }

    private function scheduleRedirect(string $url): void
    {
        $this->redirectUrl    = $url;
        $this->shouldRedirect = true;
    }

    public function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}