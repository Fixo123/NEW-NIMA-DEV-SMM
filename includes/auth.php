<?php
// Include this at the top of any page that requires the user to be logged in.
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
