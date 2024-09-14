<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/static/head.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/functions.php');

$remark = ["status" => true];

$currentEmpId = $_loginInfo['uid'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $query = '';
    $queryEnd = '';
    if ($_loginInfo['role'] === 'Operator') {
        $queryEnd = " AND role != 'admin'";
    }
    if (isset($_POST['activateAllUsers'])) {
        // Activate all users
        $query = "UPDATE employee SET isActive = 'true' WHERE ID != ?" . $queryEnd;
        $remark = ["status" => true, "type" => 'success', "message" => "All users activated successfully."];
    } elseif (isset($_POST['deactivateAllUsers'])) {
        // Deactivate all users
        $query = "UPDATE employee SET isActive = 'false' WHERE ID != ?" . $queryEnd;
        $remark = ["status" => true, "type" => 'success', "message" => "All users deactivated successfully."];
    }

    if ($query) {
        if ($stmt = $conn->prepare($query)) {
            // Bind parameters (in this case, just one integer parameter for the currentEmpId)
            $stmt->bind_param('i', $currentEmpId);

            // Execute the statement
            if ($stmt->execute()) {
                $stmt->close();
            }
            //  else {
                // $remark = ["status" => false, "type" => 'error', "message" => "Error executing query: " . $stmt->error];
            // }
        } else {
            $remark = ["status" => false, "type" => 'error', "message" => "Unexpected Error Occurred"];
        }
    }

    // Optionally, you might want to return or display the $remark array to inform the user about the operation result.
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
                                <h3>Manage Users</h3>
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
                    $sql = "SELECT e.ID, e.Employee_Name, e.isGroupHead, e.email, g.Group_Name, e.role, e.isActive FROM employee e LEFT JOIN groups g ON e.Group_ID = g.Group_ID ORDER BY `e`.`role` ASC, `e`.`Employee_Name` ASC;";
                    $result = $conn->query($sql);
                    ?>

                    <section class="section">
                        <div class="card mt-4">
                            <!-- <div class="card-header"> -->
                            <!-- <h5 class="card-title"> -->
                            <!-- jQuery Datatable -->
                            <!-- </h5> -->
                            <!-- </div> -->
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <h6>Select Group</h6>
                                            <div class="form-group">
                                                <select class="choices form-select" id="filterGroup">
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <h6>Select Status</h6>
                                            <div class="form-group">
                                                <select class="choices form-select" id="filterStatus">
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <h6>Show only group head</h6>
                                            <div class="form-group">
                                                <select class="choices form-select" id="filterGroupHead">
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <h6>Select Role</h6>
                                            <div class="form-group">
                                                <select class="choices form-select" id="filterRole">
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table" id="table1">
                                        <thead>
                                            <tr>
                                                <th class='text-center'>SL No.</th>
                                                <th class='text-center'>Name</th>
                                                <th class='text-center'>Email</th>
                                                <th class='text-center'>Group</th>
                                                <th class='text-center'>Role</th>
                                                <th class='text-center'>Is Group Head</th>
                                                <th class='text-center'>Account Status</th>
                                                <th class='text-center'>-</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <?php
                                            if ($result->num_rows > 0) {
                                                $sl_no = 1;
                                                while ($row = $result->fetch_assoc()) {
                                                    echo "<tr>";
                                                    echo "<td class='text-center'>" . $sl_no++ . "</td>";
                                                    echo "<td class='text-center'>" . htmlspecialchars($row['Employee_Name']) . "</td>";
                                                    echo "<td class='text-center'>" . htmlspecialchars($row['email']) . "</td>";
                                                    echo "<td class='text-center'>" . (htmlspecialchars($row['Group_Name']) != "" ? htmlspecialchars($row['Group_Name']) : '-') . "</td>";
                                                    echo "<td class='text-center'>" . ucfirst(htmlspecialchars($row['role'])) . "</td>";
                                                    echo "<td class='text-center'>" . ($row['isGroupHead'] ? 'Yes' : 'No') . "</td>";
                                                    echo "<td class='text-center'>" . ($row['isActive'] == 'false' ?  '<span class="badge bg-danger">Inactive</span>' : '<span class="badge bg-success">Active</span>') . "</td>";
                                                    echo "<td class='text-center'><a type='button' class='btn btn-outline-secondary' href=/viewUser?UID='" . urlencode(encryptData(htmlspecialchars($row['ID']), $key)) . "'><i class='bi bi-eye'></i></a></td>";
                                                    echo "</tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='6'>No records found</td></tr>";
                                            }
                                            ?>
                                            <!-- <span class="badge bg-success">Active</span>  -->
                                        </tbody>
                                    </table>
                                </div>
                                <form id="deactivateAllUsers" action="<?php echo pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME); ?>" method="POST">
                                    <input type="hidden" name="deactivateAllUsers" value="true">
                                </form>
                                <form id="activateAllUsers" action="<?php echo pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME); ?>" method="POST">
                                    <input type="hidden" name="activateAllUsers" value="true">
                                </form>
                                <div class="d-flex justify-content-end mb-0 mt-4">
                                    <button type="button" id="activateAll" class="btn btn-outline-success me-2">Active All User</button>
                                    <button type="button" id="deactivateAll" class="btn btn-outline-danger">Deactivate All User</button>
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

    <script>
        // Get references to the buttons and forms
        const activateAllButton = document.getElementById('activateAll');
        const deactivateAllButton = document.getElementById('deactivateAll');
        const activateAllForm = document.getElementById('activateAllUsers');
        const deactivateAllForm = document.getElementById('deactivateAllUsers');

        // Event listener for the "Activate All User" button
        activateAllButton.addEventListener('click', () => {
            showSpinner();
            activateAllForm.submit();
        });

        // Event listener for the "Deactivate All User" button
        deactivateAllButton.addEventListener('click', () => {
            showSpinner();
            deactivateAllForm.submit();
        });
    </script>
</body>