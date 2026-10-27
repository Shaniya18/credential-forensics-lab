<?php
require __DIR__ . '/includes/bootstrap.php';

$user = &current_user();
if ($user === null) {
    flash_set('Access denied. Authenticate first.', 'error');
    redirect('login.php');
}
$key = $_SESSION['current_user'];
$labErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'logout') {
        unset($_SESSION['current_user']);
        session_regenerate_id(true);
        flash_set('Session terminated. Case file closed.', 'info');
        redirect('login.php');
    }

    if ($action === 'lab') {
        $text = $_POST['lab_input'] ?? '';
        if ($text === '') {
            $_SESSION['lab_error'] = 'Enter some text to hash.';
        } elseif (strlen($text) > 64) {
            $_SESSION['lab_error'] = 'Text must be 64 characters or fewer.';
        } else {
            $_SESSION['lab'] = [
                'input'  => $text,
                'bcrypt1' => password_hash($text, PASSWORD_BCRYPT),
                'bcrypt2' => password_hash($text, PASSWORD_BCRYPT),
                'sha256' => hash('sha256', $text),
            ];
        }
        redirect('dashboard.php#hash-lab');
    }
}

$flash = flash_get();
$lab = $_SESSION['lab'] ?? null;
$labError = $_SESSION['lab_error'] ?? null;
unset($_SESSION['lab'], $_SESSION['lab_error']);

// ---- Derived facts for the case file ----
$locked   = lock_remaining($key);
$attempts = $_SESSION['attempts'][$key]['failed'] ?? 0;
$failedLog = count(array_filter($user['history'], fn($e) => $e['result'] === 'FAILED'));
$threat = $user['lockouts'] > 0 ? 'HIGH' : ($failedLog > 0 ? 'MEDIUM' : 'LOW');
$threatClass = ['HIGH' => 'bad', 'MEDIUM' => 'warn', 'LOW' => 'ok'][$threat];
$scoreClass = ['Weak' => 'bad', 'Fair' => 'warn', 'Strong' => 'cyan', 'Excellent' => 'ok'][$user['label']] ?? 'warn';
$hash = split_bcrypt($user['hash']);

$pageTitle = 'Dashboard';
require __DIR__ . '/includes/header.php';
?>
<?php if ($flash): ?>
    <div id="flash-message" class="alert alert-<?= h($flash['type']) ?>" role="status"><?= h($flash['message']) ?></div>
<?php endif; ?>

<div class="grid-2">
    <section class="panel">
        <h2 class="panel-title">[ SUBJECT FILE ]</h2>
        <dl class="facts">
            <dt>SUBJECT ID</dt><dd id="acct-username"><?= h($user['username']) ?></dd>
            <dt>STATUS</dt><dd id="acct-status" class="<?= $locked ? 'bad' : 'ok' ?>"><?= $locked ? 'LOCKED' : 'ACTIVE' ?></dd>
            <dt>ENROLLED</dt><dd id="acct-created"><?= h($user['created']) ?></dd>
            <dt>LAST LOGIN</dt><dd id="acct-last-login"><?= h($user['last_login'] ?? 'never') ?></dd>
            <dt>FAILED STREAK</dt><dd id="acct-failed"><?= (int)$attempts ?></dd>
            <dt>LOCKOUTS</dt><dd id="acct-lockouts"><?= (int)$user['lockouts'] ?></dd>
            <dt>RECOVERY Q</dt><dd id="acct-question"><?= h(QUESTIONS[$user['question']] ?? '') ?></dd>
        </dl>
        <form method="post" action="dashboard.php">
            <input type="hidden" name="action" value="logout">
            <button type="submit" id="logout-btn" class="btn btn-ghost">Close case file (logout)</button>
        </form>
    </section>

    <section class="panel">
        <h2 class="panel-title">[ SECURITY ASSESSMENT ]</h2>
        <p class="muted">Credential strength recorded at enrollment</p>
        <div class="meter">
            <div class="meter-track"><div class="meter-bar <?= $scoreClass ?>" style="width:<?= (int)$user['score'] ?>%"></div></div>
            <div class="meter-text">SCORE: <b id="score-value"><?= (int)$user['score'] ?></b>/100 &mdash; <b id="score-label" class="<?= $scoreClass ?>"><?= h($user['label']) ?></b></div>
        </div>
        <p>THREAT LEVEL: <b id="threat-level" class="<?= $threatClass ?>"><?= $threat ?></b></p>
        <p class="muted small">LOW = no failed logins &middot; MEDIUM = failed logins seen &middot; HIGH = account has been locked out.</p>
    </section>
