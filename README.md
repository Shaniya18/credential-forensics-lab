# Credential Forensics Lab

A small PHP web application that teaches how passwords are checked, stored and protected.
It has a "case file" interface, a live password-strength meter, brute-force lockout and a hash inspector.
Built for the **ITC402 Major Assignment**, with automated Selenium tests (Python) and Docker support.

> Training lab only: never enter a real password.

## Features

- 3 interconnected PHP pages: **Access Terminal** (`login.php`), **Enroll Subject** (`register.php`), **Dashboard** (`dashboard.php`)
- **Live strength meter** (JavaScript) with a rule checklist; the same rules are enforced again by PHP on the server
- **Breach blocklist** of common passwords, including simple "leet speak" variants (`P@ssw0rd!` is caught)
- Password may not contain the username; confirmation must match; length 10-64
- **Passwords are hashed** with `password_hash()` (bcrypt, random salt) and checked with `password_verify()`; recovery answers are hashed too
- **Lockout** after 3 failed logins for 30 seconds, with a live countdown; the right password is refused while locked
- Identical error message for "wrong password" and "unknown user" (no user enumeration); a dummy hash keeps response time similar
- `session_regenerate_id()` after login and logout (prevents session fixation), HttpOnly + SameSite cookie, basic security headers
- **Dashboard:** account status, threat level, strength score, evidence log of every login event, **Hash Inspector** and **Hash Lab**
- Data is stored in PHP sessions (no database)

## Project structure

```
index.php            Redirects to login or dashboard
login.php            Access terminal: login, lockout logic, purge action
register.php         Enrollment form + server-side validation
dashboard.php        Case file, evidence log, hash inspector, hash lab, logout
includes/            bootstrap.php (session, rules, helpers), header, footer, blocklist
assets/              style.css, script.js (meter, countdown, matrix background)
tests/               Selenium test script (Python)
Dockerfile           Container definition (php:8.3-apache)
git_workflow.sh      Creates the commit / branch / merge history (Git Bash, Mac, Linux)
git_workflow.ps1     Same thing for Windows PowerShell
```

## Run locally

Requirements: PHP 8.1 or newer.

```bash
php -S localhost:8000
```

Open <http://localhost:8000>. With XAMPP, copy the folder into `htdocs` and open `http://localhost/credential-forensics-lab/`.

## Run with Docker

```bash
docker build -t credential-forensics-lab .
docker run -d --name forensics-app -p 8080:80 credential-forensics-lab
```

Open <http://localhost:8080>. Stop and remove it with `docker stop forensics-app && docker rm forensics-app`.

## Run the Selenium tests

Requirements: Python 3.9+ and Google Chrome (Selenium 4 downloads a matching driver automatically).

```bash
pip install -r requirements.txt
python tests/test_forensics.py
```

Point the tests at another address with `BASE_URL` (no trailing slash):

```powershell
$env:BASE_URL="http://localhost:8080"                         # Docker container
$env:BASE_URL="http://localhost/credential-forensics-lab"     # XAMPP
$env:SLOW="0.7"                                               # slow down so you can watch (demo)
python tests\test_forensics.py
```

Results are printed and saved to `tests/test_results.txt`.

### Test cases

| # | Test | Type |
|---|------|------|
| 1 | Login page loads with its form fields | Success |
| 2 | Navigation between pages, index redirect | Success |
| 3 | Dashboard refuses visitors who are not logged in | Failure |
| 4 | Valid enrollment accepted | Success |
| 5 | Empty enrollment form lists every missing item | Failure |
| 6 | Common password rejected, including leet-speak | Failure |
| 7 | Password containing the username rejected | Failure |
| 8 | Confirmation mismatch rejected | Failure |
| 9 | Duplicate Subject ID rejected | Failure |
| 10 | Live strength meter and rule checklist update while typing | Success |
| 11 | Successful login shows the case file | Success |
| 12 | Wrong password and unknown user give the same message | Failure |
| 13 | Lockout after 3 failures, countdown ticks, correct password refused | Failure |
| 14 | Hash inspector shows a 60-character bcrypt hash, not the password | Success |
| 15 | Hash lab: different bcrypt salts, identical SHA-256 | Success |
| 16 | Logout protects the dashboard again | Success |
| 17 | Purge with confirm dialog (cancel, then accept) | Success |

## Known limits (it is a teaching demo)

- Accounts live in the PHP session, so they disappear when the session ends; a real system would use a database.
- Lockout is tracked per session; a real system would track it server-wide and per IP.
- There is no CSRF token and no HTTPS here; both are required in production.
