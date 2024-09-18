<?php
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Feature-Policy: geolocation 'none'; midi 'none'; camera 'none'; usb 'none'");

require_once($_SERVER['DOCUMENT_ROOT'] . '/session.php');

// var_dump($_SESSION);
// die();

if ($_loginInfo or $page == 'login') {
    if ($_loginInfo) {
        $role = $_loginInfo['role'];
    }
} else {
    header("Location: /login");
    die();
}

if ($_loginInfo && $page == 'login') {
    if ($role === 'Operator' || $role === 'Admin') {
        header('Location: /dashboard');
        exit();
    }
    elseif ($role === 'User') {
        header('Location: /voting');
        exit();
    }
}

if ($page == 'login') {
    $title_info = 'Login';
} elseif ($page == 'manageGroups' && ($role === 'Operator' || $role === 'Admin')) {
    $title_info = 'Manage Groups';
} elseif ($page == 'mapUser' && ($role === 'Operator' || $role === 'Admin')) {
    $title_info = 'Map Users against Groups';
} elseif ($page == 'manageUsers' && ($role === 'Operator' || $role === 'Admin')) {
    $title_info = 'Manage Users';
} elseif ($page == 'voting' && ($role === 'User')) {
    $title_info = 'Voting Booth';
} elseif ($page == 'viewUser' && ($role === 'Operator' || $role === 'Admin')) {
    $title_info = 'View User';
} elseif ($page == 'viewProfile' && ($role === 'Operator' || $role === 'Admin')) {
    $title_info = 'Profile Details';
} elseif ($page == 'dashboard' && ($role === 'Operator' || $role === 'Admin')) {
    $title_info = 'Profile Details';
} elseif ($page == 'importUsers' && ($role === 'Operator' || $role === 'Admin')) {
    $title_info = 'Import Users';
} elseif ($page == 'result' && $role === 'Admin') {
    $title_info = 'Voting Result';
}

if (!isset($title_info)) {
    header("Location: /login");
    die();
}
$title = $title_info . " | CDAC-K Voting Portal";
?>

<!DOCTYPE html>
<html lang="en">

<?php

$preventReloadPage = "";

if ($page == 'login') {
    $preventReloadPage = "/login";
} elseif ($page == 'manageGroups') {
    $preventReloadPage = "/manageGroups";
} elseif ($page == 'mapUser') {
    $preventReloadPage = "/mapUser";
} elseif ($page == 'voting') {
    $preventReloadPage = "/voting";
} elseif ($page == 'viewUser') {
    $preventReloadPage = "/viewUser";
} elseif ($page == 'importUsers') {
    $preventReloadPage = "/importUsers";
}

if ($preventReloadPage != "") {
    echo "<script type='text/javascript'>const navigationEntries = performance.getEntriesByType('navigation');if (navigationEntries.length && navigationEntries[0].type === 'reload') {document.location.replace('" . $preventReloadPage . "');}</script>";
} ?>


<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>


    <link rel="stylesheet" href="/assets/vendor/libs/sweetalert2/sweetalert2.min.css">
    <link rel="stylesheet" href="/assets/vendor/libs/spinkit/spinkit.css">

    <link rel="stylesheet" crossorigin href="/assets/vendor/css/app.css" />
    <link rel="stylesheet" crossorigin href="/assets/vendor/css/app-dark.css" />
    <link rel="stylesheet" crossorigin href="/assets/css/common.css" />

    <?php
    if ($page == 'login') {
        echo '<link rel="stylesheet" href="/assets/vendor/libs/@form-validation/form-validation.css" />';
        echo '<link rel="stylesheet" crossorigin href="/assets/css/auth.css" />';
    } elseif ($page == 'mapUser') {
        echo '<link rel="stylesheet" crossorigin href="/assets/vendor/libs/choices.js/public/assets/styles/choices.css" />';
        echo '<link rel="stylesheet" crossorigin href="/assets/css/mapUser.css" />';
    } elseif ($page == 'manageUsers') {
        echo '<link rel="stylesheet" crossorigin href="/assets/vendor/libs/choices.js/public/assets/styles/choices.css" />';
        echo '<link rel="stylesheet" crossorigin href="/assets/vendor/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css" />';
        echo '<link rel="stylesheet" crossorigin href="/assets/css/manageUsers.css" />';
    } elseif ($page == 'importUsers') {
        echo '<link rel="stylesheet" crossorigin href="/assets/vendor/libs/choices.js/public/assets/styles/choices.css" />';
        echo '<link rel="stylesheet" crossorigin href="/assets/vendor/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css" />';
        echo '<link rel="stylesheet" crossorigin href="/assets/css/manageUsers.css" />';
    } elseif ($page == 'voting') {
        echo '<link rel="stylesheet" crossorigin href="/assets/css/voting.css" />';
    }
    ?>
</head>