</div>

<section class="panel">
    <h2 class="panel-title">[ EVIDENCE LOG ]</h2>
    <table id="history-table">
        <thead><tr><th>TIME</th><th>EVENT</th><th>SOURCE IP</th><th>DETAIL</th></tr></thead>
        <tbody>
        <?php foreach (array_reverse($user['history']) as $e): ?>
            <tr class="log-row">
                <td><?= h($e['time']) ?></td>
                <td class="col-result"><span class="chip chip-<?= strtolower(h($e['result'])) ?>"><?= h($e['result']) ?></span></td>
                <td><?= h($e['ip']) ?></td>
                <td class="col-note"><?= h($e['note']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="panel">
    <h2 class="panel-title">[ HASH INSPECTOR ]</h2>
    <p class="muted">Your password is <b>not stored</b>. This is what the system keeps instead (bcrypt via <code>password_hash()</code>):</p>
    <p class="hash" id="hash-full"><span class="h-prefix"><?= h($hash['prefix']) ?></span><span class="h-cost"><?= h($hash['costs']) ?></span><span class="h-salt"><?= h($hash['salt']) ?></span><span class="h-digest"><?= h($hash['digest']) ?></span></p>
    <dl class="facts">
        <dt>ALGORITHM</dt><dd id="hash-algo"><?= h($hash['algo']) ?> (<?= h($hash['prefix']) ?>)</dd>
        <dt>COST FACTOR</dt><dd id="hash-cost"><?= (int)$hash['cost'] ?></dd>
        <dt>SALT (random)</dt><dd id="hash-salt" class="cyan"><?= h($hash['salt']) ?></dd>
        <dt>DIGEST</dt><dd id="hash-digest" class="ok"><?= h($hash['digest']) ?></dd>
    </dl>
    <p class="muted small">Hashing is one-way: there is no key to "decrypt" it. At login, <code>password_verify()</code> hashes what you typed with the same salt and compares the results.</p>
</section>

<section class="panel" id="hash-lab">
    <h2 class="panel-title">[ HASH LAB ]</h2>
    <p class="muted">Hash the same text twice. bcrypt adds a random salt, so the results differ. Plain SHA-256 does not, which is why it is unsuitable for passwords.</p>

    <?php if ($labError): ?>
        <div id="lab-error" class="alert alert-error" role="alert"><?= h($labError) ?></div>
    <?php endif; ?>

    <form id="lab-form" method="post" action="dashboard.php" novalidate autocomplete="off">
        <input type="hidden" name="action" value="lab">
        <div class="field">
            <label for="lab_input">TEXT TO HASH</label>
            <input type="text" id="lab_input" name="lab_input" autocomplete="off">
        </div>
        <button type="submit" id="lab-submit" class="btn">Run hash lab</button>
    </form>

    <?php if ($lab): ?>
        <div id="lab-result" class="lab-result">
            <p>INPUT: <b id="lab-input-echo"><?= h($lab['input']) ?></b></p>
            <p>bcrypt #1: <span class="hash" id="lab-bcrypt-1"><?= h($lab['bcrypt1']) ?></span></p>
            <p>bcrypt #2: <span class="hash" id="lab-bcrypt-2"><?= h($lab['bcrypt2']) ?></span></p>
            <p>SHA-256 (both runs): <span class="hash" id="lab-sha256"><?= h($lab['sha256']) ?></span></p>
            <p id="lab-verdict" class="ok">
                <?= $lab['bcrypt1'] !== $lab['bcrypt2'] ? 'bcrypt hashes DIFFER (random salt). ' : 'bcrypt hashes match. ' ?>
                SHA-256 is identical every time.
            </p>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
