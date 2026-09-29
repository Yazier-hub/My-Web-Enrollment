<?php
// index.php - Root entry point for LMS

// Redirect all traffic to the pages folder
header('Location: pages/index.html', true, 302);
exit();