<p align="center">
  <img src="docs/banner.svg" alt="Credential Forensics Lab: falling green code over a glowing title" width="100%">
</p>

<p align="center">
  <b>A hands-on lab that shows how a website should check, store and protect passwords.</b>
</p>

<p align="center">
  <a href="YOUR_RENDER_URL_HERE">
    <img src="https://img.shields.io/badge/🚀_Live_Demo-Online-39ff88?style=flat-square&labelColor=04080b" alt="Live Demo">
  </a>
  <img src="https://img.shields.io/badge/PHP-8.1%2B-39ff88?style=flat-square&labelColor=04080b&logo=php&logoColor=39ff88" alt="PHP 8.1+">
  <img src="https://img.shields.io/badge/Selenium-17%20tests-39ff88?style=flat-square&labelColor=04080b&logo=selenium&logoColor=39ff88" alt="Selenium 17 tests">
  <img src="https://img.shields.io/badge/Docker-ready-39ff88?style=flat-square&labelColor=04080b&logo=docker&logoColor=39ff88" alt="Docker ready">
  <img src="https://img.shields.io/badge/passwords-bcrypt-39ff88?style=flat-square&labelColor=04080b" alt="bcrypt">
  <img src="https://img.shields.io/badge/ITC402-Major%20Assignment-39ff88?style=flat-square&labelColor=04080b" alt="ITC402 Major Assignment">
</p>

<p align="center">
  <a href="#mission">Mission</a> &nbsp;|&nbsp;
  <a href="#screenshots">Screenshots</a> &nbsp;|&nbsp;
  <a href="#features">Features</a> &nbsp;|&nbsp;
  <a href="#quick-start">Quick start</a> &nbsp;|&nbsp;
  <a href="#tests">Tests</a> &nbsp;|&nbsp;
  <a href="#project-files">Files</a> &nbsp;|&nbsp;
  <a href="#known-limits">Limits</a>
</p>

```text
> training lab  |  no database (PHP sessions only)  |  never enter a real password

<img src="docs/rain.svg" alt="" width="100%">
Mission
Most people only see the login box. This lab opens up everything behind it: create an account, break the rules on purpose, get locked out, then inspect what the system actually stored about you.

You do this	The lab shows you this
Type a password	A live strength meter, then the same rules re-checked by PHP
Try P@ssw0rd!2024	The breach blocklist sees through the "leet speak" and rejects it
Fail to log in 3 times	A 30-second lockout with a ticking countdown
Log in successfully	A dashboard with threat level, score and evidence log
Open the Hash Inspector	The 60-character bcrypt hash split into algorithm, cost, salt and digest
Hash hello twice in the Hash Lab	Two different bcrypt hashes, but identical SHA-256
Screenshots
<table> <tr> <td width="33%" valign="top"><a href="docs/screenshot-signup.png"><img src="docs/screenshot-signup.png" alt="Sign-up page with live strength meter"></a></td> <td width="33%" valign="top"><a href="docs/screenshot-login.png"><img src="docs/screenshot-login.png" alt="Log in page"></a></td> <td width="33%" valign="top"><a href="docs/screenshot-dashboard.png"><img src="docs/screenshot-dashboard.png" alt="Dashboard with evidence log and hash inspector"></a></td> </tr> <tr> <td align="center"><sub><b>Sign Up</b><br>live strength meter</sub></td> <td align="center"><sub><b>Log In</b><br>lockout + countdown</sub></td> <td align="center"><sub><b>Dashboard</b><br>evidence log + hashes</sub></td> </tr> </table><img src="docs/rain.svg" alt="" width="100%">
Features
Area	What it does
Pages	login.php (Log In), register.php (Sign Up), dashboard.php (login required)
Password rules	10-64 characters with upper, lower, digit and symbol; must not contain the username; confirmation must match
Breach blocklist	Rejects common passwords, even with substitutions (@=a, 0=o, $=s)
Live meter	JavaScript strength score and checklist while you type (PHP re-checks everything)
Hashing	password_hash() (bcrypt, random salt) and password_verify(); recovery answers are hashed too
Lockout	3 failed logins lock the account for 30 seconds; the right password is refused while locked
No user enumeration	"Wrong password" and "unknown user" give the same message; a dummy hash evens out response time
Session hardening	New session id after login and logout, HttpOnly + SameSite cookies, security headers
Dashboard	Account details, strength score, threat level, evidence log, Hash Inspector, Hash Lab
CI/CD Deployment	Fully containerized and auto-deployed via Render connected to GitHub.
<details> <summary><b>How a login is decided</b> (flow diagram)</summary>

flowchart TD
    A[Submit username + password] --> B{Locked out?}
    B -- yes --> C[Refuse, log LOCKED, show countdown]
    B -- no --> D[password_verify against the stored hash, or a dummy hash if the user is unknown]
    D -- match --> E[New session id, log SUCCESS, open dashboard]
    D -- no match --> F[Add a failure, log FAILED]
    F --> G{3 failures?}
    G -- yes --> H[Lock for 30 seconds]
    G -- no --> I[Same generic error message]
