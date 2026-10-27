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
