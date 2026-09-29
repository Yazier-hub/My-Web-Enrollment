<?php
// pages/enroll.php - Enrollment confirmation/success page (OOP refactor)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../classes/EnrollmentController.php';

$controller = new EnrollmentController();
$controller->handleRequest();

$applicant  = $controller->applicant;
$hasApp     = $controller->hasApplicant();
$e          = static fn(string $v): string => $controller->e($v);
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

            <?php if ($hasApp): ?>
            <div class="bg-gray-50 rounded-xl p-6 mb-6">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Applicant ID</p>
                        <p class="font-semibold text-gray-800"><?php echo $e($applicant->getFormattedId()); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Status</p>
                        <p class="font-semibold text-blue-600"><?php echo $e($applicant->getStatus()); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Name</p>
                        <p class="font-semibold text-gray-800"><?php echo $e($applicant->getFullName()); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Course</p>
                        <p class="font-semibold text-gray-800"><?php echo $e($applicant->getCourseLabel()); ?></p>
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