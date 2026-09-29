<?php
// enrollment-form.php - COMPLETE FIXED VERSION WITH AGE AUTO-CALCULATION

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session to get course_id
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
$host = 'localhost';
$dbname = 'lms';  // Fixed: Correct database name
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get course_id from session
$course_id = isset($_SESSION['selected_course_id']) ? intval($_SESSION['selected_course_id']) : 0;
$applicant_id = 0;
$applicant_data = null;

// Check if applicant already exists (for editing)
$applicant_id = isset($_GET['applicant_id']) ? intval($_GET['applicant_id']) : 0;
if ($applicant_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM enr_applicants WHERE enr_applicant_id = ?");
        $stmt->execute([$applicant_id]);
        $applicant_data = $stmt->fetch(PDO::FETCH_ASSOC);
        // If applicant exists, use their course_id
        if ($applicant_data && isset($applicant_data['course_id'])) {
            $course_id = intval($applicant_data['course_id']);
        }
    } catch(PDOException $e) {
        $applicant_data = null;
        error_log('Error fetching applicant: ' . $e->getMessage());
    }
}

// If no course_id in session and no applicant data, redirect back
if ($course_id == 0 && !$applicant_data) {
    header("Location: selecting-course.php");
    exit();
}

// Get course info for display
$course_info = null;
if ($course_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT code, name, years FROM rgr_courses WHERE id = ?");
        $stmt->execute([$course_id]);
        $course_info = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log('Error fetching course info: ' . $e->getMessage());
    }
}

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit') {
    // Get all form data
    $surname = isset($_POST['surname']) ? trim($_POST['surname']) : '';
    $first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
    $middle_name = isset($_POST['middle_name']) ? trim($_POST['middle_name']) : '';
    $suffix = isset($_POST['suffix']) ? trim($_POST['suffix']) : '';
    $admission_type = isset($_POST['admission_type']) ? $_POST['admission_type'] : '';
    $working_student = isset($_POST['working_student']) ? $_POST['working_student'] : 'No';
    $sex = isset($_POST['sex']) ? $_POST['sex'] : '';
    $civil_status = isset($_POST['civil_status']) ? $_POST['civil_status'] : '';
    $religion = isset($_POST['religion']) ? trim($_POST['religion']) : '';
    $date_of_birth = isset($_POST['date_of_birth']) ? $_POST['date_of_birth'] : '';
    $place_of_birth = isset($_POST['place_of_birth']) ? trim($_POST['place_of_birth']) : '';
    $age = isset($_POST['age']) ? intval($_POST['age']) : 0;
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $contact_number = isset($_POST['contact_number']) ? trim($_POST['contact_number']) : '';
    $facebook = isset($_POST['facebook']) ? trim($_POST['facebook']) : '';
    $messenger = isset($_POST['messenger']) ? trim($_POST['messenger']) : '';
    $address_complete = isset($_POST['address_complete']) ? trim($_POST['address_complete']) : '';
    $address_barangay = isset($_POST['address_barangay']) ? trim($_POST['address_barangay']) : '';
    $address_city = isset($_POST['address_city']) ? trim($_POST['address_city']) : '';
    $address_province = isset($_POST['address_province']) ? trim($_POST['address_province']) : '';
    $parent_full_name = isset($_POST['parent_full_name']) ? trim($_POST['parent_full_name']) : '';
    $parent_contact = isset($_POST['parent_contact']) ? trim($_POST['parent_contact']) : '';
    $parent_address = isset($_POST['parent_address']) ? trim($_POST['parent_address']) : '';
    $school_last_attended = isset($_POST['school_last_attended']) ? trim($_POST['school_last_attended']) : '';
    $year_graduated = isset($_POST['year_graduated']) ? trim($_POST['year_graduated']) : '';
    $how_hear = isset($_POST['how_hear']) ? $_POST['how_hear'] : '';
    $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : $course_id;

    // Validate required fields
    $errors = array();
    if (empty($surname)) $errors[] = "Surname is required";
    if (empty($first_name)) $errors[] = "First Name is required";
    if (empty($sex)) $errors[] = "Sex is required";
    if (empty($address_barangay)) $errors[] = "Barangay is required";
    if (empty($address_city)) $errors[] = "City/Municipality is required";
    if (empty($address_province)) $errors[] = "Province is required";
    if (empty($school_last_attended)) $errors[] = "School Last Attended is required";
    if (empty($year_graduated)) $errors[] = "Year Graduated is required";
    if (empty($email)) $errors[] = "Email Address is required";
    if (empty($date_of_birth)) $errors[] = "Date of Birth is required";
    if (empty($place_of_birth)) $errors[] = "Place of Birth is required";
    if (empty($civil_status)) $errors[] = "Civil Status is required";
    if (empty($contact_number)) $errors[] = "Contact Number is required";
    if (empty($parent_full_name)) $errors[] = "Parent/Guardian Full Name is required";
    if (empty($course_id) && $course_id == 0) $errors[] = "No course selected. Please go back and select a course.";

    if (count($errors) > 0) {
        $message = "Please fix the following errors:<br>" . implode("<br>", $errors);
        $messageType = 'error';
    } else {
        try {
            // Check if updating existing or creating new
            if ($applicant_id > 0) {
                // UPDATE existing applicant
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
                
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([
                    ':surname' => $surname,
                    ':first_name' => $first_name,
                    ':middle_name' => $middle_name,
                    ':suffix' => $suffix,
                    ':admission_type' => $admission_type,
                    ':working_student' => $working_student,
                    ':sex' => $sex,
                    ':civil_status' => $civil_status,
                    ':religion' => $religion,
                    ':date_of_birth' => $date_of_birth,
                    ':place_of_birth' => $place_of_birth,
                    ':age' => $age,
                    ':email' => $email,
                    ':contact_number' => $contact_number,
                    ':facebook' => $facebook,
                    ':messenger' => $messenger,
                    ':address_complete' => $address_complete,
                    ':address_barangay' => $address_barangay,
                    ':address_city' => $address_city,
                    ':address_province' => $address_province,
                    ':parent_full_name' => $parent_full_name,
                    ':parent_contact' => $parent_contact,
                    ':parent_address' => $parent_address,
                    ':school_last_attended' => $school_last_attended,
                    ':year_graduated' => $year_graduated,
                    ':how_hear' => $how_hear,
                    ':course_id' => $course_id,
                    ':applicant_id' => $applicant_id
                ]);
                
                if ($result) {
                    $message = "Application updated successfully! Applicant ID: " . $applicant_id;
                    $messageType = 'success';
                    // Clear session
                    unset($_SESSION['selected_course_id']);
                    echo "<script>
                        setTimeout(function(){ 
                            window.location.href = 'enroll.php?applicant_id=" . $applicant_id . "'; 
                        }, 2000);
                    </script>";
                } else {
                    $message = "No changes were made. The data might be the same.";
                    $messageType = 'warning';
                }
            } else {
                // INSERT new applicant with all required fields
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
                
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([
                    ':surname' => $surname,
                    ':first_name' => $first_name,
                    ':middle_name' => $middle_name,
                    ':suffix' => $suffix,
                    ':admission_type' => $admission_type,
                    ':working_student' => $working_student,
                    ':sex' => $sex,
                    ':civil_status' => $civil_status,
                    ':religion' => $religion,
                    ':date_of_birth' => $date_of_birth,
                    ':place_of_birth' => $place_of_birth,
                    ':age' => $age,
                    ':email' => $email,
                    ':contact_number' => $contact_number,
                    ':facebook' => $facebook,
                    ':messenger' => $messenger,
                    ':address_complete' => $address_complete,
                    ':address_barangay' => $address_barangay,
                    ':address_city' => $address_city,
                    ':address_province' => $address_province,
                    ':parent_full_name' => $parent_full_name,
                    ':parent_contact' => $parent_contact,
                    ':parent_address' => $parent_address,
                    ':school_last_attended' => $school_last_attended,
                    ':year_graduated' => $year_graduated,
                    ':how_hear' => $how_hear,
                    ':course_id' => $course_id
                ]);
                
                if ($result) {
                    $applicant_id = $pdo->lastInsertId();
                    $message = "Application submitted successfully! Applicant ID: " . $applicant_id;
                    $messageType = 'success';
                    // Clear session
                    unset($_SESSION['selected_course_id']);
                    echo "<script>
                        setTimeout(function(){ 
                            window.location.href = 'enroll.php?applicant_id=" . $applicant_id . "'; 
                        }, 2000);
                    </script>";
                } else {
                    $message = "Failed to submit application. Please try again.";
                    $messageType = 'error';
                }
            }
        } catch(PDOException $e) {
            $message = "Database Error: " . $e->getMessage();
            $messageType = 'error';
            error_log('Database error in enrollment-form: ' . $e->getMessage());
        }
    }
}

