<?php
require __DIR__ . '/includes/bootstrap.php';

if (current_user() !== null) {
    redirect('dashboard.php');
}

// Purge all accounts (training reset).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'purge') {
    $_SESSION['users'] = [];
    $_SESSION['attempts'] = [];
    flash_set('All subject files purged.', 'info');
    redirect('login.php');
}

$errors = [];
$lockSeconds = 0;
$oldUser = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldUser  = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $key      = user_key($oldUser);

    if ($oldUser === '' || $password === '') {
        $errors[] = 'Enter both Subject ID and passphrase.';
    } else {
        // Limit how many different names can be tracked, to keep the session small.
        if (!isset($_SESSION['attempts'][$key]) && count($_SESSION['attempts']) >= 50) {
            array_shift($_SESSION['attempts']);
        }
        $_SESSION['attempts'][$key] = $_SESSION['attempts'][$key] ?? ['failed' => 0, 'locked_until' => 0];
        $attempt = &$_SESSION['attempts'][$key];
        $user = null;                        // stays null if the Subject ID does not exist
        if (isset($_SESSION['users'][$key])) {
            $user = &$_SESSION['users'][$key];
        }

        if (lock_remaining($key) > 0) {
            // Locked: do not even check the password.
            $lockSeconds = lock_remaining($key);
            $errors[] = 'Account locked. Try again in __COUNTDOWN__ seconds.';
            if (is_array($user)) {
                log_event($user, 'LOCKED', 'Attempt blocked during lockout');
            }
        } else {
            // Always run password_verify, even for unknown users, so response time does not reveal who exists.
            $valid = password_verify($password, is_array($user) ? $user['hash'] : DUMMY_HASH) && is_array($user);

            if ($valid) {
                $attempt['failed'] = 0;
                $user['last_login'] = date('Y-m-d H:i:s');
                log_event($user, 'SUCCESS', 'Authenticated');
                $_SESSION['current_user'] = $key;
                session_regenerate_id(true);   // new session id after login (prevents session fixation)
                flash_set('Access granted. Welcome, ' . $user['username'] . '.');
                redirect('dashboard.php');
            }

            // Failed login. The message is identical for "unknown user" and "wrong password".
            $attempt['failed']++;
            if (is_array($user)) {
                log_event($user, 'FAILED', 'Bad passphrase (attempt ' . $attempt['failed'] . ' of ' . MAX_ATTEMPTS . ')');
            }
            if ($attempt['failed'] >= MAX_ATTEMPTS) {
                $attempt['locked_until'] = time() + LOCK_SECONDS;
                $attempt['failed'] = 0;
                if (is_array($user)) {
                    $user['lockouts']++;
                }
                $lockSeconds = LOCK_SECONDS;
                $errors[] = 'Too many failed attempts. Account locked for __COUNTDOWN__ seconds.';
            } else {
                $errors[] = 'Access denied: invalid Subject ID or passphrase.';
            }
        }
        unset($attempt, $user);
    }
}

$flash = flash_get();
$pageTitle = 'Access Terminal';
require __DIR__ . '/includes/header.php';
?>
<section class="panel">
    <h2 class="panel-title">[ ACCESS TERMINAL ]</h2>
    <p class="muted prompt">&gt; authenticate to open a case file<span class="cursor"></span></p>

    <?php if ($flash): ?>
        <div id="flash-message" class="alert alert-<?= h($flash['type']) ?>" role="status"><?= h($flash['message']) ?></div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div id="error-summary" class="alert alert-error" role="alert">
            <ul>
                <?php foreach ($errors as $msg): ?>
                    <li class="error-item"><?= str_replace('__COUNTDOWN__', '<span id="countdown" data-seconds="' . (int)$lockSeconds . '">' . (int)$lockSeconds . '</span>', h($msg)) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form id="login-form" method="post" action="login.php" novalidate autocomplete="off">
        <div class="field">
            <label for="username">SUBJECT ID</label>
            <input type="text" id="username" name="username" value="<?= h($oldUser) ?>" autocomplete="off">
        </div>
        <div class="field">
            <label for="password">PASSPHRASE</label>
            <input type="password" id="password" name="password" autocomplete="off">
        </div>
        <button type="submit" id="submit-btn" class="btn">Authenticate</button>
        <a href="register.php" id="enroll-link" class="btn btn-ghost">Enroll new subject</a>
    </form>
</section>

<section class="panel panel-quiet">
    <form method="post" action="login.php" data-confirm="Purge ALL subject files from this lab?">
        <input type="hidden" name="action" value="purge">
        <button type="submit" id="purge-btn" class="btn btn-danger btn-small">Purge all subject files</button>
        <span class="muted"> training reset &mdash; removes every account in this session</span>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
