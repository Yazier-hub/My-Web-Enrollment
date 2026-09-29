<?php
// classes/ContactMessage.php

class ContactMessage
{
    public string $name    = '';
    public string $email   = '';
    public string $subject = '';
    public string $message = '';

    private array $errors = [];

    public function __construct(array $data = [])
    {
        $this->name    = trim($data['name']    ?? '');
        $this->email   = trim($data['email']   ?? '');
        $this->subject = trim($data['subject'] ?? '');
        $this->message = trim($data['message'] ?? '');
    }

    public function validate(): bool
    {
        $this->errors = [];

        if ($this->name === '') {
            $this->errors[] = 'Please enter your full name.';
        } elseif (mb_strlen($this->name) > 150) {
            $this->errors[] = 'Name is too long (max 150 characters).';
        }

        if ($this->email === '' || !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = 'Please enter a valid email address.';
        } elseif (mb_strlen($this->email) > 150) {
            $this->errors[] = 'Email is too long (max 150 characters).';
        }

        if ($this->subject === '') {
            $this->errors[] = 'Please enter a subject.';
        } elseif (mb_strlen($this->subject) > 255) {
            $this->errors[] = 'Subject is too long (max 255 characters).';
        }

        if ($this->message === '') {
            $this->errors[] = 'Please enter your message.';
        }

        return empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFirstError(): string
    {
        return $this->errors[0] ?? '';
    }

    public function toArray(): array
    {
        return [
            'name'    => $this->name,
            'email'   => $this->email,
            'subject' => $this->subject,
            'message' => $this->message,
        ];
    }
}