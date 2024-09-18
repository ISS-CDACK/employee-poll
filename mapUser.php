<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/static/head.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');

$remark = ["status" => true];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['groupHeadName']) && is_array($_POST['groupMembersName']) && isset($_POST['groupId'])) {
    // Initialize variables with default values
    $groupHeadId = isset($_POST['groupHeadName']) ? $_POST['groupHeadName'] : null;
    $groupMembersID = isset($_POST['groupMembersName']) ? $_POST['groupMembersName'] : [];
    $groupId = isset($_POST['groupId']) ? $_POST['groupId'] : null;

    // Validate and cast groupHeadId
    $groupHeadId = filter_var($groupHeadId, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
    if ($groupHeadId === false) {
        $remark = ["status" => false, "type" => "error", "message" => "Invalid group head ID."];
    }
    $groupHeadId = (int)$groupHeadId; // Explicitly cast to integer

    // Validate and cast groupId
    $groupId = filter_var($groupId, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
    if ($groupId === false) {
        $remark = ["status" => false, "type" => "error", "message" => "Invalid group ID."];
    }
    $groupId = (int)$groupId; // Explicitly cast to integer

    // Validate and cast groupMembersID
    if (!is_array($groupMembersID) || !array_filter($groupMembersID, function ($id) {
        return filter_var($id, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]) !== false;
    })) {
        $remark = ["status" => false, "type" => "error", "message" => "Invalid group members IDs."];
    }

    if ($remark['status']) {

        // Cast each valid group member ID to integer
        $groupMembersID = array_map('intval', $groupMembersID);

        // Ensure group head ID is included in group members
        $groupMembersID = array_merge($groupMembersID, !in_array($groupHeadId, $groupMembersID) ? [$groupHeadId] : []);

        // Begin transaction
        $conn->begin_transaction();

        try {
            // Remove existing group head if present
            $sql = "SELECT `ID` FROM `employee` WHERE `Group_ID` = ? AND `isGroupHead` = 'true'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $groupId);
            $stmt->execute();
            $result = $stmt->get_result();
            $existingGroupHead = $result->fetch_assoc();
            $stmt->close();

            // Remove all employees from the specified group
            $sql = "UPDATE `employee` SET `Group_ID` = NULL WHERE `Group_ID` = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $groupId);
            $stmt->execute();
            $stmt->close();

            if ($existingGroupHead) {
                $sql = "UPDATE `employee` SET `isGroupHead` = NULL WHERE `ID` = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('i', $existingGroupHead['ID']);
                $stmt->execute();
                $stmt->close();
            }

            // Set new group head
            $sql = "UPDATE `employee` SET `isGroupHead` = 'true' WHERE `ID` = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $groupHeadId);
            $stmt->execute();
            $stmt->close();

            // Update group members
            $sql = "UPDATE `employee` SET `Group_ID` = ? WHERE `ID` IN (" . implode(',', array_fill(0, count($groupMembersID), '?')) . ")";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(str_repeat('i', count($groupMembersID) + 1), $groupId, ...$groupMembersID);
            $stmt->execute();
            $stmt->close();

            // Commit transaction
            $conn->commit();
            $remark = ["status" => false, "type" => "success", "message" => "Group updated successfully."];
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            handle_error($e);
        }
    }
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
                                <h3>Map Users against Groups</h3>
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

                    <section class="section">
                        <?php
                        $sql = "SELECT g.Group_ID, g.Group_Name, COUNT(e.ID) AS User_Count, GROUP_CONCAT(CASE WHEN e.isGroupHead = 'true' THEN e.Employee_Name ELSE NULL END) AS GroupHead_Name FROM `groups` g LEFT JOIN `employee` e ON g.Group_ID = e.Group_ID GROUP BY g.Group_ID, g.Group_Name;";
                        $result = $conn->query($sql);
                        $groupNames = $result->fetch_all(MYSQLI_ASSOC);

                        if ($result->num_rows == 0) {
                            echo '
                                <div class="col-12 mt-3">
                                    <div class="card">
                                        <div class="card-content">
                                            <div class="card-body">
                                                <div class="alert alert-primary">
                                                    <h4 class="alert-heading">No Groups Found</h4>
                                                    <p>Before mapping users to a group, please ensure that groups are added first. To add a new group, <a href="/manageGroups">visit here</a>.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>';
                        } else {
                            echo '<div class="row g-4 mt-2">';
                            foreach ($groupNames as $group) {
                                $groupName = htmlspecialchars($group['Group_Name']);
                                $groupId = htmlspecialchars($group['Group_ID']);
                                $userCount = htmlspecialchars($group['User_Count']);
                                $groupHeadName = htmlspecialchars($group['GroupHead_Name']);
                            
                                // Determine if a group head is set
                                $groupHeadText = $groupHeadName !== ''
                                    ? "<strong>Group Head:</strong> $groupHeadName"
                                    : "<strong>No group head has been selected</strong>";
                            
                                echo "
                                <div class='col-lg-3 col-md-4 col-sm-6'>
                                    <div class='card shadow-sm h-100 border-light rounded-lg hover-card cursor-pointer' onclick=\"openGroupModal('$groupName', '$groupId')\">
                                        <div class='card-body text-center'>
                                            <h5 class='card-title mb-3'>$groupName</h5>
                                            <p class='card-text mb-3'>
                                                $groupHeadText
                                            </p>
                                            <div class='d-flex justify-content-center align-items-center'>
                                                <span class='badge bg-primary'>$userCount Users</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>";
                            }
                            echo '</div>';
                            
                        }
                        ?>
                    </section>
                </div>
                <?php require_once($_SERVER['DOCUMENT_ROOT'] . '/static/footer.php'); ?>
            </div>
        </div>

        <div class="modal fade modal-borderless" id="modal-add-group" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <form id="addNewGroup" method="post" action="<?php echo pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME); ?>">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modal-title"></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="groupCancel()"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <h6>Select Group Head Name</h6>
                                <div class="form-group">
                                    <select class="choices form-select" id="groupHeadName" name="groupHeadName" required>
                                        <option value="" disabled selected>Please Select Group Head</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <h6>Select Group Members</h6>
                                <div class="form-group">
                                    <select class="choices form-select multiple-remove" id="groupMembersName" name="groupMembersName[]" multiple="multiple">
                                    </select>
                                </div>
                            </div>

                            <input type="hidden" id="groupId" name="groupId">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" onclick="groupCancel()">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>

    </div>

    <?php require_once($_SERVER['DOCUMENT_ROOT'] . '/static/script.php');
    if (isset($remark['status']) && isset($remark['type'])) {
        echo "<script type='text/javascript'>showToast(5000, '" . $remark['type'] . "', '" . $remark['message'] . "');</script>";
    }
    ?>
</body>