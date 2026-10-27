<?php
// Shared setup: session, security headers, constants and helper functions.
// Every page requires this file BEFORE producing any output, so redirects always work.

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

const MAX_ATTEMPTS = 3;      // failed logins before a lockout
const LOCK_SECONDS = 30;     // lockout length (short so it can be demonstrated)
const PW_MIN = 10;
const PW_MAX = 64;           // bcrypt only uses the first 72 bytes
// Hash of a throw-away password. Verifying against it when a username does not exist
// makes "unknown user" take about as long as "wrong password" (timing side-channel defence).
const DUMMY_HASH = '$2y$10$9AnecDh/rv0nHdLnSe6qreyEzOT8Yyuh9znCs4tpmBbBaajGsrfXK';

const QUESTIONS = [
    'q1' => 'What was the name of your first school?',
    'q2' => "What was your first pet's name?",
    'q3' => 'In which town were you born?',
    'q4' => "What is your favourite teacher's surname?",
    'q5' => 'What was your first phone model?',
];

$_SESSION['users']    = $_SESSION['users']    ?? [];   // username (lowercase) => account record
$_SESSION['attempts'] = $_SESSION['attempts'] ?? [];   // username (lowercase) => failed count + lock time

// ---------------------------------------------------------------- output helpers
function h(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function flash_set(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

// ---------------------------------------------------------------- accounts
function user_key(string $username): string
{
    $k = strtolower(trim($username));
    return preg_match('/^[a-z0-9_]{1,40}$/', $k) ? $k : '_invalid_';
}

function &current_user(): ?array
{
    static $none = null;
    $key = $_SESSION['current_user'] ?? null;
    if ($key !== null && isset($_SESSION['users'][$key])) {
        return $_SESSION['users'][$key];
    }
    return $none;
}

function log_event(array &$user, string $result, string $note = ''): void
{
    $user['history'][] = [
        'time'   => date('Y-m-d H:i:s'),
        'result' => $result,
        'ip'     => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'note'   => $note,
    ];
}

function lock_remaining(string $key): int
{
    $until = $_SESSION['attempts'][$key]['locked_until'] ?? 0;
    return max(0, $until - time());
}

// ---------------------------------------------------------------- password rules
function common_passwords(): array
{
    static $list = null;
    return $list ??= require __DIR__ . '/common_passwords.php';
}

function is_common_password(string $pw): bool
{
    $lower = strtolower($pw);
    // Undo simple "leet speak" so P@ssw0rd is still recognised as "password".
    $leetL = strtr($lower, ['@' => 'a', '4' => 'a', '0' => 'o', '1' => 'l', '3' => 'e', '$' => 's', '5' => 's', '!' => 'i', '7' => 't']);
    $leetI = strtr($lower, ['@' => 'a', '4' => 'a', '0' => 'o', '1' => 'i', '3' => 'e', '$' => 's', '5' => 's', '!' => 'i', '7' => 't']);
    foreach (common_passwords() as $word) {
        foreach ([$lower, $leetL, $leetI] as $candidate) {
            if (strlen($word) >= 5 ? str_contains($candidate, $word) : $candidate === $word) {
                return true;
            }
        }
    }
    return false;
}

/**
 * Strength score 0-100. The same algorithm is implemented in assets/script.js for the live meter;
 * PHP is always the authority.
 */
function password_score(string $pw, string $username = ''): array
{
    $len = strlen($pw);
    if ($len === 0) {
        return ['score' => 0, 'label' => 'Empty'];
    }
    $s = min($len, 20) * 3;
    if (preg_match('/[A-Z]/', $pw)) $s += 5;
    if (preg_match('/[a-z]/', $pw)) $s += 5;
    if (preg_match('/[0-9]/', $pw)) $s += 5;
    if (preg_match('/[^A-Za-z0-9]/', $pw)) $s += 10;
    if ($len >= 16) $s += 15;
    if (strlen($username) >= 3 && stripos($pw, $username) !== false) $s -= 30;
    if (preg_match('/(.)\1\1/', $pw)) $s -= 10;
    if (is_common_password($pw)) $s = min($s, 15);
    $s = max(0, min(100, $s));
    $label = $s < 40 ? 'Weak' : ($s < 60 ? 'Fair' : ($s < 80 ? 'Strong' : 'Excellent'));
    return ['score' => $s, 'label' => $label];
}

/** Returns a list of human-readable problems (empty list = password accepted). */
function password_errors(string $pw, string $confirm, string $username): array
{
    $e = [];
    if ($pw === '') {
        return ['Password is required.'];
    }
    if (!preg_match('/^[\x20-\x7E]+$/', $pw)) {
        $e[] = 'Password may only contain standard keyboard characters.';
    }
    if (strlen($pw) < PW_MIN) $e[] = 'Password must be at least ' . PW_MIN . ' characters.';
    if (strlen($pw) > PW_MAX) $e[] = 'Password must be at most ' . PW_MAX . ' characters.';
    if (!preg_match('/[A-Z]/', $pw)) $e[] = 'Password needs an uppercase letter.';
    if (!preg_match('/[a-z]/', $pw)) $e[] = 'Password needs a lowercase letter.';
    if (!preg_match('/[0-9]/', $pw)) $e[] = 'Password needs a digit.';
    if (!preg_match('/[^A-Za-z0-9]/', $pw)) $e[] = 'Password needs a symbol (e.g. ! # $ %).';
    if (strlen($username) >= 3 && stripos($pw, $username) !== false) $e[] = 'Password must not contain your username.';
    if (is_common_password($pw)) $e[] = 'Password is too common and appears on the breach blocklist.';
    if ($pw !== $confirm) $e[] = 'Passwords do not match.';
    return $e;
}

function split_bcrypt(string $hash): array
{
    $info = password_get_info($hash);
    return [
        'algo'   => $info['algoName'],                 // "bcrypt"
        'cost'   => $info['options']['cost'] ?? 0,     // work factor
        'prefix' => substr($hash, 0, 4),               // $2y$
        'costs'  => substr($hash, 4, 3),               // 10$
        'salt'   => substr($hash, 7, 22),              // random salt
        'digest' => substr($hash, 29),                 // the actual hash
    ];
}
