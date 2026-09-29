<?php
// classes/CourseSelectionController.php

require_once __DIR__ . '/CourseCatalog.php';

class CourseSelectionController
{
    public CourseCatalog $catalog;

    public string $branch      = 'main';
    public string $message     = '';
    public string $messageType = ''; // success | error
    public bool   $shouldRedirect = false;
    public string $redirectUrl    = 'enrollment-form.php';

    public function __construct()
    {
        $this->catalog = (new CourseCatalog())->load();
    }

    public function handleRequest(): void
    {
        // Branch from URL
        if (isset($_GET['branch']) && is_string($_GET['branch'])) {
            $this->branch = $_GET['branch'];
        }

        // Handle POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_id'])) {
            $this->handlePost((int) $_POST['course_id']);
        }
    }

    private function handlePost(int $courseId): void
    {
        if ($courseId <= 0) {
            $this->message     = 'Please select a course.';
            $this->messageType = 'error';
            return;
        }

        $course = $this->catalog->find($courseId);

        if (!$course) {
            $this->message     = 'Selected course not found. Please try again.';
            $this->messageType = 'error';
            return;
        }

        $_SESSION['selected_course_id'] = $courseId;

        $this->message     = 'Course selected successfully: ' . $course->label();
        $this->messageType = 'success';

        // Delayed JS redirect
        $url = json_encode($this->redirectUrl);
        echo "<script>setTimeout(function(){ window.location.href = {$url}; }, 1500);</script>";
    }

    public function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}