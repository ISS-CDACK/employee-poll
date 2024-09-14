<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/static/head.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/functions.php');

$decrypted_data = decryptData($_GET['UID'], $key);

if (!(isset($_GET['UID']) && is_numeric($decrypted_data) && filter_var($decrypted_data, FILTER_VALIDATE_INT) !== false)) {
    header("Location: /manageUsers");
    exit();
}
?>

<body class="spin-lock">
    <script src="/assets/vendor/js/initTheme.js"></script>
    <?php require_once($_SERVER['DOCUMENT_ROOT'] . '/static/loadingSpinner.php'); ?>
    <div id="app">
        <?php require_once($_SERVER['DOCUMENT_ROOT'] . '/static/sidebar.php'); ?>
        <div id="main" class='layout-navbar navbar-fixed'>
            <?php require_once($_SERVER['DOCUMENT_ROOT'] . '/static/navbar.php'); ?>
            <div id="main-content">
                <div class="page-heading">
                    <div class="page-title">
                        <div class="row">
                            <div class="col-12 col-md-6 order-md-1 order-last">
                                <h3>View User Details</h3>
                                <!-- <p class="text-subtitle text-muted">Navbar will appear on the top of the page.</p> -->
                                <!-- <li class="breadcrumb-item"><a href="index.html">Dashboard</a></li> -->
                                <!-- <li class="breadcrumb-item active" aria-current="page">Layout Vertical Navbar</li> -->
                            </div>
                            <!-- <div class="col-12 col-md-6 order-md-2 order-first"> -->
                            <!-- <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end"> -->
                            <!-- <ol class="breadcrumb"> -->
                            <!-- <button class="btn btn-primary cursor-pointer" onclick="showAddGroupModal()">Add New Group</button> -->
                            <!-- </ol> -->
                            <!-- </nav> -->
                            <!-- </div> -->
                        </div>
                    </div>

                    <?php
                    $sql = "SELECT e.ID, e.Employee_Name, e.isGroupHead, e.email, e.Group_ID, g.Group_Name, e.authType, e.role, e.displayImg, e.isActive FROM employee e LEFT JOIN groups g ON e.Group_ID = g.Group_ID WHERE e.ID = ?;";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('i', $decrypted_data);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    $stmt->close();
                    if (!$row) {
                        header("Location: /manageUsers");
                        exit();
                    }
                    ?>

                    <section class="section mt-3">
                        <div class="row justify-content-center">
                            <div class="col-12 col-md-8 col-lg-6">
                                <div class="card shadow-lg">
                                    <div class="card-body text-center p-4">
                                        <div class="avatar avatar-3xl border rounded-circle mx-auto mb-4">
                                            <img src="<?php echo $row['displayImg'] === 'default' ? '/assets/img/user-avatar.png' : $row['displayImg']; ?>"
                                                class="rounded-circle img-fluid"
                                                alt="User Avatar"
                                                style="width: 180px; height: 180px; object-fit: cover;">
                                        </div>
                                        <h3 class="mt-1 mb-0 fw-bold"><?php echo htmlspecialchars($row['Employee_Name']); ?></h3>
                                        <p class="text-muted mb-0"><?php echo htmlspecialchars($row['email']); ?></p>

                                        <?php if ($row['isGroupHead']) : ?>
                                            <span class="badge bg-info mt-2 fs-6 px-3 py-1">Group Head</span>
                                        <?php endif; ?>

                                        <?php if (!$row['isActive']) : ?>
                                            <div class="mt-3">
                                                <span class="badge bg-danger fs-6 px-3 py-1">Inactive</span>
                                            </div>
                                        <?php endif; ?>

                                        <hr class="my-4">

                                        <div class="row justify-content-center">
                                            <div class="col-6">
                                                <p class="mb-2"><strong>Group Name: </strong><?php echo htmlspecialchars($row['Group_Name']) ?: 'Not in any group'; ?></p>
                                                <p class="mb-2"><strong>LDAP Status: </strong><?php if ($row['authType'] == 'ldap') : echo 'True';
                                                                                                else: echo 'False';
                                                                                                endif; ?></p>
                                            </div>
                                            <div class="col-6">
                                                <p class="mb-2"><strong>Role:</strong> <?php echo htmlspecialchars(ucwords($row['role'])); ?></p>
                                                <p class="mb-2"><strong>Account Status:</strong> <span id="state"><?php echo $row['isActive'] == 'true' ? 'Active' : 'Inactive'; ?></span></p>
                                            </div>

                                            <?php if(!(($_loginInfo['role'] === 'Operator' && $row['role'] === 'admin') or ($_loginInfo['uid']) == $row['ID'])){?>
                                            <div class="col-12 mt-3">
                                                <input type="radio" class="btn-check" name="options-outlined" id="success-outlined" autocomplete="off" onclick="toggleAccount(true)" <?php echo $row['isActive'] == 'true' ? 'checked' : ''; ?>>
                                                <label class="btn btn-outline-success" for="success-outlined">Activate Account</label>
                                                <input type="radio" class="btn-check" name="options-outlined" id="danger-outlined" autocomplete="off" onclick="toggleAccount(false)" <?php echo $row['isActive'] == 'true' ? '' : 'checked'; ?>>
                                                <label class="btn btn-outline-danger" for="danger-outlined">Deactivate Account</label>
                                            </div>
                                            <?php }?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                </div>
                <?php require_once($_SERVER['DOCUMENT_ROOT'] . '/static/footer.php'); ?>
        </div>
    </div>

    </div>
    <?php require_once($_SERVER['DOCUMENT_ROOT'] . '/static/script.php');
    ?>
</body>