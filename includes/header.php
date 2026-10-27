<?php
// HTML header + navigation. Pages must require bootstrap.php first.
$pageTitle  = $pageTitle ?? 'Terminal';
$page       = basename($_SERVER['PHP_SELF']);
$loggedIn   = isset($_SESSION['current_user']) && isset($_SESSION['users'][$_SESSION['current_user']]);
$caseId     = strtoupper(substr(md5(session_id()), 0, 6));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle) ?> | Credential Forensics Lab</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="topline">
    <span>CASE <b>#<?= h($caseId) ?></b></span>
    <span>CLEARANCE: <b>TRAINING</b></span>
    <span class="blink-dot">TRACE ACTIVE</span>
</div>
<header class="site-header">
    <h1 class="logo">CREDENTIAL FORENSICS LAB</h1>
    <nav>
        <?php if ($loggedIn): ?>
            <a id="nav-dashboard" href="dashboard.php" class="<?= $page === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
        <?php else: ?>
            <a id="nav-login" href="login.php" class="<?= $page === 'login.php' ? 'active' : '' ?>">Access Terminal</a>
            <a id="nav-register" href="register.php" class="<?= $page === 'register.php' ? 'active' : '' ?>">Enroll Subject</a>
        <?php endif; ?>
    </nav>
</header>
<main>
