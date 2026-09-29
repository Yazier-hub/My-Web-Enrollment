<?php
// contact.php - Modern Contact Page (Standalone, root-level)
// Located at: C:\xampp\htdocs\web\contact.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// DATABASE CONNECTION
// ============================================================
$host     = 'localhost';
$dbname   = 'lms';
$username = 'root';
$password = '';

$pdo = null;
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log('contact.php DB connect failed: ' . $e->getMessage());
}

// ============================================================
// HANDLE FORM SUBMISSION
// ============================================================
$formSuccess = false;
$formError   = '';
$oldInput    = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send_message') {
    $oldInput['name']    = trim($_POST['name']    ?? '');
    $oldInput['email']   = trim($_POST['email']   ?? '');
    $oldInput['subject'] = trim($_POST['subject'] ?? '');
    $oldInput['message'] = trim($_POST['message'] ?? '');

    if (empty($oldInput['name'])) {
        $formError = 'Please enter your full name.';
    } elseif (empty($oldInput['email']) || !filter_var($oldInput['email'], FILTER_VALIDATE_EMAIL)) {
        $formError = 'Please enter a valid email address.';
    } elseif (empty($oldInput['subject'])) {
        $formError = 'Please enter a subject.';
    } elseif (empty($oldInput['message'])) {
        $formError = 'Please enter your message.';
    } else {
        if ($pdo) {
            try {
                $pdo->exec("
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

                $stmt = $pdo->prepare("
                    INSERT INTO `enr_contact_messages` (name, email, subject, message)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $oldInput['name'],
                    $oldInput['email'],
                    $oldInput['subject'],
                    $oldInput['message']
                ]);

                $formSuccess = true;
                $oldInput = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];

            } catch (Exception $e) {
                error_log('contact.php insert failed: ' . $e->getMessage());
                $formError = 'Database error. Please try again later.';
            }
        } else {
            $formError = 'Database connection unavailable. Please try again later.';
        }
    }
}

