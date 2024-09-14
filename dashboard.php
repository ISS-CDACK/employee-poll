<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/static/head.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');

// $role
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
                                <h3><?php echo ucfirst($role); ?> Dashboard</h3>
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
                    $sql = "SELECT COUNT(*) AS total_users, SUM(CASE WHEN isActive = 'true' THEN 1 ELSE 0 END) AS active_users, SUM(CASE WHEN Group_ID IS NULL THEN 1 ELSE 0 END) AS users_with_null_group FROM employee WHERE role = 'user';";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    $stmt->close();

                    $sql = "SELECT COUNT(*) AS total_groups FROM groups;";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row2 = $result->fetch_assoc();
                    $stmt->close();
                    ?>

                    <section class="row mt-3">
                        <div class="row">
                            <div class="col-6 col-lg-3 col-md-6">
                                <div class="card">
                                    <div class="card-body px-4 py-4-5">
                                        <div class="row">
                                            <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start ">
                                                <div class="stats-icon bi bi-person-fill purple mb-2">
                                                    <i class="iconly-boldShow"></i>
                                                </div>
                                            </div>
                                            <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                                                <h6 class="text-muted font-semibold">Total Users</h6>
                                                <h6 class="font-extrabold mb-0"><?php echo ($row['total_users']); ?></h6>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-lg-3 col-md-6">
                                <div class="card">
                                    <div class="card-body px-4 py-4-5">
                                        <div class="row">
                                            <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start ">
                                                <div class="stats-icon bi bi-people-fill blue mb-2">
                                                    <i class="iconly-boldProfile"></i>
                                                </div>
                                            </div>
                                            <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                                                <h6 class="text-muted font-semibold">Total Groups</h6>
                                                <h6 class="font-extrabold mb-0"><?php echo ($row2['total_groups']); ?></h6>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-lg-3 col-md-6">
                                <div class="card">
                                    <div class="card-body px-4 py-4-5">
                                        <div class="row">
                                            <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start ">
                                                <div class="stats-icon bi bi-person-check-fill green mb-2">
                                                    <i class="iconly-boldAdd-User"></i>
                                                </div>
                                            </div>
                                            <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                                                <h6 class="text-muted font-semibold">Active Users</h6>
                                                <h6 class="font-extrabold mb-0"><?php echo ($row['active_users']); ?></h6>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-lg-3 col-md-6">
                                <div class="card">
                                    <div class="card-body px-4 py-4-5">
                                        <div class="row">
                                            <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start ">
                                                <div class="stats-icon bi bi-person-x-fill red mb-2">
                                                    <i class="iconly-boldBookmark"></i>
                                                </div>
                                            </div>
                                            <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                                                <h6 class="text-muted font-semibold">User Not In Group</h6>
                                                <h6 class="font-extrabold mb-0"><?php echo ($row['users_with_null_group']); ?></h6>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h4>Group Wise Data</h4>
                                    </div>
                                    <div class="card-body">
                                        <div id="chart-group-stat"></div>
                                        <!-- SELECT g.Group_ID, g.Group_Name, COUNT(e.ID) AS Total_Users, COUNT(CASE WHEN e.isActive = 'true' THEN 1 END) AS Active_Users, COUNT(CASE WHEN e.isActive = 'false' OR e.isActive IS NULL THEN 1 END) AS Inactive_Users FROM groups g LEFT JOIN employee e ON g.Group_ID = e.Group_ID GROUP BY g.Group_ID, g.Group_Name; -->
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

    </div>
    <?php require_once($_SERVER['DOCUMENT_ROOT'] . '/static/script.php');
    ?>
</body>