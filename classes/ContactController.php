<?php
// classes/ContactController.php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/ContactMessage.php';

class ContactController
{
    private ?PDO $pdo;

    public bool   $success  = false;
    public string $error    = '';
    public array  $oldInput = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function handleRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        if (($_POST['action'] ?? '') !== 'send_message') {
            return;
        }

        $msg = new ContactMessage($_POST);

        if (!$msg->validate()) {
            $this->error    = $msg->getFirstError();
            $this->oldInput = $msg->toArray();
            return;
        }

        if (!$this->pdo) {
            $this->error    = 'Database connection unavailable. Please try again later.';
            $this->oldInput = $msg->toArray();
            return;
        }

        try {
            $this->ensureTableExists();
            $this->store($msg);

            $this->success  = true;
            $this->oldInput = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];
        } catch (Throwable $e) {
            error_log('ContactController insert failed: ' . $e->getMessage());
            $this->error    = 'Database error. Please try again later.';
            $this->oldInput = $msg->toArray();
        }
    }

    private function ensureTableExists(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS `enr_contact_messages` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(150) NOT NULL,
                `email` varchar(150) NOT NULL,
                `subject` varchar(255) NOT NULL,
                `message` text NOT NULL,
                `is_read` tinyint(1) NOT NULL DEFAULT 0,
                `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id`),
                KEY `idx_is_read` (`is_read`),
                KEY `idx_created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    private function store(ContactMessage $msg): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO `enr_contact_messages` (name, email, subject, message)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $msg->name,
            $msg->email,
            $msg->subject,
            $msg->message,
        ]);
    }

    public function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}