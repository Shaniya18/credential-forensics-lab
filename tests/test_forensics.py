"""
Automated functional tests for the Credential Forensics Lab (PHP app).

Tools: Python unittest + Selenium WebDriver (Chrome).

Run (the app must already be running, e.g. `php -S localhost:8000`, XAMPP, or the Docker container):

    pip install selenium
    python tests/test_forensics.py

Windows PowerShell example (XAMPP):
    $env:BASE_URL="http://localhost/credential-forensics-lab"
    python tests\\test_forensics.py

Optional environment variables:
    BASE_URL        default http://localhost:8000 (no trailing slash)
    HEADLESS        1 = run without a browser window
    SLOW            seconds to pause after each action (e.g. 0.7) - handy for the demo
    CHROME_BINARY / CHROMEDRIVER   paths, only if Selenium cannot find them itself

Results are printed and saved to tests/test_results.txt
"""
import os
import sys
import time
import unittest
from datetime import datetime

from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import Select, WebDriverWait

BASE_URL = os.environ.get("BASE_URL", "http://localhost:8000").rstrip("/")
SLOW = float(os.environ.get("SLOW", "0"))

STRONG_PW = "Vault#Raven-9271!"      # 17 chars, all character classes -> score 91 (Excellent)
USER = "analyst_nia"


def pause():
    """Optional delay so a human can follow along during the demonstration."""
    if SLOW:
        time.sleep(SLOW)


def build_driver():
    options = Options()
    if os.environ.get("HEADLESS") == "1":
        options.add_argument("--headless=new")
    if os.environ.get("CHROME_BINARY"):
        options.binary_location = os.environ["CHROME_BINARY"]
    options.add_argument("--no-sandbox")
    options.add_argument("--disable-dev-shm-usage")
    options.add_argument("--window-size=1280,1000")
    service = Service(os.environ["CHROMEDRIVER"]) if os.environ.get("CHROMEDRIVER") else Service()
    return webdriver.Chrome(service=service, options=options)


