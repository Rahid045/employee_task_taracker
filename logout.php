<?php
session_start();
require_once __DIR__ . '/app/init.php';

if (isset($_SESSION['user']['id'])) {
    logAuditEvent($_SESSION['user']['id'], 'logout', 'User signed out.');
}

destroyUserSession();

header('Location: login.php');
exit;
