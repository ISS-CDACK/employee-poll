<?php
if ($_SERVER['REQUEST_METHOD'] == 'GET' && realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(404);
    include($_SERVER["DOCUMENT_ROOT"]."/404.html");
    exit();
}
?>

<script src="/assets/vendor/libs/jquery/jquery.min.js"></script>

<?php

if ($page == 'login') {
    echo '<script src="/assets/vendor/libs/@form-validation/popular.js"></script>
    <script src="/assets/vendor/libs/@form-validation/bootstrap5.js"></script>
    <script src="/assets/vendor/libs/@form-validation/auto-focus.js"></script>';
}

if ($page != 'login') {
    echo '<script src="/assets/vendor/js/dark.js"></script>
    <script src="/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="/assets/vendor/js/app.js"></script>';
}

if ($page == 'mapUser') {
    echo '<script src="/assets/vendor/libs/choices.js/public/assets/scripts/choices.js"></script>';
}
elseif ($page == 'manageUsers') {
    echo '<script src="/assets/vendor/libs/choices.js/public/assets/scripts/choices.js"></script>';
    echo '<script src="/assets/vendor/libs/datatables.net/js/jquery.dataTables.min.js"></script>';
    echo '<script src="/assets/vendor/libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>';
}
elseif ($page == 'importUsers') {
    echo '<script src="/assets/vendor/libs/choices.js/public/assets/scripts/choices.js"></script>';
    echo '<script src="/assets/vendor/libs/datatables.net/js/jquery.dataTables.min.js"></script>';
    echo '<script src="/assets/vendor/libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>';
}

elseif ($page == 'dashboard') {
    echo '<script src="/assets/vendor/libs/apexcharts/apexcharts.min.js"></script>';
}

?>

<script src="/assets/vendor/libs/sweetalert2/sweetalert2.min.js"></script>
<script src="/assets/js/main.js"></script>

<?php
if ($page == 'login') {
    echo '<script src="/assets/js/login.js"></script>';
}
elseif ($page == 'manageGroups') {
    echo '<script src="/assets/js/manageGroups.js"></script>';
}
elseif ($page == 'mapUser') {
    echo '<script src="/assets/js/mapUser.js"></script>';
}
elseif ($page == 'manageUsers') {
    echo '<script src="/assets/js/manageUsers.js"></script>';
}
elseif ($page == 'voting') {
    echo '<script src="/assets/js/voting.js"></script>';
}
elseif ($page == 'viewUser') {
    echo '<script src="/assets/js/viewUser.js"></script>';
}
elseif ($page == 'dashboard') {
    echo '<script src="/assets/js/dashboard.js"></script>';
}elseif ($page == 'importUsers') {
    echo '<script src="/assets/js/importUsers.js"></script>';
}
?>

<script>
    document.onreadystatechange = function () {
    if (document.readyState == "complete") {
        hideSpinner();
    }
}
</script>