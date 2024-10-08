<?php
if ($_SERVER['REQUEST_METHOD'] == 'GET' && realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(404);
    include($_SERVER["DOCUMENT_ROOT"]."/404.html");
    exit();
}
?>

<header>
    <nav class="navbar navbar-expand navbar-light navbar-top">
        <div class="container-fluid">
            <?php
            if ($page != 'voting'){
            echo '
                <a href="#" class="burger-btn d-block">
                    <i class="bi bi-justify fs-3"></i>
                </a>';
            }?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav ms-auto mb-lg-0">
                </ul>
                <div class="dropdown">
                    <a href="#" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-menu d-flex">
                            <div class="user-name text-end me-3">
                                <h6 class="mb-0 text-gray-600"><?php echo $_loginInfo['username']; ?></h6>
                                <p class="mb-0 text-sm text-gray-600"><?php echo $role; ?></p>
                            </div>
                            <div class="user-img d-flex align-items-center">
                                <div class="avatar avatar-md">
                                    <img src="<?php echo $_loginInfo['dp'];  ?>">
                                </div>
                            </div>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton" style="min-width: 11rem;">
                        <!-- <li> -->
                            <!-- <h6 class="dropdown-header">Hello, John!</h6> -->
                        <!-- </li> -->
                        <?php
                        if ($_loginInfo['role'] != 'User'){
                            echo '<li><a class="dropdown-item" href="/viewProfile"><i class="icon-mid bi bi-person me-2"></i> My
                                    Profile</a></li>
                                <hr class="dropdown-divider">
                            </li>';
                        }
                        ?>
                        <li><a class="dropdown-item" href="/logout"><i
                                    class="icon-mid bi bi-box-arrow-left me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
</header>