<?php
// course-selection.php - COMPLETE FIXED VERSION

// Database connection
$host = 'localhost';
$dbname = 'sms';  // Fixed: Correct database name
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Start session to store course_id
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get branch from URL parameter
$branch = isset($_GET['branch']) ? $_GET['branch'] : 'main';

// Fetch courses from rgr_courses table
$courses = [];
try {
    // Fixed: Using rgr_courses table with correct column names
    $stmt = $pdo->query("SELECT id, code, name, years FROM rgr_courses ORDER BY code");
    $dbCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($dbCourses as $c) {
        $courses[] = [
            'id' => $c['id'],
            'code' => $c['code'],
            'name' => $c['name'],
            'years' => $c['years'] ?? 4
        ];
    }
} catch(PDOException $e) {
    error_log('Error fetching courses: ' . $e->getMessage());
    $courses = [];
}

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_id'])) {
    $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
    
    if ($course_id > 0) {
        // Store course_id in session
        $_SESSION['selected_course_id'] = $course_id;
        
        // Find course name for message
        $courseName = '';
        foreach ($courses as $c) {
            if ($c['id'] == $course_id) {
                $courseName = $c['code'] . ' - ' . $c['name'];
                break;
            }
        }
        
        $message = "Course selected successfully: " . $courseName;
        $messageType = 'success';
        
        // Redirect to enrollment form after 1.5 seconds
        echo "<script>setTimeout(function(){ window.location.href = 'enrollment-form.php'; }, 1500);</script>";
    } else {
        $message = "Please select a course.";
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Select Course · Bestlink College of the Philippines</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <style>
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

        .course-hero {
            background: linear-gradient(135deg, #0a1e3d 0%, #1a3a6b 50%, #0a1e3d 100%);
            padding: 140px 0 60px;
            position: relative;
            overflow: hidden;
        }
        .course-hero::before {
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

        .course-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: 1px solid rgba(79, 195, 247, 0.08);
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            cursor: pointer;
            height: 100%;
        }
        .course-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 48px -12px rgba(10, 30, 61, 0.15);
            border-color: rgba(79, 195, 247, 0.25);
        }
        .course-card.selected {
            border-color: #4fc3f7;
            box-shadow: 0 0 0 3px rgba(79, 195, 247, 0.2), 0 8px 30px rgba(79, 195, 247, 0.1);
            background: rgba(79, 195, 247, 0.03);
        }
        .course-card .course-code {
            font-size: 1.1rem;
            font-weight: 700;
            color: #0a1e3d;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .course-card .course-code .badge {
            font-size: 0.6rem;
            font-weight: 600;
            color: #4fc3f7;
            background: rgba(79, 195, 247, 0.1);
            padding: 0.15rem 0.7rem;
            border-radius: 40px;
        }
        .course-card .course-name {
            color: #1a3a6b;
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }
        .course-card .course-years {
            color: #4fc3f7;
            font-size: 0.75rem;
            font-weight: 500;
            margin-top: 0.25rem;
        }
        .course-card .proceed-btn {
            display: none;
            margin-top: 1rem;
            padding: 0.5rem 1.5rem;
            background: linear-gradient(135deg, #4fc3f7, #29b6f6);
            color: #0a1e3d;
            font-weight: 600;
            border-radius: 40px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.85rem;
            width: 100%;
            text-align: center;
        }
        .course-card.selected .proceed-btn {
            display: block;
        }
        .course-card .proceed-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(79, 195, 247, 0.4);
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            color: #4fc3f7;
            display: inline-block;
        }

        .footer-link {
            transition: color 0.3s;
        }
        .footer-link:hover {
            color: #4fc3f7;
        }

        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.15);
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

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .course-card {
            animation: fadeInUp 0.6s ease forwards;
            opacity: 0;
        }
        .course-card:nth-child(1) { animation-delay: 0.05s; }
        .course-card:nth-child(2) { animation-delay: 0.1s; }
        .course-card:nth-child(3) { animation-delay: 0.15s; }
        .course-card:nth-child(4) { animation-delay: 0.2s; }
        .course-card:nth-child(5) { animation-delay: 0.25s; }
        .course-card:nth-child(6) { animation-delay: 0.3s; }
        .course-card:nth-child(7) { animation-delay: 0.35s; }
        .course-card:nth-child(8) { animation-delay: 0.4s; }
        .course-card:nth-child(9) { animation-delay: 0.45s; }
        .course-card:nth-child(10) { animation-delay: 0.5s; }
        .course-card:nth-child(11) { animation-delay: 0.55s; }
        .course-card:nth-child(12) { animation-delay: 0.6s; }
        .course-card:nth-child(13) { animation-delay: 0.65s; }
        .course-card:nth-child(14) { animation-delay: 0.7s; }

        @media (max-width: 640px) {
            .course-card {
                padding: 1.25rem;
            }
        }

        .message-success {
            background: #e8f5e9;
            border: 2px solid #4caf50;
            border-radius: 16px;
            padding: 1rem 1.5rem;
            color: #2e7d32;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .message-error {
            background: #ffebee;
            border: 2px solid #ef5350;
            border-radius: 16px;
            padding: 1rem 1.5rem;
            color: #c62828;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .note-text {
            font-size: 0.8rem;
            color: #1a3a6b;
            background: #f8faff;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            border-left: 3px solid #4fc3f7;
        }

        .no-courses {
            background: #f8faff;
            border: 2px dashed #e2ebf6;
            border-radius: 16px;
            padding: 3rem 2rem;
            text-align: center;
        }
        .no-courses .icon {
            font-size: 3rem;
            color: #a0b4c8;
            margin-bottom: 1rem;
        }
        .no-courses h3 {
            color: #0a1e3d;
            font-size: 1.2rem;
            font-weight: 600;
        }
        .no-courses p {
            color: #1a3a6b;
            font-size: 0.9rem;
            margin-top: 0.5rem;
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
                <a href="events.html" class="nav-link px-1 py-1 text-gray-300">Events</a>
                <a href="#" class="nav-link px-1 py-1 text-gray-300">Contact</a>
                <a href="online-admission.html" class="btn-sky px-6 py-2 rounded-full font-bold flex items-center gap-2 text-sm">
                    <i class="fas fa-user-graduate"></i> Enroll Now!
                </a>
            </nav>
        </div>
    </header>

    <!-- ===== HERO ===== -->
    <section class="course-hero">
        <div class="float-shape w-96 h-96 bg-[#4fc3f7] top-20 -left-20"></div>
        <div class="float-shape w-64 h-64 bg-[#29b6f6] bottom-20 -right-20"></div>
        
        <div class="max-w-7xl mx-auto px-4 relative z-10">
            <div class="text-center text-white">
                <span class="inline-block bg-white/10 backdrop-blur-sm text-bcp-sky text-xs font-semibold px-5 py-2 rounded-full mb-6 border border-white/10 hero-text">
                    <i class="fas fa-graduation-cap mr-2"></i> Bestlink College of the Philippines
                </span>
                <h1 class="text-4xl md:text-5xl font-extrabold leading-tight hero-text delay-1">
                    Enrollment Management <span class="text-bcp-sky">System</span>
                </h1>
                <p class="text-lg text-[#b0d4e8] mt-4 max-w-2xl mx-auto leading-relaxed hero-text delay-2">
                    Application Process — Select your course.
                </p>
            </div>
        </div>
    </section>

    <!-- ===== COURSE SELECTION ===== -->
    <section class="py-16 bg-white relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-[#4fc3f7]/5 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 left-0 w-64 h-64 bg-[#29b6f6]/5 rounded-full blur-3xl"></div>
        
        <div class="max-w-6xl mx-auto px-4 relative z-10">
            <?php if ($message): ?>
                <div class="<?php echo $messageType === 'success' ? 'message-success' : 'message-error'; ?>">
                    <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($courses)): ?>
                <!-- No Courses Available -->
                <div class="no-courses">
                    <div class="icon"><i class="fas fa-book-open"></i></div>
                    <h3>No Courses Available</h3>
                    <p>There are currently no courses available. Please contact the admissions office for more information.</p>
                </div>
            <?php else: ?>
                <!-- Note -->
                <div class="note-text mb-6">
                    <i class="fas fa-info-circle text-bcp-sky mr-2"></i>
                    Select your desired course below. You will be asked to provide your personal information in the next step.
                    <span class="block text-xs text-gray-500 mt-1">Total courses available: <?php echo count($courses); ?></span>
                </div>

                <form method="POST" action="" id="courseForm">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
                        <?php foreach ($courses as $course): ?>
                            <div class="course-card" @click="selectCourse(<?php echo $course['id']; ?>)" :class="{ selected: selectedCourse === <?php echo $course['id']; ?> }">
                                <div class="course-code">
                                    <?php echo htmlspecialchars($course['code']); ?>
                                    <span class="badge"><?php echo ucfirst($branch); ?></span>
                                </div>
                                <div class="course-name"><?php echo htmlspecialchars($course['name']); ?></div>
                                <div class="course-years"><?php echo $course['years']; ?> Year Program</div>
                                <input type="radio" name="course_id" value="<?php echo $course['id']; ?>" style="display:none;" :checked="selectedCourse === <?php echo $course['id']; ?>" />
                                <button type="submit" class="proceed-btn" @click.stop>
                                    <i class="fas fa-arrow-right mr-2"></i> Proceed
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Hidden submit for form compatibility -->
                    <input type="submit" style="display:none;" id="hiddenSubmit" />
                </form>
            <?php endif; ?>

            <p class="text-center text-sm text-[#1a3a6b] mt-8">
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
                        <li><a href="events.html" class="footer-link">Events</a></li>
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
    const { createApp, ref } = Vue;

    const app = createApp({
        setup() {
            const selectedCourse = ref(0);

            const selectCourse = (courseId) => {
                selectedCourse.value = courseId;
            };

            return {
                selectedCourse,
                selectCourse
            };
        }
    });

    app.mount('#app');
</script>

</body>
</html>