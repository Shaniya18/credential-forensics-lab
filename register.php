<?php
require __DIR__ . '/includes/bootstrap.php';

if (current_user() !== null) {
    redirect('dashboard.php');
}

$errors = [];
$old = ['username' => '', 'question' => '', 'answer' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ---- 1. Read input (passwords are never echoed back into the form) ----
    $old['username'] = trim($_POST['username'] ?? '');
    $old['question'] = $_POST['security_question'] ?? '';
    $old['answer']   = trim($_POST['security_answer'] ?? '');
    $password        = $_POST['password'] ?? '';
    $confirm         = $_POST['confirm_password'] ?? '';
    $ack             = isset($_POST['ack']);

    // ---- 2. Validate on the server ----
    if ($old['username'] === '') {
        $errors[] = 'Subject ID (username) is required.';
    } elseif (!preg_match('/^[A-Za-z][A-Za-z0-9_]{3,19}$/', $old['username'])) {
        $errors[] = 'Subject ID must be 4-20 characters: start with a letter, then letters, digits or underscores.';
    } elseif (isset($_SESSION['users'][strtolower($old['username'])])) {
        $errors[] = 'That Subject ID is already enrolled.';
    }

    if (!isset(QUESTIONS[$old['question']])) {
        $errors[] = 'Please choose a recovery question.';
    }
    if ($old['answer'] === '') {
        $errors[] = 'Recovery answer is required.';
    } elseif (strlen($old['answer']) < 3 || strlen($old['answer']) > 40) {
        $errors[] = 'Recovery answer must be 3-40 characters.';
    }

    foreach (password_errors($password, $confirm, $old['username']) as $msg) {
        $errors[] = $msg;
    }

    if (!$ack) {
        $errors[] = 'You must acknowledge the training-lab notice.';
    }

    // ---- 3. Save (only hashes are stored, never the plain password) ----
    if (!$errors) {
        $key = strtolower($old['username']);
        $score = password_score($password, $old['username']);
        $user = [
            'username'    => $old['username'],
            'hash'        => password_hash($password, PASSWORD_DEFAULT),
            'question'    => $old['question'],
            'answer_hash' => password_hash(strtolower($old['answer']), PASSWORD_DEFAULT),
            'created'     => date('Y-m-d H:i:s'),
            'last_login'  => null,
            'score'       => $score['score'],
            'label'       => $score['label'],
            'lockouts'    => 0,
            'history'     => [],
        ];
        log_event($user, 'ENROLLED', 'Credentials registered');
        $_SESSION['users'][$key] = $user;
        flash_set('Subject ' . $old['username'] . ' enrolled. Authenticate to open the case file.');
        redirect('login.php');
    }
}

$pageTitle = 'Enroll Subject';
require __DIR__ . '/includes/header.php';
?>
<section class="panel">
    <h2 class="panel-title">[ ENROLL SUBJECT ]</h2>

    <?php if ($errors): ?>
        <div id="error-summary" class="alert alert-error" role="alert">
            <strong>Enrollment rejected:</strong>
            <ul>
                <?php foreach ($errors as $msg): ?><li class="error-item"><?= h($msg) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- novalidate: validation is done by PHP on the server so it can be tested end to end -->
    <form id="register-form" method="post" action="register.php" novalidate autocomplete="off">
        <div class="field">
            <label for="username">SUBJECT ID</label>
            <input type="text" id="username" name="username" value="<?= h($old['username']) ?>" autocomplete="off">
        </div>

        <div class="field">
            <label for="password">PASSPHRASE</label>
            <div class="pw-row">
                <input type="password" id="password" name="password" autocomplete="new-password">
                <button type="button" id="toggle-pw" class="btn btn-small btn-ghost">Show</button>
            </div>

            <div class="meter" aria-live="polite">
                <div class="meter-track"><div id="meter-bar" class="meter-bar" style="width:0%"></div></div>
                <div class="meter-text">STRENGTH: <b id="meter-label">&mdash;</b> <span class="muted">(<span id="meter-score">0</span>/100)</span></div>
            </div>
            <ul class="rules" id="rules">
                <li id="rule-length">at least <?= PW_MIN ?> characters</li>
                <li id="rule-upper">uppercase letter</li>
                <li id="rule-lower">lowercase letter</li>
                <li id="rule-digit">digit</li>
                <li id="rule-symbol">symbol</li>
                <li id="rule-user">does not contain the Subject ID</li>
                <li id="rule-common">not on the breach blocklist</li>
                <li id="rule-match">confirmation matches</li>
            </ul>
        </div>

        <div class="field">
            <label for="confirm_password">CONFIRM PASSPHRASE</label>
            <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password">
        </div>

        <div class="field">
            <label for="security_question">RECOVERY QUESTION</label>
            <select id="security_question" name="security_question">
                <option value="">-- select a question --</option>
                <?php foreach (QUESTIONS as $k => $q): ?>
                    <option value="<?= h($k) ?>" <?= $old['question'] === $k ? 'selected' : '' ?>><?= h($q) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label for="security_answer">RECOVERY ANSWER</label>
            <input type="text" id="security_answer" name="security_answer" value="<?= h($old['answer']) ?>" autocomplete="off">
        </div>

        <div class="field">
            <label class="check">
                <input type="checkbox" id="ack" name="ack" value="yes">
                I understand this is a training lab and I will not use a real password.
            </label>
        </div>

        <button type="submit" id="submit-btn" class="btn">Enroll subject</button>
        <a href="login.php" id="cancel-link" class="btn btn-ghost">Back to terminal</a>
    </form>
</section>

<script>
    // Data for the live strength meter (the server remains the authority).
    window.PW_CONFIG = { min: <?= PW_MIN ?>, common: <?= json_encode(common_passwords()) ?> };
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
