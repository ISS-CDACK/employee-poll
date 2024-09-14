<?php

if ($_SERVER['REQUEST_METHOD'] == 'GET' && realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(404);
    include($_SERVER["DOCUMENT_ROOT"] . "/404.html");
    exit();
}

session_name("secure_session");

$page = pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME);

// Check if HTTPS is being used
$https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

// Start session with appropriate cookie settings
session_start([
    'cookie_lifetime' => 0,
    'cookie_httponly' => $https,
    'cookie_secure' => $https, // Set to true if HTTPS is being used
    'cookie_samesite' => 'Lax',
]);

$session_timeout = 3600; // Session Timeout for inactivity

if (isset($_SESSION['uid']) && isset($_SESSION['username']) && isset($_SESSION['email']) && isset($_SESSION['role']) && isset($_SESSION['authType']) && isset($_SESSION['dp'])) {
    $_loginInfo = array(
        'uid' => $_SESSION['uid'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email'],
        'isHead' => $_SESSION['isHead'],
        'groupId' => $_SESSION['groupId'],
        'groupName' => $_SESSION['groupName'],
        'role' => $_SESSION['role'],
        'authType' => $_SESSION['authType'],
        'dp' => $_SESSION['dp'],
    );
} else {
    $_loginInfo = false;
}

function setCustomSession($uid, $username, $email, $isHead, $groupId, $groupName, $role, $authType, $dp)
{
    global $_loginInfo;
    // Set session values
    $_SESSION['uid'] = $uid;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['isHead'] = $isHead;
    $_SESSION['groupId'] = $groupId;
    $_SESSION['groupName'] = $groupName;
    $_SESSION['role'] = ucwords($role);
    $_SESSION['authType'] = $authType;
    $_SESSION['dp'] = $dp;
    $_SESSION['agent'] = $_SERVER['HTTP_USER_AGENT'];
    $_SESSION['last_activity'] = time();
    $_loginInfo = array(
        'uid' => $_SESSION['uid'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email'],
        'isHead' => $_SESSION['isHead'],
        'groupId' => $_SESSION['groupId'],
        'groupName' => $_SESSION['groupName'],
        'role' => $_SESSION['role'],
        'authType' => $_SESSION['authType'],
        'dp' => $_SESSION['dp'],
    );
    return true;
}

// Session timeout and user agent checking
if (isset($_SESSION['authType'])) {
    if (isset($_SESSION['last_activity'])) {
        $inactive_time = time() - $_SESSION['last_activity'];
        if ($inactive_time > $session_timeout) {
            // Unset all session values
            $_SESSION = array();
            $_loginInfo = array();

            // Destroy the session cookie
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }

            // Destroy the session
            session_destroy();

            header("Location: /");
            exit();
        }
    }
    session_regenerate_id();

    if ($_SESSION['agent'] === $_SERVER['HTTP_USER_AGENT']) {
        $_SESSION['last_activity'] = time(); // Update last activity time
    } else {
        // Unset all session values
        $_SESSION = array();
        $_loginInfo = array();

        // Destroy the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // Destroy the session
        session_destroy();

        header("Location: /");
        exit();
    }
}
