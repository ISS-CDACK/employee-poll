<?php

require_once $_SERVER["DOCUMENT_ROOT"] . "/session.php";
require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');

// Unset all session values
$_SESSION = array();
$_loginInfo = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

$conn->close();

header("Location: /");
die();

?>