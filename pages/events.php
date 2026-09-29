<?php
// pages/events.php - Public Events Calendar (fully fixed OOP version)

// ---------- Session ----------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------- Load controller from parent /classes/ ----------
$controllerFile = __DIR__ . '/../classes/EventController.php';

$controller = null;
$repository = null;
$eventsByMonth  = [];
$totalEvents    = 0;
$upcomingCount  = 0;
$ongoingCount   = 0;
$completedCount = 0;
$fatalMessage   = null;

if (!file_exists($controllerFile)) {
    error_log('events.php: EventController.php not found at ' . $controllerFile);
    $fatalMessage = 'Events system is temporarily unavailable. Please try again later.';
} else {
    try {
        require_once $controllerFile;

        if (class_exists('EventController')) {
            $controller     = new EventController();
            $repository     = $controller->repository;
            $eventsByMonth  = $repository->byMonth();
            $totalEvents    = $repository->total();
            $upcomingCount  = $repository->upcomingCount();
            $ongoingCount   = $repository->ongoingCount();
            $completedCount = $repository->completedCount();
        } else {
            error_log('events.php: EventController class missing after include.');
            $fatalMessage = 'Events system is temporarily unavailable. Please try again later.';
        }
    } catch (Throwable $e) {
        error_log('events.php boot failed: ' . $e->getMessage());
        $fatalMessage = 'Events system is temporarily unavailable. Please try again later.';
    }
}

// ---------- Escape helper ----------
$e = static function ($v) use ($controller) {
    if ($controller && method_exists($controller, 'e')) {
        return $controller->e((string) $v);
    }
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};

