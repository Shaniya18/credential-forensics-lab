Here is the complete README code in a single copy-paste block. Just highlight everything inside the box, copy it, and paste it directly into your `README.md` file.

```markdown
# 🕵️‍♂️ CREDENTIAL FORENSICS LAB

> **A cyber-security training simulator demonstrating password hashing, brute-force protection, and the boundary between client-side and server-side validation.**

[![PHP Version](https://img.shields.io/badge/PHP-8.3-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![Docker](https://img.shields.io/badge/Docker-Enabled-2496ED?style=flat-square&logo=docker&logoColor=white)](https://docker.com)
[![Selenium](https://img.shields.io/badge/Selenium-Tested-43B02A?style=flat-square&logo=selenium&logoColor=white)](https://selenium.dev)
[![Assignment](https://img.shields.io/badge/ITC402-Major_Assignment-red?style=flat-square)](#)

---

### 🛑 TRAINING LAB ONLY
**Never enter a real password.** This application runs entirely in PHP `$_SESSION` memory. There is no database, no tracking, and no data persistence. Close the browser, and the "case file" is gone forever.

---

## ⚡ The Feature Set

### 🔐 Core Authentication
*   **3 Interconnected Pages:** Access Terminal (`login.php`), Enroll Subject (`register.php`), and the Case File Dashboard (`dashboard.php`).
*   **Server-Side Authority:** Browsers lie. Every rule you see in the UI is re-validated in PHP before an account is created or accessed.
*   **No User Enumeration:** "Wrong password" and "Unknown user" give the exact same error message and response time (thanks to a dummy hash).
*   **Session Hardening:** `session_regenerate_id()` prevents fixation attacks. Cookies are `HttpOnly` and `SameSite=Lax`.

### 🧠 The Forensics Dashboard
*   **Live Threat Level:** Goes from `LOW` → `MEDIUM` → `HIGH` based on failed login attempts and lockouts.
*   **Evidence Log:** A timestamped audit trail of every `ENROLLED`, `SUCCESS`, `FAILED`, and `LOCKED` event.
*   **Hash Inspector:** See exactly what the database would store—a 60-character `bcrypt` string. It breaks down the algorithm, cost factor, salt, and digest.
*   **Hash Lab:** Interactive playground. Hash `hello` twice. See the bcrypt salts differ. See SHA-256 stay identical.

### 🛡️ Brute-Force Defense
*   **Breach Blocklist:** Catches common passwords, including "leet speak" bypasses (e.g., `P@ssw0rd!2024` is un-leeted to `password` and blocked).
*   **Zero-Trust Username:** Passwords cannot contain the Subject ID (e.g., user `nia_ops` cannot use `Nia_Ops#2026!`).
*   **Hard Lockout:** 3 failed attempts = 30-second lockout. A live JavaScript countdown ticks down, but the server remains the ultimate authority.

---

## 🚀 Deployment & Setup

### Option A: Run Locally (PHP)
Requires PHP 8.1+.
```bash
php -S localhost:8000
```
Navigate to `http://localhost:8000`.

### Option B: Run in Docker (Containerized)
Requires Docker.
```bash
docker build -t credential-forensics-lab .
docker run -d --name forensics-app -p 8080:80 credential-forensics-lab
```
Navigate to `http://localhost:8080`.

### Option C: Run the Automated Tests
Requires Python 3.9+ and Google Chrome.
```bash
pip install -r requirements.txt
python tests/test_forensics.py
```

---

## 🧪 Automated Test Coverage (17 Cases)

The Selenium suite runs 17 functional tests covering success and failure paths across all pages.

| # | Test Scenario | Path |
|---|---------------|------|
| 1 | Login page loads with form fields | ✅ Success |
| 2 | Navigation and index redirect work | ✅ Success |
| 3 | Unauthenticated users blocked from Dashboard | ❌ Failure |
| 4 | Valid enrollment accepted | ✅ Success |
| 5 | Empty form lists every missing item | ❌ Failure |
| 6 | Blocklist catches common/leet passwords | ❌ Failure |
| 7 | Password containing username rejected | ❌ Failure |
| 8 | Confirmation mismatch rejected | ❌ Failure |
| 9 | Duplicate Subject ID rejected | ❌ Failure |
| 10 | Live meter and checklist update | ✅ Success |
| 11 | Successful login shows Case File | ✅ Success |
| 12 | No user enumeration on failure | ❌ Failure |
| 13 | Lockout triggers, countdown ticks | ❌ Failure |
| 14 | Inspector shows 60-char bcrypt hash | ✅ Success |
| 15 | Lab shows random salts vs SHA-256 | ✅ Success |
| 16 | Logout protects the Dashboard again | ✅ Success |
| 17 | Purge confirmation dialog works | ✅ Success |

---

## 📂 Architecture

```text
credential-forensics-lab/
├── index.php            # Router: redirects to login or dashboard
├── login.php            # Access Terminal: lockout, purge, verification
├── register.php         # Enrollment: server-side rules, hashing
├── dashboard.php        # Case File: log, hash inspector, lab
├── includes/
│   ├── bootstrap.php    # Session start, helpers, security constants
│   ├── header.php       # HTML top, nav, case ID generator
│   ├── footer.php       # HTML bottom, script loader
│   └── common_passwords.php # The breach blocklist array
├── assets/
│   ├── style.css        # Cyber-terminal aesthetic
│   └── script.js        # Meter, countdown, matrix rain, intervals
├── tests/
│   ├── test_forensics.py    # Selenium WebDriver suite
│   └── test_results.txt     # Latest execution log
├── Dockerfile           # php:8.3-apache container definition
└── git_workflow.ps1     # Automates commit/branch/merge history
```

---

## ⚠️ Known Limitations (Production vs. Demo)

Because this is a session-based training lab, it explicitly demonstrates *why* production systems use databases:
1.  **Data Loss:** Accounts vanish when the PHP session ends.
2.  **Lockout Scope:** Lockouts are per-session. A real app would track by username + IP in a database.
3.  **CSRF:** No CSRF tokens are implemented here; a real login form needs them.
4.  **HTTPS:** No TLS/SSL certificate is configured for local development.

---
*Built for the ITC402 Major Assignment. Secured by PHP, deployed by Docker, verified by Selenium.*
```
