<?php
if ($_SERVER['REQUEST_METHOD'] == 'GET' && realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(404);
    include($_SERVER["DOCUMENT_ROOT"] . "/404.html");
    exit();
}
?>

<!-- <footer> -->
    <!-- <div class="footer clearfix mb-0 text-muted"> -->
        <!-- <div class="float-start"> -->
            <!-- <p>Developed By CDAC Kolkata ISS Team</p> -->
        <!-- </div> -->
        <!-- <div class="float-end"> -->
            <!-- <p><a href="https://github.com/ISS-CDACK/CCTF" target="_blank" class="custom-link"> -->
                    <!-- <span class="default-text">CDAC Employee Voting Portal</span> <i class="bi bi-github"></i> -->
                <!-- </a></p> -->
        <!-- </div> -->
    <!-- </div> -->
<!-- </footer> -->