// Refresh applicant data after submission
if ($applicant_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM enr_applicants WHERE applicant_id = ?");
        $stmt->execute([$applicant_id]);
        $applicant_data = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $applicant_data = null;
        error_log('Error refreshing applicant data: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Enrollment Form · Bestlink College of the Philippines</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <style>
        /* ===== BASE STYLES ===== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        .bg-bcp-deep { background: #0a1e3d; }
        .text-bcp-sky { color: #4fc3f7; }
        .bg-bcp-sky { background: #4fc3f7; }
        
        .glass-dark {
            background: rgba(10, 30, 61, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        
        .btn-sky {
            background: linear-gradient(135deg, #4fc3f7, #29b6f6);
            color: #0a1e3d;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 4px 20px rgba(79, 195, 247, 0.3);
        }
        .btn-sky:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 20px 40px -8px rgba(79, 195, 247, 0.5);
        }
        
        .btn-outline-sky {
            border: 2px solid #4fc3f7;
            color: #0a1e3d;
            background: transparent;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .btn-outline-sky:hover {
            background: #4fc3f7;
            color: #0a1e3d;
            transform: translateY(-3px);
            box-shadow: 0 20px 40px -12px rgba(79, 195, 247, 0.3);
        }
        
        .nav-link {
            position: relative;
            transition: color 0.3s;
        }
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 0;
            height: 2px;
            background: #4fc3f7;
            transition: width 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .nav-link:hover::after { width: 100%; }
        .nav-link:hover { color: #4fc3f7; }
        
        .form-hero {
            background: linear-gradient(135deg, #0a1e3d 0%, #1a3a6b 50%, #0a1e3d 100%);
            padding: 140px 0 60px;
            position: relative;
            overflow: hidden;
        }
        .form-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(79, 195, 247, 0.08), transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        
        .form-container {
            background: white;
            border-radius: 24px;
            padding: 2.5rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.08);
            border: 1px solid rgba(79, 195, 247, 0.1);
            max-width: 900px;
            margin: 0 auto;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e2ebf6;
            border-radius: 12px;
            transition: all 0.3s;
            font-size: 0.95rem;
            color: #0a1e3d;
            background: white;
        }
        .form-input:focus {
            outline: none;
            border-color: #4fc3f7;
            box-shadow: 0 0 0 4px rgba(79, 195, 247, 0.1);
        }
        .form-input::placeholder {
            color: #a0b4c8;
        }
        .form-input:disabled {
            background: #f5f7fa;
            cursor: not-allowed;
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            color: #0a1e3d;
            margin-bottom: 0.4rem;
            font-size: 0.9rem;
        }
        
        select.form-input {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%230a1e3d' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        .step-indicator .step {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            color: #a0b4c8;
            font-weight: 500;
            cursor: pointer;
            padding: 0.25rem 0.5rem;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .step-indicator .step:hover {
            background: rgba(79, 195, 247, 0.05);
        }
        .step-indicator .step .num {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #e2ebf6;
            font-weight: 700;
            font-size: 0.8rem;
            color: #a0b4c8;
            transition: all 0.3s;
        }
        .step-indicator .step.active .num {
            border-color: #4fc3f7;
            background: #4fc3f7;
            color: white;
        }
        .step-indicator .step.active {
            color: #0a1e3d;
        }
        .step-indicator .step.completed .num {
            border-color: #4caf50;
            background: #4caf50;
            color: white;
        }
        .step-indicator .step.completed {
            color: #4caf50;
        }
        
        .form-section {
            display: none;
            animation: fadeInUp 0.5s ease forwards;
        }
        .form-section.active {
            display: block;
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .radio-group {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
        }
        .radio-group label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            color: #1a3a6b;
            font-size: 0.95rem;
        }
        .radio-group label input[type="radio"] {
            width: 18px;
            height: 18px;
            accent-color: #4fc3f7;
        }
        
        .success-message {
            background: #e8f5e9;
            border: 2px solid #4caf50;
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            color: #2e7d32;
        }
        .success-message .icon {
            font-size: 4rem;
            color: #4caf50;
            margin-bottom: 1rem;
        }
        .success-message h3 {
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .error-message {
            background: #ffebee;
            border: 2px solid #ef5350;
            border-radius: 16px;
            padding: 1rem 1.5rem;
            color: #c62828;
            margin-bottom: 1.5rem;
        }
        .warning-message {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 16px;
            padding: 1rem 1.5rem;
            color: #856404;
            margin-bottom: 1.5rem;
        }
        
        .float-shape {
            position: absolute;
            border-radius: 50%;
            opacity: 0.06;
            pointer-events: none;
        }
        
        .hero-text {
            animation: fadeInUp 0.8s ease forwards;
            opacity: 0;
        }
        .hero-text.delay-1 { animation-delay: 0.2s; }
        .hero-text.delay-2 { animation-delay: 0.4s; }
        
        .requirements-section {
            background: #f8faff;
            border-radius: 16px;
            padding: 1.5rem;
            border: 1px solid #e2ebf6;
            margin-top: 1.5rem;
        }
        .requirements-section .req-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #0a1e3d;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .requirements-section .req-title i {
            color: #4fc3f7;
        }
        .requirements-section .req-subtitle {
            font-weight: 600;
            color: #1a3a6b;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        .requirements-section ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.1rem 1.5rem;
        }
        .requirements-section ul li {
            padding: 0.25rem 0;
            color: #1a3a6b;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .requirements-section ul li i {
            color: #4fc3f7;
            font-size: 0.7rem;
        }
        .requirements-section .transferee-section {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px dashed #e2ebf6;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            color: #4fc3f7;
            display: inline-block;
        }
        
        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        
        .footer-link {
            transition: color 0.3s;
        }
        .footer-link:hover {
            color: #4fc3f7;
        }
        
        .course-badge {
            display: inline-block;
            background: rgba(79, 195, 247, 0.15);
            color: #4fc3f7;
            padding: 0.25rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        @media (max-width: 640px) {
            .requirements-section ul {
                grid-template-columns: 1fr;
            }
            .form-container {
                padding: 1.5rem;
            }
            .step-indicator {
                gap: 0.5rem;
            }
            .step-indicator .step span:not(.num) {
                display: none;
            }
        }
    </style>
</head>
<body>

<div id="app">
    <!-- ===== TOP NAV ===== -->
    <header class="fixed top-0 left-0 right-0 z-50 glass-dark text-white py-3 border-b border-white/5">
        <div class="max-w-7xl mx-auto px-4 flex flex-wrap items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="index.html">
                    <img src="assets/bcp-logo.png" alt="BCP Logo" class="h-11 w-auto object-contain" onerror="this.style.display='none'" />
                </a>
                <a href="index.html" class="text-sm font-light hidden sm:inline hover:text-bcp-sky transition-colors">
                    <span class="font-semibold">Bestlink</span> College of the Philippines
                </a>
            </div>
            <nav class="flex flex-wrap items-center gap-3 md:gap-6 text-sm font-medium">
                <a href="index.html" class="nav-link px-1 py-1 text-gray-300">Home</a>
                <a href="about.html" class="nav-link px-1 py-1 text-gray-300">About</a>
                <a href="programs.html" class="nav-link px-1 py-1 text-gray-300">Programs</a>
                <a href="admissions.html" class="nav-link px-1 py-1 text-gray-300">Admissions</a>
                <a href="student-services.html" class="nav-link px-1 py-1 text-gray-300">Student Services</a>
                <a href="#" class="nav-link px-1 py-1 text-gray-300">News</a>
                <a href="events.php" class="nav-link px-1 py-1 text-gray-300">Events</a>
                <a href="contact.php" class="nav-link px-1 py-1 text-gray-300">Contact</a>
                <a href="online-admission.html" class="btn-sky px-6 py-2 rounded-full font-bold flex items-center gap-2 text-sm">
                    <i class="fas fa-user-graduate"></i> Enroll Now!
                </a>
            </nav>
        </div>
    </header>

    <!-- ===== HERO ===== -->
    <section class="form-hero">
        <div class="float-shape w-96 h-96 bg-[#4fc3f7] top-20 -left-20"></div>
        <div class="float-shape w-64 h-64 bg-[#29b6f6] bottom-20 -right-20"></div>
        
        <div class="max-w-7xl mx-auto px-4 relative z-10">
            <div class="text-center text-white">
                <span class="inline-block bg-white/10 backdrop-blur-sm text-bcp-sky text-xs font-semibold px-5 py-2 rounded-full mb-6 border border-white/10 hero-text">
                    <i class="fas fa-file-alt mr-2"></i> Application Form
                </span>
                <h1 class="text-4xl md:text-5xl font-extrabold leading-tight hero-text delay-1">
                    Enrollment Management <span class="text-bcp-sky">System</span>
                </h1>
                <p class="text-lg text-[#b0d4e8] mt-4 max-w-2xl mx-auto leading-relaxed hero-text delay-2">
                    <?php if ($course_info): ?>
                        Applying for <strong class="text-white"><?php echo htmlspecialchars($course_info['code']); ?></strong> - <?php echo htmlspecialchars($course_info['name']); ?>
                    <?php else: ?>
                        Complete your application form.
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </section>

    <!-- ===== FORM ===== -->
    <section class="py-16 bg-white relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-[#4fc3f7]/5 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 left-0 w-64 h-64 bg-[#29b6f6]/5 rounded-full blur-3xl"></div>
        
        <div class="max-w-4xl mx-auto px-4 relative z-10">
            <div class="form-container">
                <?php if ($message): ?>
                    <div class="<?php 
                        echo $messageType === 'success' ? 'success-message' : 
                            ($messageType === 'warning' ? 'warning-message' : 'error-message'); 
                    ?>">
                        <?php if ($messageType === 'success'): ?>
                            <div class="icon"><i class="fas fa-check-circle"></i></div>
                            <h3>Application Submitted Successfully!</h3>
                            <p><?php echo htmlspecialchars($message); ?></p>
                            <p class="text-sm mt-2">You will be redirected to the enrollment page.</p>
                        <?php else: ?>
                            <i class="fas <?php echo $messageType === 'warning' ? 'fa-exclamation-triangle' : 'fa-exclamation-circle'; ?> mr-2"></i>
                            <?php echo $message; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (!$message || $messageType !== 'success'): ?>
                <div>
                    <!-- Step Indicator -->
                    <div class="step-indicator">
                        <div class="step" :class="{ active: currentStep === 0, completed: currentStep > 0 }" @click="currentStep = 0">
                            <span class="num">1</span>
                            <span>Basic Info</span>
                        </div>
                        <div class="step" :class="{ active: currentStep === 1, completed: currentStep > 1 }" @click="currentStep = 1">
                            <span class="num">2</span>
                            <span>Parent/Guardian</span>
                        </div>
                        <div class="step" :class="{ active: currentStep === 2, completed: currentStep > 2 }" @click="currentStep = 2">
                            <span class="num">3</span>
                            <span>Education</span>
                        </div>
                        <div class="step" :class="{ active: currentStep === 3 }" @click="currentStep = 3">
                            <span class="num">4</span>
                            <span>Summary</span>
                        </div>
                    </div>

                    <form method="POST" action="" id="enrollmentForm">
                        <input type="hidden" name="action" value="submit" />
                        <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course_id); ?>" />

                        <?php if ($course_info): ?>
                            <div class="bg-[#f8faff] rounded-xl p-4 mb-6 border border-[#e2ebf6] text-center">
                                <span class="text-sm text-[#1a3a6b]">Selected Course:</span>
                                <span class="course-badge"><?php echo htmlspecialchars($course_info['code']); ?> - <?php echo htmlspecialchars($course_info['name']); ?></span>
                            </div>
                        <?php endif; ?>

                        <!-- ===== SECTION 1: BASIC INFORMATION ===== -->
                        <div class="form-section" :class="{ active: currentStep === 0 }">
                            <h2 class="text-2xl font-bold text-[#0a1e3d] mb-6">Basic Information</h2>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="form-label">Admission Type <span class="text-red-500">*</span></label>
                                    <select name="admission_type" class="form-input" required>
                                        <option value="">Select Admission Type</option>
                                        <option value="freshmen" <?php echo (isset($applicant_data['admission_type']) && $applicant_data['admission_type'] == 'freshmen') ? 'selected' : ''; ?>>Freshmen</option>
                                        <option value="transferee" <?php echo (isset($applicant_data['admission_type']) && $applicant_data['admission_type'] == 'transferee') ? 'selected' : ''; ?>>Transferee</option>
                                        <option value="returnee" <?php echo (isset($applicant_data['admission_type']) && $applicant_data['admission_type'] == 'returnee') ? 'selected' : ''; ?>>Returnee</option>
                                        <option value="senior_high" <?php echo (isset($applicant_data['admission_type']) && $applicant_data['admission_type'] == 'senior_high') ? 'selected' : ''; ?>>Senior High School</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="form-label">Are you a Working Student?</label>
                                    <div class="radio-group mt-1">
                                        <label>
                                            <input type="radio" name="working_student" value="Yes" <?php echo (isset($applicant_data['working_student']) && $applicant_data['working_student'] == 'Yes') ? 'checked' : ''; ?> /> Yes
                                        </label>
                                        <label>
                                            <input type="radio" name="working_student" value="No" <?php echo (isset($applicant_data['working_student']) && $applicant_data['working_student'] == 'No') ? 'checked' : ''; ?> /> No
                                        </label>
                                    </div>
                                </div>

                                <div>
                                    <label class="form-label">Last Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="surname" class="form-input" placeholder="Enter last name" value="<?php echo htmlspecialchars($applicant_data['surname'] ?? ''); ?>" required />
                                </div>

                                <div>
                                    <label class="form-label">First Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="first_name" class="form-input" placeholder="Enter first name" value="<?php echo htmlspecialchars($applicant_data['first_name'] ?? ''); ?>" required />
                                </div>

                                <div>
                                    <label class="form-label">Middle Name</label>
                                    <input type="text" name="middle_name" class="form-input" placeholder="Enter middle name" value="<?php echo htmlspecialchars($applicant_data['middle_name'] ?? ''); ?>" />
                                </div>

                                <div>
                                    <label class="form-label">Suffix</label>
                                    <input type="text" name="suffix" class="form-input" placeholder="Jr., Sr., III" value="<?php echo htmlspecialchars($applicant_data['suffix'] ?? ''); ?>" />
                                </div>

                                <div>
                                    <label class="form-label">Sex <span class="text-red-500">*</span></label>
                                    <select name="sex" class="form-input" required>
                                        <option value="">Select Sex</option>
                                        <option value="Male" <?php echo (isset($applicant_data['sex']) && $applicant_data['sex'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo (isset($applicant_data['sex']) && $applicant_data['sex'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                        <option value="Other" <?php echo (isset($applicant_data['sex']) && $applicant_data['sex'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="form-label">Civil Status <span class="text-red-500">*</span></label>
                                    <select name="civil_status" class="form-input" required>
                                        <option value="">Select Civil Status</option>
                                        <option value="Single" <?php echo (isset($applicant_data['civil_status']) && $applicant_data['civil_status'] == 'Single') ? 'selected' : ''; ?>>Single</option>
                                        <option value="Married" <?php echo (isset($applicant_data['civil_status']) && $applicant_data['civil_status'] == 'Married') ? 'selected' : ''; ?>>Married</option>
                                        <option value="Divorced" <?php echo (isset($applicant_data['civil_status']) && $applicant_data['civil_status'] == 'Divorced') ? 'selected' : ''; ?>>Divorced</option>
                                        <option value="Widowed" <?php echo (isset($applicant_data['civil_status']) && $applicant_data['civil_status'] == 'Widowed') ? 'selected' : ''; ?>>Widowed</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="form-label">Religion</label>
                                    <input type="text" name="religion" class="form-input" placeholder="Enter religion" value="<?php echo htmlspecialchars($applicant_data['religion'] ?? ''); ?>" />
                                </div>

                                <div>
                                    <label class="form-label">Date of Birth <span class="text-red-500">*</span></label>
                                    <input type="date" name="date_of_birth" id="date_of_birth" class="form-input" value="<?php echo htmlspecialchars($applicant_data['date_of_birth'] ?? ''); ?>" required @change="calculateAge" />
                                </div>

                                <div>
                                    <label class="form-label">Age <span class="text-red-500">*</span></label>
                                    <input type="number" name="age" id="age" class="form-input" placeholder="Age" value="<?php echo htmlspecialchars($applicant_data['age'] ?? ''); ?>" readonly required />
                                </div>

                                <div>
                                    <label class="form-label">Place of Birth <span class="text-red-500">*</span></label>
                                    <input type="text" name="place_of_birth" class="form-input" placeholder="City, Province" value="<?php echo htmlspecialchars($applicant_data['place_of_birth'] ?? ''); ?>" required />
                                </div>

                                <div>
                                    <label class="form-label">Email Address <span class="text-red-500">*</span></label>
                                    <input type="email" name="email" class="form-input" placeholder="you@example.com" value="<?php echo htmlspecialchars($applicant_data['email'] ?? ''); ?>" required />
                                </div>

                                <div>
                                    <label class="form-label">Contact Number <span class="text-red-500">*</span></label>
                                    <input type="tel" name="contact_number" class="form-input" placeholder="0912 345 6789" value="<?php echo htmlspecialchars($applicant_data['contact_number'] ?? ''); ?>" required />
                                </div>

                                <div>
                                    <label class="form-label">Facebook Name</label>
                                    <input type="text" name="facebook" class="form-input" placeholder="Facebook profile name" value="<?php echo htmlspecialchars($applicant_data['facebook'] ?? ''); ?>" />
                                </div>

                                <div>
                                    <label class="form-label">Messenger Name</label>
                                    <input type="text" name="messenger" class="form-input" placeholder="Messenger name" value="<?php echo htmlspecialchars($applicant_data['messenger'] ?? ''); ?>" />
                                </div>
                            </div>

                            <div class="mt-4">
                                <label class="form-label">Complete Address</label>
                                <input type="text" name="address_complete" class="form-input" placeholder="House #, Street, Barangay" value="<?php echo htmlspecialchars($applicant_data['address_complete'] ?? ''); ?>" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                                <div>
                                    <label class="form-label">Barangay <span class="text-red-500">*</span></label>
                                    <input type="text" name="address_barangay" class="form-input" placeholder="Barangay" value="<?php echo htmlspecialchars($applicant_data['address_barangay'] ?? ''); ?>" required />
                                </div>
                                <div>
                                    <label class="form-label">City/Municipality <span class="text-red-500">*</span></label>
                                    <input type="text" name="address_city" class="form-input" placeholder="City" value="<?php echo htmlspecialchars($applicant_data['address_city'] ?? ''); ?>" required />
                                </div>
                                <div>
                                    <label class="form-label">Province <span class="text-red-500">*</span></label>
                                    <input type="text" name="address_province" class="form-input" placeholder="Province" value="<?php echo htmlspecialchars($applicant_data['address_province'] ?? ''); ?>" required />
                                </div>
                            </div>

                            <div class="flex justify-end mt-6">
                                <button type="button" class="btn-sky px-8 py-3 rounded-full font-bold" @click="validateAndProceed(1)">
                                    Next <i class="fas fa-arrow-right ml-2"></i>
                                </button>
                            </div>
                        </div>

                        <!-- ===== SECTION 2: PARENT/GUARDIAN ===== -->
                        <div class="form-section" :class="{ active: currentStep === 1 }">
                            <h2 class="text-2xl font-bold text-[#0a1e3d] mb-6">Parent's/Guardian's Information</h2>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="form-label">Parent/Guardian Full Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="parent_full_name" class="form-input" placeholder="Full name" value="<?php echo htmlspecialchars($applicant_data['parent_full_name'] ?? ''); ?>" required />
                                </div>
                                <div>
                                    <label class="form-label">Parent/Guardian Contact</label>
                                    <input type="tel" name="parent_contact" class="form-input" placeholder="0912 345 6789" value="<?php echo htmlspecialchars($applicant_data['parent_contact'] ?? ''); ?>" />
                                </div>
                            </div>

                            <div class="mt-4">
                                <label class="form-label">Parent/Guardian Address</label>
                                <input type="text" name="parent_address" class="form-input" placeholder="Complete address" value="<?php echo htmlspecialchars($applicant_data['parent_address'] ?? ''); ?>" />
                            </div>

                            <div class="flex justify-between mt-6">
                                <button type="button" class="btn-outline-sky px-8 py-3 rounded-full font-bold" @click="currentStep = 0">
                                    <i class="fas fa-arrow-left mr-2"></i> Back
                                </button>
                                <button type="button" class="btn-sky px-8 py-3 rounded-full font-bold" @click="currentStep = 2">
                                    Next <i class="fas fa-arrow-right ml-2"></i>
                                </button>
                            </div>
                        </div>

                        <!-- ===== SECTION 3: EDUCATIONAL BACKGROUND ===== -->
                        <div class="form-section" :class="{ active: currentStep === 2 }">
                            <h2 class="text-2xl font-bold text-[#0a1e3d] mb-6">Educational Background</h2>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="form-label">Last School Attended <span class="text-red-500">*</span></label>
                                    <input type="text" name="school_last_attended" class="form-input" placeholder="School name" value="<?php echo htmlspecialchars($applicant_data['school_last_attended'] ?? ''); ?>" required />
                                </div>
                                <div>
                                    <label class="form-label">Year Graduated <span class="text-red-500">*</span></label>
                                    <input type="text" name="year_graduated" class="form-input" placeholder="e.g. 2024-2025" value="<?php echo htmlspecialchars($applicant_data['year_graduated'] ?? ''); ?>" required />
                                </div>
                            </div>

                            <div class="mt-4">
                                <label class="form-label">How did you hear about our school?</label>
                                <select name="how_hear" class="form-input">
                                    <option value="">Select option</option>
                                    <option value="social_media" <?php echo (isset($applicant_data['how_hear']) && $applicant_data['how_hear'] == 'social_media') ? 'selected' : ''; ?>>Social Media</option>
                                    <option value="friend" <?php echo (isset($applicant_data['how_hear']) && $applicant_data['how_hear'] == 'friend') ? 'selected' : ''; ?>>Friend/Relative</option>
                                    <option value="school_website" <?php echo (isset($applicant_data['how_hear']) && $applicant_data['how_hear'] == 'school_website') ? 'selected' : ''; ?>>School Website</option>
                                    <option value="advertisement" <?php echo (isset($applicant_data['how_hear']) && $applicant_data['how_hear'] == 'advertisement') ? 'selected' : ''; ?>>Advertisement</option>
                                    <option value="others" <?php echo (isset($applicant_data['how_hear']) && $applicant_data['how_hear'] == 'others') ? 'selected' : ''; ?>>Others</option>
                                </select>
                            </div>

                            <div class="flex justify-between mt-6">
                                <button type="button" class="btn-outline-sky px-8 py-3 rounded-full font-bold" @click="currentStep = 1">
                                    <i class="fas fa-arrow-left mr-2"></i> Back
                                </button>
                                <button type="button" class="btn-sky px-8 py-3 rounded-full font-bold" @click="currentStep = 3">
                                    Next <i class="fas fa-arrow-right ml-2"></i>
                                </button>
                            </div>
                        </div>

                        <!-- ===== SECTION 4: SUMMARY & REQUIREMENTS ===== -->
                        <div class="form-section" :class="{ active: currentStep === 3 }">
                            <h2 class="text-2xl font-bold text-[#0a1e3d] mb-6">Summary</h2>

                            <div class="bg-[#f8faff] rounded-xl p-6 mb-6 border border-[#e2ebf6]">
                                <p class="text-sm text-[#1a3a6b]">Please review your information before submitting.</p>
                                <?php if ($course_info): ?>
                                    <p class="text-sm text-[#1a3a6b] mt-2">
                                        <strong>Course:</strong> <?php echo htmlspecialchars($course_info['code']); ?> - <?php echo htmlspecialchars($course_info['name']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <!-- College Requirements Section -->
                            <div class="requirements-section">
                                <div class="req-title">
                                    <i class="fas fa-graduation-cap"></i> College Requirements
                                </div>
                                <p class="text-sm text-[#1a3a6b] mb-3">Original Copy of the following documents shall be submitted to your respective branch:</p>
                                
                                <div class="req-subtitle">College New / Freshmen</div>
                                <ul>
                                    <li><i class="fas fa-check-circle"></i> Form 138 (Report Card)</li>
                                    <li><i class="fas fa-check-circle"></i> Form 137</li>
                                    <li><i class="fas fa-check-circle"></i> Certificate of Good Moral</li>
                                    <li><i class="fas fa-check-circle"></i> PSA Authenticated Birth Certificate</li>
                                    <li><i class="fas fa-check-circle"></i> Passport Size ID Picture (White Background, Formal Attire) - 2pcs.</li>
                                    <li><i class="fas fa-check-circle"></i> Barangay Clearance</li>
                                </ul>

                                <div class="transferee-section">
                                    <div class="req-subtitle">College Transferee</div>
                                    <ul>
                                        <li><i class="fas fa-check-circle"></i> Transcript of Records from Previous School</li>
                                        <li><i class="fas fa-check-circle"></i> Honorable Dismissal</li>
                                        <li><i class="fas fa-check-circle"></i> Certificate of Good Moral</li>
                                        <li><i class="fas fa-check-circle"></i> PSA Authenticated Birth Certificate</li>
                                        <li><i class="fas fa-check-circle"></i> Passport Size ID Picture (White Background, Formal Attire) - 2pcs.</li>
                                        <li><i class="fas fa-check-circle"></i> Barangay Clearance</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="flex justify-between mt-6">
                                <button type="button" class="btn-outline-sky px-8 py-3 rounded-full font-bold" @click="currentStep = 2">
                                    <i class="fas fa-arrow-left mr-2"></i> Back
                                </button>
                                <button type="submit" class="btn-sky px-10 py-3 rounded-full font-bold" @click="validateForm">
                                    <i class="fas fa-paper-plane mr-2"></i> Submit Application
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
            </div>

            <p class="text-center text-sm text-[#1a3a6b] mt-6">
                BCP Online Admission © <?php echo date('Y'); ?>
            </p>
        </div>
    </section>

    <!-- ===== STATS SECTION ===== -->
    <div class="bg-bcp-deep text-white py-16 my-6 rounded-[4rem] rounded-b-3xl mx-4 md:mx-8 shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 right-0 w-80 h-80 bg-[#4fc3f7]/10 rounded-full blur-2xl"></div>
        <div class="absolute bottom-0 left-0 w-80 h-80 bg-[#29b6f6]/10 rounded-full blur-2xl"></div>
        
        <div class="max-w-7xl mx-auto px-4 relative z-10 flex flex-wrap justify-around items-center gap-8 text-center">
            <div>
                <div class="stat-number text-5xl">45,000+</div>
                <div class="font-light opacity-80 mt-1">Students</div>
                <div class="text-xs opacity-50">A thriving learning community across all programs and campuses.</div>
            </div>
            <div>
                <div class="stat-number text-5xl">2002</div>
                <div class="font-light opacity-80 mt-1">Established</div>
                <div class="text-xs opacity-50">Founded in June 2002 — over two decades of quality education.</div>
            </div>
            <div>
                <div class="stat-number text-5xl">3</div>
                <div class="font-light opacity-80 mt-1">Campuses</div>
                <div class="text-xs opacity-50">Millionaire's Village, Main, and Bulacan campuses serving learners.</div>
            </div>
            <div class="glass px-8 py-4 rounded-full flex items-center gap-4 border border-white/10">
                <img src="assets/bcp-logo.png" alt="BCP" class="h-10 w-auto" onerror="this.style.display='none'" />
                <span class="font-bold tracking-wider text-lg">BCP</span>
                <span class="opacity-30">|</span>
                <span class="font-light">PHILIPPINES</span>
            </div>
        </div>
    </div>

    <!-- ===== FOOTER ===== -->
    <footer class="bg-[#0a1e3d] text-white py-12">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <a href="index.html">
                            <img src="assets/bcp-logo.png" alt="Bestlink College of the Philippines logo" class="h-12 w-auto" onerror="this.style.display='none'" />
                        </a>
                        <a href="index.html" class="font-bold text-lg hover:text-bcp-sky transition-colors">BCP</a>
                    </div>
                    <p class="text-gray-400 text-sm leading-relaxed">
                        At Bestlink College of the Philippines, We provide and promote quality education with modern and unique techniques to able to enhance the skill and the knowledge of our dear students to make them globally competitive and productive citizens.
                    </p>
                </div>
                <div>
                    <h4 class="font-bold text-white mb-4">EXPLORE</h4>
                    <ul class="space-y-2 text-gray-400 text-sm">
                        <li><a href="index.html" class="footer-link">Home</a></li>
                        <li><a href="about.html" class="footer-link">About</a></li>
                        <li><a href="programs.html" class="footer-link">Programs</a></li>
                        <li><a href="admissions.html" class="footer-link">Admissions</a></li>
                        <li><a href="student-services.html" class="footer-link">Student Services</a></li>
                        <li><a href="#" class="footer-link">News</a></li>
                        <li><a href="events.php" class="footer-link">Events</a></li>
                        <li><a href="#" class="footer-link">Contact</a></li>
                        <li><a href="#" class="footer-link">Gallery</a></li>
                        <li><a href="#" class="footer-link">Careers</a></li>
                        <li><a href="#" class="footer-link">Scholarships</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold text-white mb-4">USEFUL LINKS</h4>
                    <ul class="space-y-2 text-gray-400 text-sm">
                        <li><a href="index.html" class="footer-link">Home</a></li>
                        <li><a href="#" class="footer-link">BCP College LMS</a></li>
                        <li><a href="#" class="footer-link">BCP SHS LMS</a></li>
                        <li><a href="#" class="footer-link">BCP SMS</a></li>
                        <li><a href="#" class="footer-link">BCP Student E-Mail</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold text-white mb-4">CONTACT</h4>
                    <ul class="space-y-3 text-gray-400 text-sm">
                        <li class="flex gap-3"><i class="fas fa-map-marker-alt text-bcp-sky mt-1"></i> #1071 Brgy. Kaligayahan, Quirino Highway, Novaliches Quezon City, Philippines 1123</li>
                        <li class="flex gap-3"><i class="fas fa-envelope text-bcp-sky mt-1"></i> bcp-inquiry@bcp.edu.ph</li>
                        <li class="flex gap-3"><i class="fas fa-phone text-bcp-sky mt-1"></i> 7000-5317 · 8442-8601</li>
                    </ul>
                    <div class="flex gap-4 mt-4">
                        <a href="#" class="text-gray-400 hover:text-bcp-sky transition-colors"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-gray-400 hover:text-bcp-sky transition-colors"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-gray-400 hover:text-bcp-sky transition-colors"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-gray-400 hover:text-bcp-sky transition-colors"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
            </div>
            <div class="border-t border-white/10 mt-8 pt-6 flex flex-wrap justify-between items-center text-sm text-gray-400">
                <span>BCP Online Admission © <?php echo date('Y'); ?></span>
                <span>Developed by BCP MIS Department</span>
            </div>
        </div>
    </footer>
</div>

<!-- ===== VUE APP ===== -->
<script>
    const { createApp, ref, onMounted } = Vue;

    const app = createApp({
        setup() {
            const currentStep = ref(0);

            // Calculate age from date of birth
            const calculateAge = function(event) {
                const dobInput = document.getElementById('date_of_birth');
                const ageInput = document.getElementById('age');
                
                if (dobInput && dobInput.value) {
                    const birthDate = new Date(dobInput.value);
                    const today = new Date();
                    let age = today.getFullYear() - birthDate.getFullYear();
                    const monthDiff = today.getMonth() - birthDate.getMonth();
                    
                    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                        age--;
                    }
                    
                    if (ageInput) {
                        ageInput.value = age > 0 ? age : '';
                    }
                }
            };

            // Validate required fields in step 1 before proceeding
            const validateAndProceed = function(step) {
                const form = document.getElementById('enrollmentForm');
                const inputs = form.querySelectorAll('.form-section.active .form-input[required]');
                let valid = true;
                
                inputs.forEach(input => {
                    if (!input.value.trim()) {
                        input.style.borderColor = '#ef5350';
                        valid = false;
                    } else {
                        input.style.borderColor = '#e2ebf6';
                    }
                });
                
                if (valid) {
                    currentStep.value = step;
                } else {
                    alert('Please fill in all required fields marked with * before proceeding.');
                }
            };

            // Validate form before submit
            const validateForm = function(event) {
                const form = document.getElementById('enrollmentForm');
                const inputs = form.querySelectorAll('.form-input[required]');
                let valid = true;
                
                inputs.forEach(input => {
                    if (!input.value.trim()) {
                        input.style.borderColor = '#ef5350';
                        valid = false;
                    } else {
                        input.style.borderColor = '#e2ebf6';
                    }
                });
                
                if (!valid) {
                    event.preventDefault();
                    alert('Please fill in all required fields marked with * before submitting.');
                    // Go to the first section with errors
                    const firstError = form.querySelector('.form-input[required][style*="border-color: rgb(239, 83, 80)"]');
                    if (firstError) {
                        const section = firstError.closest('.form-section');
                        if (section) {
                            const sections = form.querySelectorAll('.form-section');
                            sections.forEach((s, index) => {
                                if (s === section) {
                                    currentStep.value = index;
                                }
                            });
                        }
                    }
                }
            };

            // Auto-calculate age when page loads if date of birth is set
            onMounted(() => {
                const dobInput = document.getElementById('date_of_birth');
                if (dobInput && dobInput.value) {
                    calculateAge();
                }
            });

            return {
                currentStep,
                calculateAge,
                validateAndProceed,
                validateForm
            };
        }
    });

    app.mount('#app');
</script>

</body>
</html>