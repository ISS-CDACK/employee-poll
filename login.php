<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/static/head.php');


$loginStatus = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email']) && isset($_POST['password']) && isset($_POST['captcha'])) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
    require_once($_SERVER['DOCUMENT_ROOT'] . '/functions.php');

    $email = $_POST['email'];
    $password = $_POST['password'];
    $captcha = $_POST['captcha'];

    // Verify captcha
    if (isset($_SESSION['captcha']) && !empty($captcha) && $captcha === $_SESSION['captcha']) {
        $sql = "SELECT e.ID, e.Employee_Name, e.isGroupHead, e.email, e.Group_ID, g.Group_Name, e.authType, e.role, e.password, e.displayImg, e.isActive 
                FROM employee e 
                LEFT JOIN groups g ON e.Group_ID = g.Group_ID 
                WHERE e.email = ?;";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = mysqli_num_rows($result);

        if ($count == 1) {
            $row = $result->fetch_assoc();
            if ($row['authType'] == 'self') {
                $simpleLoginResult = loginSimple($email, $password);
                if ($simpleLoginResult['status'] === true) {
                    $dpPath = '';
                    if ($simpleLoginResult['data']['dp'] === 'default') {
                        $dpPath = '/assets/img/user-avatar.png';
                    } else {
                        $dpPath = '';
                    }
                    if ($simpleLoginResult['data']['active'] === 'true') {
                        setCustomSession($simpleLoginResult['data']['uid'], $simpleLoginResult['data']['username'], $simpleLoginResult['data']['email'], $simpleLoginResult['data']['isHead'], $simpleLoginResult['data']['groupId'], $simpleLoginResult['data']['groupName'], $simpleLoginResult['data']['role'], $simpleLoginResult['data']['authType'], $dpPath);
                        if ($simpleLoginResult['data']['role'] === 'admin') {
                            header('Location: /dashboard');
                            exit();
                        } elseif ($simpleLoginResult['data']['role'] === 'operator') {
                            header('Location: /dashboard');
                            exit();
                        } elseif ($simpleLoginResult['data']['role'] === 'user') {
                            header('Location: /voting');
                            exit();
                        }
                    } else {
                        $loginStatus = array('status' => false, 'type' => 'warning', 'message' => 'Your Account has been disabled');
                    }
                } else {
                    $loginStatus = array('status' => false, 'type' => 'error', 'message' => $simpleLoginResult['message']);
                }
            } elseif ($row['authType'] == 'ldap') {
                $ldapStatus = loginLDAP($email, $password);
                if ($ldapStatus['status'] === true) {
                    if ($row['isActive'] === 'true') {
                        insertLog($conn, $row['ID'], 'Login Success');
                        $dpPath = '';
                        if ($row['displayImg'] === 'default') {
                            $dpPath = '/assets/img/user-avatar.png';
                        } else {
                            $dpPath = '';
                        }

                        setCustomSession(
                            $row['ID'],
                            $row['Employee_Name'],
                            $row['email'],
                            $row['isGroupHead'],
                            $row['Group_ID'],
                            $row['Group_Name'],
                            $row['role'],
                            $row['authType'],
                            $dpPath
                        );

                        if ($row['role'] === 'admin' || $row['role'] === 'operator') {
                            header('Location: /dashboard');
                            exit();
                        } elseif ($row['role'] === 'user') {
                            header('Location: /voting');
                            exit();
                        }
                    } else {
                        insertLog($conn, $row['ID'], 'Login Attempted While User Not Activated');
                        $loginStatus = array('status' => false, 'type' => 'warning', 'message' => 'Your Account has been disabled');
                    }
                } else {
                    insertLog($conn, $row['ID'], 'Invalid login Attempt');
                    $loginStatus = array('status' => false, 'type' => 'error', 'message' => 'Invalid email or password');
                }
            }
        } else {
            $loginStatus = array('status' => false, 'type' => 'error', 'message' => 'Invalid email or password.');
        }
    } else {
        $loginStatus = array('status' => false, 'type' => 'warning', 'message' => 'Captcha verification failed.');
    }
}
?>

<body class="spin-lock">
    <script src="/assets/vendor/js/initTheme.js"></script>
    <?php require_once($_SERVER['DOCUMENT_ROOT'] . '/static/loadingSpinner.php'); ?>
    <div id="auth">
        <div class="row h-100">
            <div class="col-lg-5 col-12">
                <div id="auth-left">
                    <h1 class="auth-title">Log in</h1>
                    <p class="auth-subtitle mb-5">Please Log in with your CDAC LDAP Account</p>
                    <form id="login-form" action="<?php echo pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME); ?>" method="POST">
                        <div class="form-group position-relative has-icon-left mb-4">
                            <input type="text" name="email" class="form-control form-control-xl" placeholder="CDAC Email" autocomplete="off">
                            <div class="form-control-icon">
                                <i class="bi bi-person"></i>
                            </div>
                        </div>
                        <div class="form-group position-relative has-icon-left mb-4">
                            <input type="password" name="password" id="password-field" class="form-control form-control-xl" placeholder="Password" autocomplete="off">
                            <div class="form-control-icon">
                                <i class="bi bi-shield-lock"></i>
                            </div>
                            <button type="button" id="toggle-password" class="btn btn-light btn-sm position-absolute" style="right: 15px; top: 50%; transform: translateY(-50%); display: none;">
                                <i class="bi bi-eye-slash"></i>
                            </button>
                        </div>

                        <!-- CAPTCHA Box -->
                        <div class="form-group mb-4">
                            <div id="captcha-box">
                                <img src="/captcha" alt="CAPTCHA Image" id="captcha-image">
                                <button type="button" onclick="reloadCaptcha('captcha-image', 'captcha-input')" class="btn btn-light btn-sm">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-group position-relative has-icon-left mb-4">
                            <input type="text" name="captcha" class="form-control form-control-xl" id="captcha-input" placeholder="Enter CAPTCHA" autocomplete="off">
                            <div class="form-control-icon">
                                <i class="bi bi-shield-lock"></i>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block btn-lg shadow-lg mt-5">Log in</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-7 d-none d-lg-block">
                <div id="auth-right">
                </div>
            </div>
        </div>
    </div>

    <?php
    require_once($_SERVER['DOCUMENT_ROOT'] . '/static/script.php');
    if ($loginStatus != '') {
        echo "<script type='text/javascript'>showToast(5000, '" . $loginStatus['type'] . "', '" . $loginStatus['message'] . "');</script>";
    }
    ?>
</body>



</html>