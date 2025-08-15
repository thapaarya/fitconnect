<?php
session_start();
require_once '../config/auth_functions.php';

// Log the user out
logoutUser();

// Redirect to home page with logout success message
$redirect_url = $_GET['redirect'] ?? '../index.php';
$redirect_url .= (strpos($redirect_url, '?') !== false ? '&' : '?') . 'logout=success';

header("Location: $redirect_url");
exit;
?>