class ForensicsLabTests(unittest.TestCase):
    """Every test starts with a fresh PHP session (cookies cleared), so no accounts exist."""

    @classmethod
    def setUpClass(cls):
        cls.driver = build_driver()
        cls.wait = WebDriverWait(cls.driver, 10)

    @classmethod
    def tearDownClass(cls):
        cls.driver.quit()

    def setUp(self):
        self.driver.get(BASE_URL + "/login.php")
        self.driver.delete_all_cookies()
        self.driver.get(BASE_URL + "/login.php")

    # ------------------------------------------------------------------ helpers
    def type_into(self, element_id, text):
        el = self.driver.find_element(By.ID, element_id)
        el.clear()
        el.send_keys(text)

    def register(self, username=USER, password=STRONG_PW, confirm=None, question="q1",
                 answer="Vatuwaqa", ack=True):
        d = self.driver
        d.get(BASE_URL + "/register.php")
        self.wait.until(EC.presence_of_element_located((By.ID, "register-form")))
        self.type_into("username", username)
        self.type_into("password", password)
        self.type_into("confirm_password", password if confirm is None else confirm)
        if question:
            Select(d.find_element(By.ID, "security_question")).select_by_value(question)
        self.type_into("security_answer", answer)
        box = d.find_element(By.ID, "ack")
        if ack != box.is_selected():
            box.click()
        pause()
        d.find_element(By.ID, "submit-btn").click()
        pause()

    def login(self, username=USER, password=STRONG_PW):
        d = self.driver
        d.get(BASE_URL + "/login.php")
        self.wait.until(EC.presence_of_element_located((By.ID, "login-form")))
        self.type_into("username", username)
        self.type_into("password", password)
        pause()
        d.find_element(By.ID, "submit-btn").click()
        pause()

    def enroll_and_login(self, username=USER, password=STRONG_PW):
        self.register(username=username, password=password)
        self.wait.until(EC.url_contains("login.php"))
        self.login(username, password)
        self.wait.until(EC.url_contains("dashboard.php"))

    def error_messages(self):
        self.wait.until(EC.visibility_of_element_located((By.ID, "error-summary")))
        return [e.text for e in self.driver.find_elements(By.CSS_SELECTOR, "#error-summary .error-item")]

    def meter(self):
        d = self.driver
        return d.find_element(By.ID, "meter-label").text, int(d.find_element(By.ID, "meter-score").text)

    def rule_ok(self, rule_id):
        return "ok" in self.driver.find_element(By.ID, rule_id).get_attribute("class").split()

    # -------------------------------------------------------------------- tests
    def test_01_login_page_loads(self):
        """The access terminal opens with its title, banner and login form fields."""
        d = self.driver
        self.assertIn("Access Terminal", d.title)
        self.assertEqual("CREDENTIAL FORENSICS LAB", d.find_element(By.CSS_SELECTOR, ".site-header h1").text)
        self.assertTrue(d.find_element(By.ID, "username").is_displayed())
        self.assertTrue(d.find_element(By.ID, "password").is_displayed())
        self.assertEqual("password", d.find_element(By.ID, "password").get_attribute("type"))
        self.assertTrue(d.find_element(By.ID, "submit-btn").is_displayed())

    def test_02_navigation_between_pages(self):
        """Navigation links and buttons move between the login and enroll pages; index redirects."""
        d = self.driver
        d.find_element(By.ID, "nav-register").click()
        self.wait.until(EC.url_contains("register.php"))
        self.assertIn("Enroll Subject", d.title)
        pause()
        d.find_element(By.ID, "cancel-link").click()
        self.wait.until(EC.url_contains("login.php"))
        d.find_element(By.ID, "enroll-link").click()
        self.wait.until(EC.url_contains("register.php"))
        d.find_element(By.ID, "nav-login").click()
        self.wait.until(EC.url_contains("login.php"))
        d.get(BASE_URL + "/index.php")
        self.wait.until(EC.url_contains("login.php"))

    def test_03_dashboard_requires_authentication(self):
        """Opening the dashboard without logging in is refused and redirected to the login page."""
        d = self.driver
        d.get(BASE_URL + "/dashboard.php")
        self.wait.until(EC.url_contains("login.php"))
        flash = self.wait.until(EC.visibility_of_element_located((By.ID, "flash-message")))
        self.assertIn("Access denied. Authenticate first.", flash.text)

    def test_04_successful_enrollment(self):
        """A valid enrollment is accepted and the user is sent to the login page."""
        self.register()
        self.wait.until(EC.url_contains("login.php"))
        flash = self.wait.until(EC.visibility_of_element_located((By.ID, "flash-message")))
        self.assertIn("Subject " + USER + " enrolled", flash.text)

    def test_05_empty_enrollment_form_rejected(self):
        """Submitting an empty enrollment form lists every missing item."""
        d = self.driver
        d.get(BASE_URL + "/register.php")
        d.find_element(By.ID, "submit-btn").click()
        messages = self.error_messages()
        self.assertIn("register.php", d.current_url)
        self.assertIn("Subject ID (username) is required.", messages)
        self.assertIn("Password is required.", messages)
        self.assertIn("Please choose a recovery question.", messages)
        self.assertIn("Recovery answer is required.", messages)
        self.assertIn("You must acknowledge the training-lab notice.", messages)

    def test_06_common_password_rejected_even_with_leet_speak(self):
        """'password123' and 'P@ssw0rd!2024' are both on the breach blocklist."""
        for bad in ("password123", "P@ssw0rd!2024"):
            with self.subTest(password=bad):
                self.register(password=bad)
                self.assertIn("Password is too common and appears on the breach blocklist.", self.error_messages())

    def test_07_password_containing_username_rejected(self):
        """A password that contains the Subject ID is refused."""
        self.register(username="nia_ops", password="Nia_Ops#Strong-2026!")
        self.assertIn("Password must not contain your username.", self.error_messages())

    def test_08_password_confirmation_must_match(self):
        """Different confirmation is refused and the typed Subject ID is kept (passwords are not)."""
        d = self.driver
        self.register(password=STRONG_PW, confirm="Something#Else-1234!")
        self.assertIn("Passwords do not match.", self.error_messages())
        self.assertEqual(USER, d.find_element(By.ID, "username").get_attribute("value"))
        self.assertEqual("", d.find_element(By.ID, "password").get_attribute("value"))

    def test_09_duplicate_subject_id_rejected(self):
        """A Subject ID can only be enrolled once (case-insensitive)."""
        self.register()
        self.wait.until(EC.url_contains("login.php"))
        self.register(username=USER.upper())
        self.assertIn("That Subject ID is already enrolled.", self.error_messages())

    def test_10_live_strength_meter_updates_while_typing(self):
        """The JavaScript meter moves from Weak to Excellent and ticks the rule checklist."""
        d = self.driver
        d.get(BASE_URL + "/register.php")
        self.assertEqual(("—", 0), self.meter())

        self.type_into("password", "abc")
        label, score = self.meter()
        self.assertEqual("Weak", label)
        self.assertFalse(self.rule_ok("rule-length"))
        self.assertTrue(self.rule_ok("rule-lower"))
        self.assertFalse(self.rule_ok("rule-upper"))

        self.type_into("password", "password123")
        self.assertEqual("Weak", self.meter()[0])
        self.assertFalse(self.rule_ok("rule-common"))

        self.type_into("password", STRONG_PW)
        self.type_into("confirm_password", STRONG_PW)
        self.assertEqual(("Excellent", 91), self.meter())
        for rule in ("length", "upper", "lower", "digit", "symbol", "user", "common", "match"):
            self.assertTrue(self.rule_ok("rule-" + rule), rule)
        self.assertIn("width: 91%", d.find_element(By.ID, "meter-bar").get_attribute("style"))

        d.find_element(By.ID, "toggle-pw").click()
        self.assertEqual("text", d.find_element(By.ID, "password").get_attribute("type"))

    def test_11_successful_login_shows_case_file(self):
        """After login the dashboard shows the account status, score, question and evidence log."""
        d = self.driver
        self.enroll_and_login()
        flash = d.find_element(By.ID, "flash-message").text
        self.assertIn("Access granted", flash)
        self.assertEqual(USER, d.find_element(By.ID, "acct-username").text)
        self.assertEqual("ACTIVE", d.find_element(By.ID, "acct-status").text)
        self.assertEqual("91", d.find_element(By.ID, "score-value").text)
        self.assertEqual("Excellent", d.find_element(By.ID, "score-label").text)
        self.assertEqual("LOW", d.find_element(By.ID, "threat-level").text)
        self.assertEqual("What was the name of your first school?", d.find_element(By.ID, "acct-question").text)
        results = [c.text for c in d.find_elements(By.CSS_SELECTOR, "#history-table .col-result")]
        self.assertEqual(["SUCCESS", "ENROLLED"], results)   # newest first

    def test_12_wrong_password_and_unknown_user_give_same_message(self):
        """The error text does not reveal whether the Subject ID exists (no user enumeration)."""
        self.register()
        self.wait.until(EC.url_contains("login.php"))
        self.login(USER, "Wrong#Password-111!")
        known = self.error_messages()
        self.login("ghost_user", "Wrong#Password-111!")
        unknown = self.error_messages()
        self.assertEqual(["Access denied: invalid Subject ID or passphrase."], known)
        self.assertEqual(known, unknown)
        self.assertNotIn("dashboard.php", self.driver.current_url)

    def test_13_lockout_after_three_failures_with_countdown(self):
        """Three bad logins lock the account; the countdown ticks down; the right password is also refused."""
        d = self.driver
        self.register()
        self.wait.until(EC.url_contains("login.php"))
        for _ in range(3):
            self.login(USER, "Wrong#Password-111!")
            self.error_messages()          # wait until the server has answered before the next attempt
        messages = self.error_messages()
        self.assertTrue(any("Too many failed attempts" in m for m in messages), messages)

        first = int(d.find_element(By.ID, "countdown").text)
        self.assertTrue(0 < first <= 30, first)
        time.sleep(2.5)
        self.assertLess(int(d.find_element(By.ID, "countdown").text), first)   # JavaScript countdown is running

        self.login(USER, STRONG_PW)                      # correct password, but the account is locked
        messages = self.error_messages()
        self.assertTrue(any(m.startswith("Account locked. Try again in") for m in messages), messages)
        self.assertNotIn("dashboard.php", d.current_url)

    def test_14_hash_inspector_shows_bcrypt_not_password(self):
        """The dashboard shows a 60-character bcrypt hash (never the password itself)."""
        d = self.driver
        self.enroll_and_login()
        full = d.find_element(By.ID, "hash-full").text
        self.assertEqual(60, len(full))
        self.assertTrue(full.startswith("$2y$10$"))
        self.assertNotIn(STRONG_PW, d.page_source)
        self.assertIn("bcrypt", d.find_element(By.ID, "hash-algo").text)
        self.assertEqual("10", d.find_element(By.ID, "hash-cost").text)
        self.assertEqual(22, len(d.find_element(By.ID, "hash-salt").text))
        self.assertEqual(31, len(d.find_element(By.ID, "hash-digest").text))

    def test_15_hash_lab_salts_differ_but_sha256_is_identical(self):
        """Hashing the same text twice gives different bcrypt hashes; empty input is refused."""
        d = self.driver
        self.enroll_and_login()

        self.type_into("lab_input", "hello")
        d.find_element(By.ID, "lab-submit").click()
        self.wait.until(EC.visibility_of_element_located((By.ID, "lab-result")))
        h1 = d.find_element(By.ID, "lab-bcrypt-1").text
        h2 = d.find_element(By.ID, "lab-bcrypt-2").text
        self.assertNotEqual(h1, h2)
        self.assertEqual(60, len(h1))
        self.assertEqual("2cf24dba5fb0a30e26e83b2ac5b9e29e1b161e5c1fa7425e73043362938b9824",
                         d.find_element(By.ID, "lab-sha256").text)          # SHA-256("hello")
        self.assertIn("DIFFER", d.find_element(By.ID, "lab-verdict").text)

        self.type_into("lab_input", "")
        d.find_element(By.ID, "lab-submit").click()
        error = self.wait.until(EC.visibility_of_element_located((By.ID, "lab-error")))
        self.assertEqual("Enter some text to hash.", error.text)

    def test_16_logout_closes_case_file(self):
        """Logging out returns to the terminal and the dashboard is protected again."""
        d = self.driver
        self.enroll_and_login()
        d.find_element(By.ID, "logout-btn").click()
        self.wait.until(EC.url_contains("login.php"))
        self.assertIn("Session terminated", self.wait.until(
            EC.visibility_of_element_located((By.ID, "flash-message"))).text)
        d.get(BASE_URL + "/dashboard.php")
        self.wait.until(EC.url_contains("login.php"))
        self.assertIn("Access denied", d.find_element(By.ID, "flash-message").text)

    def test_17_purge_with_confirm_dialog(self):
        """Purge asks for confirmation (JS alert): cancel keeps accounts, accept removes them."""
        d = self.driver
        self.register()
        self.wait.until(EC.url_contains("login.php"))

        d.find_element(By.ID, "purge-btn").click()
        self.wait.until(EC.alert_is_present())
        d.switch_to.alert.dismiss()
        self.login()                                       # account still exists
        self.wait.until(EC.url_contains("dashboard.php"))
        d.find_element(By.ID, "logout-btn").click()
        self.wait.until(EC.url_contains("login.php"))

        d.find_element(By.ID, "purge-btn").click()
        self.wait.until(EC.alert_is_present())
        d.switch_to.alert.accept()
        self.assertIn("purged", self.wait.until(EC.visibility_of_element_located((By.ID, "flash-message"))).text)
        self.login()                                       # account is gone
        self.assertEqual(["Access denied: invalid Subject ID or passphrase."], self.error_messages())


class TeeStream:
    """Write test output to the console and to a results file at the same time."""

    def __init__(self, *streams):
        self.streams = streams

    def write(self, data):
        for s in self.streams:
            s.write(data)

    def flush(self):
        for s in self.streams:
            s.flush()


if __name__ == "__main__":
    results_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), "test_results.txt")
    with open(results_path, "w", encoding="utf-8") as out:
        tee = TeeStream(sys.stdout, out)
        tee.write(f"Selenium test run: {datetime.now():%Y-%m-%d %H:%M:%S}\n")
        tee.write(f"Target: {BASE_URL}\n\n")
        suite = unittest.defaultTestLoader.loadTestsFromTestCase(ForensicsLabTests)
        result = unittest.TextTestRunner(stream=tee, verbosity=2).run(suite)
        tee.write(f"\nSummary: {result.testsRun} run, {len(result.failures)} failed, {len(result.errors)} errors\n")
    sys.exit(0 if result.wasSuccessful() else 1)
