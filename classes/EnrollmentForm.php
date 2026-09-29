<?php
// classes/EnrollmentForm.php

class EnrollmentForm
{
    // All form fields
    public array $data = [
        'surname'             => '',
        'first_name'          => '',
        'middle_name'         => '',
        'suffix'              => '',
        'admission_type'      => '',
        'working_student'     => 'No',
        'sex'                 => '',
        'civil_status'        => '',
        'religion'            => '',
        'date_of_birth'       => '',
        'place_of_birth'      => '',
        'age'                 => 0,
        'email'               => '',
        'contact_number'      => '',
        'facebook'            => '',
        'messenger'           => '',
        'address_complete'    => '',
        'address_barangay'    => '',
        'address_city'        => '',
        'address_province'    => '',
        'parent_full_name'    => '',
        'parent_contact'      => '',
        'parent_address'      => '',
        'school_last_attended'=> '',
        'year_graduated'      => '',
        'how_hear'            => '',
        'course_id'           => 0,
    ];

    private array $errors = [];

    public function __construct(array $input = [], int $defaultCourseId = 0)
    {
        foreach ($this->data as $key => $default) {
            if ($key === 'course_id') {
                $this->data[$key] = isset($input[$key])
                    ? (int) $input[$key]
                    : $defaultCourseId;
                continue;
            }
            $this->data[$key] = isset($input[$key])
                ? trim((string) $input[$key])
                : $default;
        }

        // Normalize age
        $this->data['age'] = (int) ($this->data['age'] ?: 0);
    }

    public function fillFrom(array $row): void
    {
        foreach ($row as $key => $value) {
            if (array_key_exists($key, $this->data)) {
                $this->data[$key] = $value ?? $this->data[$key];
            }
        }
    }

    public function validate(): bool
    {
        $this->errors = [];

        $required = [
            'surname'           => 'Surname is required',
            'first_name'        => 'First Name is required',
            'sex'               => 'Sex is required',
            'civil_status'      => 'Civil Status is required',
            'date_of_birth'     => 'Date of Birth is required',
            'place_of_birth'    => 'Place of Birth is required',
            'email'             => 'Email Address is required',
            'contact_number'    => 'Contact Number is required',
            'address_barangay'  => 'Barangay is required',
            'address_city'      => 'City/Municipality is required',
            'address_province'  => 'Province is required',
            'parent_full_name'  => 'Parent/Guardian Full Name is required',
            'school_last_attended' => 'School Last Attended is required',
            'year_graduated'    => 'Year Graduated is required',
        ];

        foreach ($required as $field => $msg) {
            if (empty($this->data[$field])) {
                $this->errors[] = $msg;
            }
        }

        // Email format
        if (!empty($this->data['email']) &&
            !filter_var($this->data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = 'Please enter a valid email address.';
        }

        // Course
        if (empty($this->data['course_id'])) {
            $this->errors[] = 'No course selected. Please go back and select a course.';
        }

        return empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getErrorHtml(): string
    {
        return 'Please fix the following errors:<br>' . implode('<br>', $this->errors);
    }

    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, $value): void
    {
        $this->data[$key] = $value;
    }

    public function toArray(): array
    {
        return $this->data;
    }
}