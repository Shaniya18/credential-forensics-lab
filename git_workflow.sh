#!/usr/bin/env bash
# Creates a Git history for the assignment: several meaningful commits,
# a feature branch with a change, and a merge back into main.
#
# Usage (run from the project folder, in Git Bash / macOS / Linux terminal):
#   ./git_workflow.sh https://github.com/<your-username>/<your-repo>.git
#
# Create the EMPTY repository on github.com first (no README, no .gitignore), then pass its URL.
# Without a URL the script still builds the local history; add the remote and push later.
# Your own `git config user.name` / `user.email` are used for the commits.

set -euo pipefail

REMOTE_URL="${1:-}"

if [ -d .git ]; then
  echo "A .git folder already exists here. Delete it first (rm -rf .git) to start clean." >&2
  exit 1
fi

if ! git config user.name >/dev/null || ! git config user.email >/dev/null; then
  echo "Set your identity first:" >&2
  echo '  git config --global user.name "Your Name"' >&2
  echo '  git config --global user.email "you@example.com"' >&2
  exit 1
fi

echo "== 1. Initialise repository =="
git init -b main

echo "== 2. Commits on main =="
git add .gitignore includes/ assets/style.css
git commit -m "Add shared bootstrap, password rules, blocklist and cyber-lab stylesheet"

git add index.php assets/script.js
git commit -m "Add entry point and client-side script (strength meter, countdown)"

git add register.php login.php dashboard.php
git commit -m "Add enrollment, login with lockout and dashboard with hash inspector"

git add tests/test_forensics.py requirements.txt
git commit -m "Add Selenium functional tests (17 test cases)"

git add Dockerfile .dockerignore README.md git_workflow.sh git_workflow.ps1
git commit -m "Add Dockerfile, README and Git workflow scripts"

echo "== 3. Feature branch with an application change =="
git checkout -b feature/footer-php-version

cat > includes/footer.php <<'EOF'
</main>
<footer class="site-footer">
    <p>ITC402 Major Assignment &mdash; training lab. Do not enter real passwords.</p>
    <p>Running on PHP <?= h(PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION) ?></p>
</footer>
<script src="assets/script.js"></script>
</body>
</html>
EOF
git add includes/footer.php
git commit -m "Show PHP version in the page footer"

echo "== 4. Merge branch into main =="
git checkout main
git merge --no-ff feature/footer-php-version -m "Merge branch 'feature/footer-php-version' into main"

echo "== 5. History =="
git log --oneline --graph --decorate

if [ -n "$REMOTE_URL" ]; then
  echo "== 6. Push to GitHub =="
  git remote add origin "$REMOTE_URL"
  git push -u origin main
  git push origin feature/footer-php-version
  echo "Pushed. Open $REMOTE_URL to check the commits, the branch and the merge."
else
  echo
  echo "No remote URL given. To publish later:"
  echo "  git remote add origin https://github.com/<your-username>/<your-repo>.git"
  echo "  git push -u origin main && git push origin feature/footer-php-version"
fi
