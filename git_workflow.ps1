# Creates a Git history for the assignment: several meaningful commits,
# a feature branch with a change, and a merge back into main.
#
# Usage (Windows PowerShell, from inside the project folder):
#   powershell -ExecutionPolicy Bypass -File .\git_workflow.ps1 https://github.com/<your-username>/<your-repo>.git
#
# Create the EMPTY repository on github.com first (no README, no .gitignore), then pass its URL.
# Without a URL the script still builds the local history; add the remote and push later.

param([string]$RemoteUrl = "")

$ErrorActionPreference = "Stop"

function Run-Git {
    git @args
    if ($LASTEXITCODE -ne 0) { throw "git $($args -join ' ') failed" }
}

if (Test-Path .git) {
    Write-Error "A .git folder already exists here. Delete it first (Remove-Item -Recurse -Force .git) to start clean."
}

if (-not (git config user.name) -or -not (git config user.email)) {
    Write-Host 'Set your identity first:'
    Write-Host '  git config --global user.name "Your Name"'
    Write-Host '  git config --global user.email "you@example.com"'
    exit 1
}

Write-Host "== 1. Initialise repository =="
Run-Git init -b main

Write-Host "== 2. Commits on main =="
Run-Git add .gitignore includes/ assets/style.css
Run-Git commit -m "Add shared bootstrap, password rules, blocklist and cyber-lab stylesheet"

Run-Git add index.php assets/script.js
Run-Git commit -m "Add entry point and client-side script (strength meter, countdown)"

Run-Git add register.php login.php dashboard.php
Run-Git commit -m "Add enrollment, login with lockout and dashboard with hash inspector"

Run-Git add tests/test_forensics.py requirements.txt
Run-Git commit -m "Add Selenium functional tests (17 test cases)"

Run-Git add Dockerfile .dockerignore README.md git_workflow.sh git_workflow.ps1
Run-Git commit -m "Add Dockerfile, README and Git workflow scripts"

Write-Host "== 3. Feature branch with an application change =="
Run-Git checkout -b feature/footer-php-version

$footer = @'
</main>
<footer class="site-footer">
    <p>ITC402 Major Assignment &mdash; training lab. Do not enter real passwords.</p>
    <p>Running on PHP <?= h(PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION) ?></p>
</footer>
<script src="assets/script.js"></script>
</body>
</html>
'@
Set-Content -Path includes/footer.php -Value $footer -Encoding UTF8
Run-Git add includes/footer.php
Run-Git commit -m "Show PHP version in the page footer"

Write-Host "== 4. Merge branch into main =="
Run-Git checkout main
Run-Git merge --no-ff feature/footer-php-version -m "Merge branch 'feature/footer-php-version' into main"

Write-Host "== 5. History =="
Run-Git log --oneline --graph --decorate

if ($RemoteUrl -ne "") {
    Write-Host "== 6. Push to GitHub =="
    Run-Git remote add origin $RemoteUrl
    Run-Git push -u origin main
    Run-Git push origin feature/footer-php-version
    Write-Host "Pushed. Open $RemoteUrl to check the commits, the branch and the merge."
} else {
    Write-Host ""
    Write-Host "No remote URL given. To publish later:"
    Write-Host "  git remote add origin https://github.com/<your-username>/<your-repo>.git"
    Write-Host "  git push -u origin main"
    Write-Host "  git push origin feature/footer-php-version"
}