$pageTitle = 'Events Calendar';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Events · Bestlink College of the Philippines</title>
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

    .glass-nav {
      background: rgba(10, 30, 61, 0.88);
      backdrop-filter: blur(20px) saturate(180%);
      -webkit-backdrop-filter: blur(20px) saturate(180%);
      border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    }
    .nav-link { position: relative; transition: color 0.3s; font-weight: 500; }
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

    .hero {
      padding: 160px 0 100px;
      background: linear-gradient(135deg, #0a1e3d 0%, #1a3a6b 50%, #0a1e3d 100%);
      position: relative;
      overflow: hidden;
      text-align: center;
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
      width: 400px; height: 400px;
      background: radial-gradient(circle, rgba(79, 195, 247, 0.4), transparent 70%);
      top: -100px; right: -100px;
    }
    .hero-orb-2 {
      width: 350px; height: 350px;
      background: radial-gradient(circle, rgba(41, 182, 246, 0.35), transparent 70%);
      bottom: -100px; left: -100px;
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
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.15);
      font-size: 0.72rem;
      font-weight: 700;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      color: var(--sky);
      margin-bottom: 1.5rem;
      backdrop-filter: blur(10px);
    }
    .hero-badge::before {
      content: '';
      width: 8px; height: 8px;
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
      color: white;
      letter-spacing: -2px;
      margin-bottom: 1.25rem;
      line-height: 1.05;
    }
    .hero h1 span { color: var(--sky); }
    .hero p {
      font-size: 1.15rem;
      color: #b0d4e8;
      max-width: 620px;
      margin: 0 auto;
      line-height: 1.65;
    }

    .hero-stats {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 2rem 3rem;
      margin-top: 2.5rem;
      position: relative;
      z-index: 1;
    }
    .hero-stat-value {
      font-size: 2.5rem;
      font-weight: 800;
      color: var(--sky);
      line-height: 1;
    }
    .hero-stat-label {
      font-size: 0.7rem;
      text-transform: uppercase;
      letter-spacing: 1.5px;
      color: #b0d4e8;
      margin-top: 0.5rem;
    }

    .events-body {
      padding: 80px 0 100px;
      background: var(--gray-50);
    }

    .month-header {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding-bottom: 1rem;
      border-bottom: 2px solid var(--gray-200);
      margin-bottom: 2rem;
      flex-wrap: wrap;
    }
    .month-header h2 {
      font-size: 1.65rem;
      font-weight: 800;
      color: var(--gray-900);
      letter-spacing: -0.5px;
    }
    .month-header .event-count {
      font-size: 0.75rem;
      font-weight: 700;
      color: var(--sky);
      background: var(--sky-soft);
      padding: 0.3rem 0.9rem;
      border-radius: 40px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .event-card {
      background: white;
      border-radius: 20px;
      padding: 1.75rem;
      transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
      border: 1px solid var(--gray-100);
      position: relative;
      overflow: hidden;
      height: 100%;
    }
    .event-card::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 3px;
      background: linear-gradient(90deg, var(--sky), var(--sky-dark));
      transform: scaleX(0);
      transform-origin: left;
      transition: transform 0.4s ease;
    }
    .event-card:hover::before { transform: scaleX(1); }
    .event-card:hover {
      transform: translateY(-4px);
      box-shadow:
        0 4px 6px -1px rgba(10, 30, 61, 0.04),
        0 20px 40px -12px rgba(10, 30, 61, 0.12);
      border-color: rgba(79, 195, 247, 0.25);
    }

    .event-card .event-tag {
      display: inline-flex;
      align-items: center;
      gap: 0.3rem;
      font-size: 0.65rem;
      font-weight: 800;
      padding: 0.25rem 0.75rem;
      border-radius: 40px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .event-tag.academic      { background: #e8f0fe; color: #1a3c6e; }
    .event-tag.meeting       { background: #d4e8fc; color: #1a3c6e; }
    .event-tag.seminar       { background: #e8f4fd; color: #2a5c9e; }
    .event-tag.institutional { background: #dce8f5; color: #0f2a4e; }
    .event-tag.cultural      { background: #cce5ff; color: #1a3c6e; }
    .event-tag.sports        { background: #d4e8fc; color: #2a5c9e; }
    .event-tag.orientation   { background: #e8f0fe; color: #1a3c6e; }
    .event-tag.other         { background: #e8f4fd; color: #5a7fa8; }

    .event-card .event-date-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      font-size: 0.72rem;
      font-weight: 700;
      color: var(--sky);
      background: var(--sky-soft);
      padding: 0.25rem 0.8rem;
      border-radius: 40px;
    }

    .status-pill {
      display: inline-flex;
      align-items: center;
      gap: 0.3rem;
      font-size: 0.65rem;
      font-weight: 800;
      padding: 0.25rem 0.75rem;
      border-radius: 40px;
      text-transform: uppercase;
      letter-spacing: 0.4px;
    }
    .status-upcoming  { background: #d4e8fc; color: #1a3c6e; }
    .status-ongoing   { background: #e8f0fe; color: #2a5c9e; }
    .status-completed { background: #e8f4fd; color: #5a7fa8; }
    .status-cancelled { background: #dce8f5; color: #0f2a4e; }

    .event-card h3 {
      font-size: 1.15rem;
      font-weight: 800;
      color: var(--gray-900);
      margin: 0.85rem 0 0.5rem;
      letter-spacing: -0.3px;
      line-height: 1.35;
    }
    .event-card p {
      color: var(--gray-500);
      font-size: 0.9rem;
      line-height: 1.6;
      margin-bottom: 0.75rem;
    }
    .event-card .event-meta {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      color: var(--gray-500);
      font-size: 0.82rem;
      margin-top: 0.4rem;
      font-weight: 500;
    }
    .event-card .event-meta i { color: var(--sky); width: 16px; text-align: center; }

    .empty-state {
      text-align: center;
      padding: 5rem 1rem;
      color: var(--gray-400);
      background: white;
      border-radius: 20px;
      border: 1px solid var(--gray-100);
    }
    .empty-state i {
      font-size: 4rem;
      color: var(--sky);
      opacity: 0.3;
      margin-bottom: 1rem;
    }

    .footer-link { transition: color 0.3s; }
    .footer-link:hover { color: var(--sky); }

    @media (max-width: 768px) {
      .hero { padding: 130px 0 70px; }
      .hero h1 { font-size: 2.75rem; letter-spacing: -1.5px; }
      .hero p { font-size: 1rem; }
      .hero-stat-value { font-size: 2rem; }
      .hero-stats { gap: 1.5rem 2rem; }
      .month-header h2 { font-size: 1.35rem; }
      .event-card { padding: 1.5rem; }
    }
  </style>
</head>
<body>

<!-- ===== TOP NAV ===== -->
<header class="glass-nav fixed top-0 left-0 right-0 z-50 text-white py-3">
  <div class="max-w-7xl mx-auto px-4 flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-3">
      <a href="index.html">
        <img src="../assets/bcp-logo.png" alt="BCP Logo" class="h-11 w-auto object-contain" />
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
      <a href="events.php" class="nav-link px-1 py-1 text-bcp-sky">Events</a>
      <a href="contact.php" class="nav-link px-1 py-1 text-gray-300">Contact</a>
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

  <div class="max-w-4xl mx-auto px-4 relative z-10">
    <div class="hero-badge">What's Happening</div>
    <h1>Events <span>Calendar</span></h1>
    <p>
      Festivals, research expos, sports, and academic activities
      happening across Bestlink College of the Philippines.
    </p>

    <div class="hero-stats">
      <div>
        <div class="hero-stat-value"><?php echo (int) $totalEvents; ?></div>
        <div class="hero-stat-label">Total Events</div>
      </div>
      <div>
        <div class="hero-stat-value"><?php echo (int) $upcomingCount; ?></div>
        <div class="hero-stat-label">Upcoming</div>
      </div>
      <div>
        <div class="hero-stat-value"><?php echo (int) $ongoingCount; ?></div>
        <div class="hero-stat-label">Ongoing</div>
      </div>
      <div>
        <div class="hero-stat-value"><?php echo (int) $completedCount; ?></div>
        <div class="hero-stat-label">Completed</div>
      </div>
    </div>
  </div>
</section>

<!-- ===== EVENTS BODY ===== -->
<section class="events-body">
  <div class="max-w-7xl mx-auto px-4">

    <?php if (isset($fatalMessage)): ?>
      <div class="empty-state">
        <i class="fas fa-exclamation-triangle"></i>
        <h3 style="font-size:1.25rem;font-weight:700;color:#0f172a;margin-bottom:0.5rem;">System Temporarily Unavailable</h3>
        <p style="font-size:0.9rem;"><?php echo $e($fatalMessage); ?></p>
      </div>
    <?php elseif ($repository === null || $repository->isEmpty()): ?>
      <div class="empty-state">
        <i class="fas fa-calendar-times"></i>
        <h3 style="font-size:1.25rem;font-weight:700;color:#0f172a;margin-bottom:0.5rem;">No events scheduled</h3>
        <p style="font-size:0.9rem;">Check back soon for upcoming activities.</p>
      </div>
    <?php else: ?>
      <?php foreach ($eventsByMonth as $monthName => $monthEvents): ?>
        <div style="margin-bottom:4rem;">
          <div class="month-header">
            <h2><?php echo $e($monthName); ?></h2>
            <span class="event-count">
              <?php echo count($monthEvents); ?> event<?php echo count($monthEvents) !== 1 ? 's' : ''; ?>
            </span>
          </div>

          <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:1.5rem;">
            <?php foreach ($monthEvents as $event): ?>
              <div class="event-card">
                <div style="display:flex;flex-wrap:wrap;align-items:center;gap:0.6rem;margin-bottom:0.85rem;">
                  <span class="event-tag <?php echo $e($event->tagClass()); ?>">
                    <i class="fas <?php echo $e($event->icon()); ?>"></i>
                    <?php echo $e($event->eventType); ?>
                  </span>
                  <span class="event-date-badge">
                    <i class="far fa-calendar-alt"></i>
                    <?php echo $e($event->formattedDate()); ?>
                  </span>
                  <span class="status-pill <?php echo $e($event->statusClass()); ?>">
                    <?php echo $e($event->statusLabel()); ?>
                  </span>
                </div>

                <h3><?php echo $e($event->eventTitle); ?></h3>

                <?php if ($event->description !== ''): ?>
                  <p><?php echo $e($event->description); ?></p>
                <?php endif; ?>

                <?php if ($event->timeRange() !== ''): ?>
                  <div class="event-meta">
                    <i class="far fa-clock"></i>
                    <?php echo $e($event->timeRange()); ?>
                  </div>
                <?php endif; ?>

                <?php if ($event->location !== ''): ?>
                  <div class="event-meta">
                    <i class="fas fa-map-marker-alt"></i>
                    <?php echo $e($event->location); ?>
                  </div>
                <?php endif; ?>

                <?php if ($event->targetAudience !== ''): ?>
                  <div class="event-meta">
                    <i class="fas fa-users"></i>
                    <?php echo $e($event->targetAudience); ?>
                  </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

  </div>
</section>

<!-- ===== STATS SECTION ===== -->
<div style="background:#0a1e3d;color:white;padding:4rem 2rem;margin:2rem 1rem;border-radius:4rem 4rem 2rem 2rem;position:relative;overflow:hidden;">
  <div style="position:absolute;top:0;right:0;width:320px;height:320px;background:rgba(79,195,247,0.1);border-radius:50%;filter:blur(60px);"></div>
  <div style="position:absolute;bottom:0;left:0;width:320px;height:320px;background:rgba(41,182,246,0.1);border-radius:50%;filter:blur(60px);"></div>

  <div style="max-width:80rem;margin:0 auto;position:relative;z-index:10;display:flex;flex-wrap:wrap;justify-content:space-around;align-items:center;gap:2rem;text-align:center;">
    <div>
      <div style="font-size:3rem;font-weight:800;color:#4fc3f7;">45,000+</div>
      <div style="font-weight:300;opacity:0.8;margin-top:0.25rem;">Students</div>
      <div style="font-size:0.75rem;opacity:0.5;">Across all programs and campuses</div>
    </div>
    <div>
      <div style="font-size:3rem;font-weight:800;color:#4fc3f7;">2002</div>
      <div style="font-weight:300;opacity:0.8;margin-top:0.25rem;">Established</div>
      <div style="font-size:0.75rem;opacity:0.5;">Two decades of quality education</div>
    </div>
    <div>
      <div style="font-size:3rem;font-weight:800;color:#4fc3f7;">3</div>
      <div style="font-weight:300;opacity:0.8;margin-top:0.25rem;">Campuses</div>
      <div style="font-size:0.75rem;opacity:0.5;">Millionaire's Village, Main, Bulacan</div>
    </div>
    <div style="background:rgba(255,255,255,0.1);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,0.15);padding:1rem 2rem;border-radius:40px;display:flex;align-items:center;gap:1rem;">
      <img src="../assets/bcp-logo.png" alt="BCP" style="height:40px;width:auto;" />
      <span style="font-weight:700;letter-spacing:0.05em;font-size:1.1rem;">BCP</span>
      <span style="opacity:0.3;">|</span>
      <span style="font-weight:300;">PHILIPPINES</span>
    </div>
  </div>
</div>

<!-- ===== FOOTER ===== -->
<footer style="background:#0a1e3d;color:white;padding:3rem 0;">
  <div class="max-w-7xl mx-auto px-4">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
      <div>
        <div class="flex items-center gap-3 mb-4">
          <a href="index.html">
            <img src="../assets/bcp-logo.png" alt="Bestlink College of the Philippines logo" class="h-12 w-auto" />
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