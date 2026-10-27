<?php
// Entry point: send the visitor to the right page.
require __DIR__ . '/includes/bootstrap.php';
redirect(current_user() !== null ? 'dashboard.php' : 'login.php');
