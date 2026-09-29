<?php
// enroll.php - Enrollment confirmation/success page

session_start();

// Get applicant_id from URL
$applicant_id = isset($_GET['applicant_id']) ? intval($_GET['applicant_id']) : 0;

// Database connection
$host = 'localhost';
$dbname = 'lms';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Fetch applicant data
$applicant_data = null;
$course_info = null;

if ($applicant_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT a.*, r.code, r.name as course_name FROM enr_applicants a 
                               LEFT JOIN rgr_courses r ON a.course_id = r.id 
                               WHERE a.applicant_id = ?");
        $stmt->execute([$applicant_id]);
        $applicant_data = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log('Error fetching applicant: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrollment Confirmation - Bestlink College of the Philippines</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-2xl w-full">
            <div class="text-center">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check-circle text-green-500 text-4xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Enrollment Submitted!</h1>
                <p class="text-gray-600 mb-6">Your application has been successfully submitted.</p>
            </div>

            <?php if ($applicant_data): ?>
            <div class="bg-gray-50 rounded-xl p-6 mb-6">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Applicant ID</p>
                        <p class="font-semibold text-gray-800">#<?php echo str_pad($applicant_id, 6, '0', STR_PAD_LEFT); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Status</p>
                        <p class="font-semibold text-blue-600 capitalize"><?php echo htmlspecialchars($applicant_data['status'] ?? 'Pending'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Name</p>
                        <p class="font-semibold text-gray-800">
                            <?php echo htmlspecialchars($applicant_data['first_name'] . ' ' . $applicant_data['surname']); ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Course</p>
                        <p class="font-semibold text-gray-800">
                            <?php echo htmlspecialchars($applicant_data['code'] ?? 'N/A') . ' - ' . htmlspecialchars($applicant_data['course_name'] ?? ''); ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
                <h3 class="font-semibold text-blue-800 flex items-center gap-2">
                    <i class="fas fa-info-circle"></i> What's Next?
                </h3>
                <ul class="mt-2 space-y-2 text-sm text-blue-700">
                    <li><i class="fas fa-circle text-xs mr-2"></i> Our admissions team will review your application</li>
                    <li><i class="fas fa-circle text-xs mr-2"></i> You will receive an email confirmation within 3-5 business days</li>
                    <li><i class="fas fa-circle text-xs mr-2"></i> Prepare the required documents listed in the requirements section</li>
                </ul>
            </div>

            <div class="flex gap-4">
                <a href="index.html" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-3 px-4 rounded-xl transition text-center">
                    <i class="fas fa-home mr-2"></i> Home
                </a>
                <a href="online-admission.html" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl transition text-center">
                    <i class="fas fa-plus mr-2"></i> New Application
                </a>
            </div>
        </div>
    </div>
</body>
</html>