<?php
// Expects $pageTitle and $activeNav to be set by the including page.
// $activeNav is one of: dashboard, profile, bmi, split, link
$pageTitle = $pageTitle ?? 'Dashboard';
$activeNav = $activeNav ?? 'dashboard';

$navItems = [
    'dashboard'  => ['label' => 'Dashboard',      'href' => '/mini-projectTEMP/fitCo/user/home/home.php'],
    'profile'    => ['label' => 'Profile',        'href' => '/mini-projectTEMP/fitCo/user/profile/index.php'],
    'membership' => ['label' => 'Membership',     'href' => '/mini-projectTEMP/fitCo/user/membership/index.php'],
    'payments'   => ['label' => 'Payments',       'href' => '/mini-projectTEMP/fitCo/user/payments/index.php'],
    'attendance' => ['label' => 'Attendance',     'href' => '/mini-projectTEMP/fitCo/user/attendance/index.php'],
    'trainer'    => ['label' => 'Trainer',        'href' => '/mini-projectTEMP/fitCo/user/trainer/index.php'],
    'workout'    => ['label' => 'Workout Plan',   'href' => '/mini-projectTEMP/fitCo/user/workout/index.php'],
    'bmi'        => ['label' => 'BMI Tracker',    'href' => '/mini-projectTEMP/fitCo/user/bmi/bmi.php'],
    'split'      => ['label' => 'Workout Split',  'href' => '/mini-projectTEMP/fitCo/user/split/split.php'],
];

// Only surface the "link your membership" nav item while there's
// nothing linked yet — once linked, the dashboard/profile cover it.
if (!$member) {
    $navItems['link'] = ['label' => 'Link Membership', 'href' => '/mini-projectTEMP/fitCo/user/link-membership/link.php'];
}

$flash = flash_get();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo h($pageTitle); ?> — FITCO</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/mini-projectTEMP/fitCo/user/includes/user.css">
</head>
<body>

  <div class="shell">

    <aside class="sidebar">
      <div class="brand">
        <span class="brand-mark" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M7 20 L7 4 L17 4"/>
            <path d="M7 12 L14 12"/>
          </svg>
        </span>
        <span class="brand-name">FITCO</span>
      </div>

      <nav class="nav">
        <?php foreach ($navItems as $key => $item): ?>
          <a class="nav-link <?php echo $activeNav === $key ? 'nav-link--active' : ''; ?>" href="<?php echo h($item['href']); ?>">
            <?php echo h($item['label']); ?>
          </a>
        <?php endforeach; ?>
      </nav>

      <div class="sidebar-foot">
        <div class="gym-chip">
          <span class="gym-chip-dot" aria-hidden="true"></span>
          <span class="gym-chip-name"><?php echo $gym ? h($gym['gym_name']) : 'No gym linked'; ?></span>
        </div>
        <a class="logout-link" href="?logout=1">Log out</a>
      </div>
    </aside>

    <div class="main-col">

      <header class="topbar">
        <div class="topbar-inner">
          <h1 class="topbar-title"><?php echo h($pageTitle); ?></h1>
          <span class="today"><?php echo date('D, j M · g:i A'); ?></span>
        </div>
      </header>

      <main class="page">

        <?php if ($flash): ?>
          <div class="flash flash--<?php echo h($flash['type']); ?> reveal"><?php echo h($flash['msg']); ?></div>
        <?php endif; ?>