$pageTitle = 'Contact Us';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Contact Us · Bestlink College of the Philippines</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html { scroll-behavior: smooth; }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', Roboto, sans-serif;
      background: #f8fafc;
      color: #0f172a;
      -webkit-font-smoothing: antialiased;
    }

    /* ============================================================
       MODERN DESIGN SYSTEM
       ============================================================ */
    :root {
      --navy:      #0a1e3d;
      --sky:       #4fc3f7;
      --sky-dark:  #29b6f6;
      --sky-soft:  #eef4ff;
      --gray-50:   #f8fafc;
      --gray-100:  #f1f5f9;
      --gray-200:  #e2e8f0;
      --gray-400:  #94a3b8;
      --gray-500:  #64748b;
      --gray-900:  #0f172a;
    }

    /* ============================================================
       NAV BAR
       ============================================================ */
    .glass-nav {
      background: rgba(10, 30, 61, 0.88);
      backdrop-filter: blur(20px) saturate(180%);
      -webkit-backdrop-filter: blur(20px) saturate(180%);
      border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    }
    .nav-link {
      position: relative;
      transition: color 0.3s;
      font-weight: 500;
    }
    .nav-link::after {
      content: '';
      position: absolute;
      bottom: -4px; left: 0;
      width: 0; height: 2px;
      background: var(--sky);
      transition: width 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .nav-link:hover::after { width: 100%; }
    .nav-link:hover { color: var(--sky); }

    .btn-sky {
      background: linear-gradient(135deg, #4fc3f7, #29b6f6);
      color: var(--navy);
      transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
      box-shadow: 0 4px 20px rgba(79, 195, 247, 0.3);
      font-weight: 700;
    }
    .btn-sky:hover {
      transform: translateY(-2px);
      box-shadow: 0 16px 36px -8px rgba(79, 195, 247, 0.55);
    }

    .text-bcp-sky { color: var(--sky); }

    /* ============================================================
       HERO — Modern gradient with animated orbs
       ============================================================ */
    .hero {
      padding: 160px 0 100px;
      background: linear-gradient(180deg, #ffffff 0%, #f0f7ff 100%);
      position: relative;
      overflow: hidden;
    }
    .hero-orb {
      position: absolute;
      border-radius: 50%;
      filter: blur(80px);
      opacity: 0.5;
      pointer-events: none;
      animation: floatOrb 12s ease-in-out infinite;
    }
    .hero-orb-1 {
      width: 400px;
      height: 400px;
      background: radial-gradient(circle, rgba(79, 195, 247, 0.4), transparent 70%);
      top: -100px;
      right: -100px;
    }
    .hero-orb-2 {
      width: 350px;
      height: 350px;
      background: radial-gradient(circle, rgba(41, 182, 246, 0.35), transparent 70%);
      bottom: -100px;
      left: -100px;
      animation-delay: -6s;
    }
    @keyframes floatOrb {
      0%, 100% { transform: translate(0, 0) scale(1); }
      50%      { transform: translate(30px, -30px) scale(1.05); }
    }

    .hero-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.6rem;
      padding: 0.5rem 1.1rem;
      border-radius: 40px;
      background: rgba(255, 255, 255, 0.9);
      border: 1px solid var(--gray-200);
      font-size: 0.72rem;
      font-weight: 700;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      color: var(--gray-500);
      margin-bottom: 1.5rem;
      box-shadow: 0 4px 16px rgba(10, 30, 61, 0.06);
      backdrop-filter: blur(10px);
    }
    .hero-badge::before {
      content: '';
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: var(--sky);
      box-shadow: 0 0 12px var(--sky);
      animation: pulseDot 2s ease-in-out infinite;
    }
    @keyframes pulseDot {
      0%, 100% { opacity: 1; transform: scale(1); }
      50%      { opacity: 0.5; transform: scale(0.85); }
    }

    .hero h1 {
      font-size: 4rem;
      font-weight: 800;
      letter-spacing: -2px;
      margin-bottom: 1.25rem;
      line-height: 1.05;
      background: linear-gradient(135deg, #0f172a 0%, #1e40af 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .hero p {
      font-size: 1.2rem;
      color: var(--gray-500);
      max-width: 620px;
      margin: 0 auto;
      line-height: 1.65;
      font-weight: 400;
    }

    /* ============================================================
       BODY
       ============================================================ */
    .contact-body {
      padding: 80px 0 100px;
      background: var(--gray-50);
    }

    /* ============================================================
       MODERN INFO CARDS
       ============================================================ */
    .info-card {
      background: white;
      border: 1px solid var(--gray-100);
      border-radius: 20px;
      padding: 1.75rem;
      display: flex;
      align-items: flex-start;
      gap: 1.25rem;
      margin-bottom: 1.25rem;
      transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
      position: relative;
      overflow: hidden;
    }
    .info-card::before {
      content: '';
      position: absolute;
      top: 0; left: 0;
      width: 4px;
      height: 100%;
      background: linear-gradient(180deg, var(--sky), var(--sky-dark));
      transform: scaleY(0);
      transform-origin: top;
      transition: transform 0.4s ease;
    }
    .info-card:hover::before { transform: scaleY(1); }
    .info-card:hover {
      transform: translateY(-4px);
      box-shadow:
        0 4px 6px -1px rgba(10, 30, 61, 0.04),
        0 20px 40px -12px rgba(10, 30, 61, 0.12);
      border-color: rgba(79, 195, 247, 0.25);
    }

    .info-icon {
      width: 52px;
      height: 52px;
      border-radius: 14px;
      background: linear-gradient(135deg, var(--sky-soft), #dbeafe);
      color: var(--sky);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.25rem;
      flex-shrink: 0;
      transition: all 0.4s ease;
    }
    .info-card:hover .info-icon {
      background: linear-gradient(135deg, var(--sky), var(--sky-dark));
      color: white;
      transform: scale(1.05) rotate(-5deg);
      box-shadow: 0 8px 20px rgba(79, 195, 247, 0.4);
    }

    .info-content { flex: 1; min-width: 0; }
    .info-label {
      font-size: 0.7rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 1.5px;
      color: var(--gray-400);
      margin-bottom: 0.5rem;
    }
    .info-value {
      font-size: 1rem;
      color: var(--gray-900);
      line-height: 1.55;
      font-weight: 600;
      word-break: break-word;
    }
    .info-value a {
      color: var(--gray-900);
      text-decoration: none;
      transition: all 0.2s;
    }
    .info-value a:hover { color: var(--sky); }

    /* ============================================================
       MAP
       ============================================================ */
    .map-wrapper {
      position: relative;
      margin-top: 1.25rem;
      border-radius: 20px;
      overflow: hidden;
      border: 1px solid var(--gray-100);
      box-shadow: 0 4px 20px rgba(10, 30, 61, 0.06);
      height: 280px;
      transition: all 0.4s ease;
    }
    .map-wrapper:hover {
      box-shadow: 0 20px 40px -12px rgba(10, 30, 61, 0.15);
      transform: translateY(-2px);
    }
    .map-wrapper iframe {
      width: 100%;
      height: 100%;
      border: 0;
      display: block;
      filter: saturate(1.1);
    }

    /* ============================================================
       MODERN FORM CARD
       ============================================================ */
    .form-card {
      background: white;
      border: 1px solid var(--gray-100);
      border-radius: 24px;
      padding: 3rem;
      box-shadow:
        0 1px 3px rgba(10, 30, 61, 0.04),
        0 20px 40px -20px rgba(10, 30, 61, 0.1);
      position: relative;
      overflow: hidden;
    }
    .form-card::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -30%;
      width: 300px;
      height: 300px;
      background: radial-gradient(circle, rgba(79, 195, 247, 0.08), transparent 70%);
      border-radius: 50%;
      pointer-events: none;
    }

    .form-card h2 {
      font-size: 1.85rem;
      font-weight: 800;
      color: var(--gray-900);
      margin-bottom: 0.5rem;
      letter-spacing: -0.5px;
      position: relative;
    }
    .form-card .form-subtitle {
      color: var(--gray-500);
      font-size: 0.95rem;
      margin-bottom: 2rem;
      position: relative;
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.25rem;
      margin-bottom: 1.25rem;
    }
    .form-group { margin-bottom: 1.25rem; }
    .form-row .form-group { margin-bottom: 0; }

    .form-group label {
      display: block;
      font-weight: 700;
      font-size: 0.75rem;
      color: var(--gray-500);
      margin-bottom: 0.6rem;
      text-transform: uppercase;
      letter-spacing: 0.8px;
    }

    .form-group input,
    .form-group textarea {
      width: 100%;
      padding: 1rem 1.15rem;
      border: 1.5px solid var(--gray-200);
      border-radius: 14px;
      font-size: 0.95rem;
      color: var(--gray-900);
      background: white;
      transition: all 0.25s ease;
      font-family: inherit;
      outline: none;
      font-weight: 500;
    }
    .form-group input::placeholder,
    .form-group textarea::placeholder {
      color: var(--gray-400);
      font-weight: 400;
    }
    .form-group input:hover,
    .form-group textarea:hover {
      border-color: #cbd5e1;
    }
    .form-group input:focus,
    .form-group textarea:focus {
      border-color: var(--sky);
      box-shadow: 0 0 0 4px rgba(79, 195, 247, 0.12);
    }
    .form-group textarea {
      min-height: 140px;
      resize: vertical;
      line-height: 1.6;
    }

    .btn-submit {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.65rem;
      padding: 1rem 2.25rem;
      background: linear-gradient(135deg, #0a1e3d 0%, #1e40af 100%);
      color: white;
      border: none;
      border-radius: 14px;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
      font-family: inherit;
      box-shadow: 0 8px 20px -4px rgba(10, 30, 61, 0.4);
      margin-top: 0.5rem;
      position: relative;
      overflow: hidden;
    }
    .btn-submit::before {
      content: '';
      position: absolute;
      top: 0; left: -100%;
      width: 100%; height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
      transition: left 0.6s ease;
    }
    .btn-submit:hover::before { left: 100%; }
    .btn-submit:hover {
      transform: translateY(-3px);
      box-shadow: 0 20px 40px -8px rgba(30, 64, 175, 0.5);
    }
    .btn-submit:active { transform: translateY(-1px); }
    .btn-submit i { transition: transform 0.3s ease; }
    .btn-submit:hover i { transform: translateX(4px); }

    /* ============================================================
       ALERTS
       ============================================================ */
    .alert {
      padding: 1.1rem 1.35rem;
      border-radius: 14px;
      margin-bottom: 1.75rem;
      display: flex;
      align-items: flex-start;
      gap: 0.85rem;
      font-size: 0.9rem;
      line-height: 1.55;
      border: 1px solid;
    }
    .alert-success {
      background: linear-gradient(135deg, #d4e8fc, #e0f2fe);
      color: #1a3c6e;
      border-color: #b8d4e8;
    }
    .alert-error {
      background: linear-gradient(135deg, #dce8f5, #e0e7ff);
      color: #0f2a4e;
      border-color: #b8d4e8;
    }
    .alert i { margin-top: 0.15rem; font-size: 1.1rem; }

    /* ============================================================
       FOOTER
       ============================================================ */
    .footer-link { transition: color 0.3s; }
    .footer-link:hover { color: var(--sky); }

    /* ============================================================
       RESPONSIVE
       ============================================================ */
    @media (max-width: 1024px) {
      .contact-layout { grid-template-columns: 1fr !important; }
    }
    @media (max-width: 768px) {
      .hero { padding: 130px 0 70px; }
      .hero h1 { font-size: 2.75rem; letter-spacing: -1.5px; }
      .hero p { font-size: 1.05rem; }
      .form-row { grid-template-columns: 1fr; }
      .form-card { padding: 2rem; }
      .form-card h2 { font-size: 1.5rem; }
      .info-card { padding: 1.5rem; }
      .info-icon { width: 46px; height: 46px; font-size: 1.1rem; }
    }
  </style>
</head>
<body>

<!-- ===== TOP NAV ===== -->
<header class="glass-nav fixed top-0 left-0 right-0 z-50 text-white py-3">
  <div class="max-w-7xl mx-auto px-4 flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-3">
      <a href="index.html">
        <img src="assets/bcp-logo.png" alt="BCP Logo" class="h-11 w-auto object-contain" />
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
      <a href="contact.php" class="nav-link px-1 py-1 text-bcp-sky">Contact</a>
      <a href="online-admission.html" class="btn-sky px-6 py-2 rounded-full flex items-center gap-2 text-sm">
        <i class="fas fa-user-graduate"></i> Enroll Now!
      </a>
    </nav>
  </div>
</header>

<!-- ===== HERO ===== -->
<section class="hero">
  <div class="hero-orb hero-orb-1"></div>
  <div class="hero-orb hero-orb-2"></div>

  <div class="max-w-4xl mx-auto px-4 text-center relative z-10">
    <div class="hero-badge">Get in Touch</div>
    <h1>Contact Us</h1>
    <p>
      Have a question about programs, admission, or student services?
      We'd love to hear from you — our team is ready to help.
    </p>
  </div>
</section>

<!-- ===== BODY ===== -->
<section class="contact-body">
  <div class="max-w-7xl mx-auto px-4">

    <div class="contact-layout" style="display:grid;grid-template-columns:1fr 1.5fr;gap:2rem;align-items:start;">

      <!-- LEFT: Info Cards + Map -->
      <div>
        <div class="info-card">
          <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
          <div class="info-content">
            <div class="info-label">Address</div>
            <div class="info-value">
              #1071 Brgy. Kaligayahan, Quirino Highway,
              Novaliches Quezon City, Philippines 1123
            </div>
          </div>
        </div>

        <div class="info-card">
          <div class="info-icon"><i class="fas fa-envelope"></i></div>
          <div class="info-content">
            <div class="info-label">Email</div>
            <div class="info-value">
              <a href="mailto:bcp-inquiry@bcp.edu.ph">bcp-inquiry@bcp.edu.ph</a>
            </div>
          </div>
        </div>

        <div class="info-card">
          <div class="info-icon"><i class="fas fa-phone"></i></div>
          <div class="info-content">
            <div class="info-label">Phone</div>
            <div class="info-value">7000-5317</div>
          </div>
        </div>

        <div class="map-wrapper">
          <iframe
            src="https://www.openstreetmap.org/export/embed.html?bbox=120.98,14.68,121.05,14.75&layer=mapnik&marker=14.715,121.015"
            allowfullscreen=""
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            title="BCP Location">
          </iframe>
        </div>
      </div>

      <!-- RIGHT: Contact Form -->
      <div class="form-card">

        <h2>Send us a message</h2>
        <p class="form-subtitle">Fill out the form and our team will get back to you shortly.</p>

        <?php if ($formSuccess): ?>
          <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <div>
              <strong>Message sent successfully!</strong><br>
              Thank you for reaching out. We'll get back to you as soon as possible.
            </div>
          </div>
        <?php endif; ?>

        <?php if (!empty($formError)): ?>
          <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <div><?php echo htmlspecialchars($formError); ?></div>
          </div>
        <?php endif; ?>

        <form method="POST" action="contact.php" id="contactForm">
          <input type="hidden" name="action" value="send_message">

          <div class="form-row">
            <div class="form-group">
              <label for="name">Full Name</label>
              <input
                type="text"
                id="name"
                name="name"
                placeholder="Juan dela Cruz"
                value="<?php echo htmlspecialchars($oldInput['name']); ?>"
                required
              >
            </div>
            <div class="form-group">
              <label for="email">Email Address</label>
              <input
                type="email"
                id="email"
                name="email"
                placeholder="you@example.com"
                value="<?php echo htmlspecialchars($oldInput['email']); ?>"
                required
              >
            </div>
          </div>

          <div class="form-group">
            <label for="subject">Subject</label>
            <input
              type="text"
              id="subject"
              name="subject"
              placeholder="Admission inquiry"
              value="<?php echo htmlspecialchars($oldInput['subject']); ?>"
              required
            >
          </div>

          <div class="form-group">
            <label for="message">Message</label>
            <textarea
              id="message"
              name="message"
              placeholder="How can we help you?"
              required
            ><?php echo htmlspecialchars($oldInput['message']); ?></textarea>
          </div>

          <button type="submit" class="btn-submit">
            Send Message
            <i class="fas fa-paper-plane"></i>
          </button>
        </form>
      </div>

    </div>
  </div>
</section>

<!-- ===== FOOTER ===== -->
<footer class="bg-[#0a1e3d] text-white py-12">
  <div class="max-w-7xl mx-auto px-4">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
      <div>
        <div class="flex items-center gap-3 mb-4">
          <a href="index.html">
            <img src="assets/bcp-logo.png" alt="Bestlink College of the Philippines logo" class="h-12 w-auto" />
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
          <li><a href="contact.php" class="footer-link">Contact</a></li>
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
      <span>© Copyright BCP. All Rights Reserved</span>
      <span>Developed by BCP MIS Department</span>
    </div>
  </div>
</footer>

</body>
</html>