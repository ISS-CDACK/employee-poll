<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/static/head.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');

$remark = ["status" => true];
?>

<body class="spin-lock">
    <script src="/assets/vendor/js/initTheme.js"></script>
    <?php require_once($_SERVER['DOCUMENT_ROOT'] . '/static/loadingSpinner.php'); ?>
    <div id="app">
        <?php require_once($_SERVER['DOCUMENT_ROOT'] . '/static/sidebar.php'); ?>
        <!-- <div id="main" class='layout-navbar navbar-fixed'> -->
            <?php require_once($_SERVER['DOCUMENT_ROOT'] . '/static/navbar.php'); ?>
            <div id="main-content">
            <!-- <div id="main-content justify-content-center" style="max-width: 98vw;"> -->
                <div class="page-heading">
                    <?php
                    $userGroup = $_loginInfo['groupId'];
                    $userEmpId = $_loginInfo['uid'];

                    $sql = "SELECT COUNT(*) AS vote_count, GROUP_CONCAT(ts) AS timestamps FROM voting WHERE `Emp_ID` = ?;";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('i', $userEmpId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    $voteCount = $row['vote_count'];
                    $timestamps = $row['timestamps'];
                    $stmt->close();

                    if (!$voteCount > 0) {

                        // Prepare the SQL statement with placeholders
                        $sql = "SELECT e.ID, e.Employee_Name, e.isGroupHead, e.email, e.Group_ID, e.authType, e.role, e.isActive, e.displayImg, g.Group_Name FROM employee e INNER JOIN groups g ON e.Group_ID = g.Group_ID WHERE e.ID != ? AND (e.isGroupHead IS NULL OR e.isGroupHead = 'false') ORDER BY e.Group_ID;";

                        // Initialize prepared statement
                        $stmt = $conn->prepare($sql);

                        // Bind parameters (s for string, i for integer, etc.)
                        $stmt->bind_param('i', $userEmpId);

                        // Execute the statement
                        $stmt->execute();

                        // Fetch results
                        $result = $stmt->get_result();
                        $groups = [];
                        $userGroupData = null;

                        if ($result->num_rows > 0 && ($userGroup != null or $userGroup!="") ) {
                            // Organize employees by Group_ID
                            while ($row = $result->fetch_assoc()) {
                                if ($row['Group_ID'] == $userGroup) {
                                    $userGroupData['Group_Name'] = $row['Group_Name'];
                                    $userGroupData['employees'][] = $row;
                                } else {
                                    $groups[$row['Group_ID']]['Group_Name'] = $row['Group_Name'];
                                    $groups[$row['Group_ID']]['employees'][] = $row;
                                }
                            }
                            // Add the user's group at the top
                            if ($userGroupData) {
                                $groups = ['userGroup' => $userGroupData] + $groups;
                            }
                    ?>
                                        <div class="page-title">
                        <div class="row" style="max-width: 98vw;">
                            <div class="col-12 col-md-6 order-md-1 order-last">
                                <h3>Voting Booth</h3>
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
                            <section class="section mt-3">
                                <?php foreach ($groups as $group_id => $group): ?>
                                    <div class="accordion">
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="headingGroup<?php echo $group_id; ?>">
                                                <button class="accordion-button bg-info text-white fw-bold" type="button">
                                                    <?php echo $group['Group_Name'] . ($group_id == 'userGroup' ? ' (Own Group)' : ''); ?>
                                                </button>
                                            </h2>
                                            <div id="collapseGroup<?php echo $group_id; ?>" class="accordion-collapse show" aria-labelledby="headingGroup<?php echo $group_id; ?>">
                                                <div class="accordion-body">
                                                    <div class="row row-cols-1 row-cols-md-3 g-4 justify-content-center">
                                                        <?php foreach ($group['employees'] as $employee): ?>
                                                            <div class="col">
                                                                <div class="card h-100">
                                                                    <div class="image-container">
                                                                        <img src="<?php echo $employee['displayImg'] == 'default' ? '/assets/img/user-avatar.png' : $employee['displayImg']; ?>" class="card-img-top" alt="<?php echo $employee['Employee_Name']; ?>" style="width: 200px; height: auto;">
                                                                        <img id="votedImg<?php echo $employee['ID']; ?>" src="/assets/img/voteStamp.png" class="voted-img" alt="Voted" style="width: 200px; height: auto; display: none;">
                                                                    </div>
                                                                    <div class="card-body text-center pt-0 mt-0">
                                                                        <h5 class="card-title"><?php echo $employee['Employee_Name']; ?></h5>
                                                                        <button id="voteBtn<?php echo $employee['ID']; ?>" class="btn btn-primary vote-btn" data-employee-id="<?php echo $employee['ID']; ?>" data-employee-name="<?php echo $employee['Employee_Name']; ?>">
                                                                            Vote for <?php echo $employee['Employee_Name']; ?>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <div class="container d-flex justify-content-center mt-3 pt-3">
                                    <button class="btn btn-secondary" onclick="submitVote()">
                                        Submit Vote
                                    </button>
                                </div>
                            </section>

                    <?php
                        } else {
                            echo '
                            <div class="col-12 mt-3">
                                <div class="card" style="background-color: rgba(255, 255, 255, 0); border: none;">
                                    <div class="card-content">
                                        <div class="card-body">
                                            <div class="alert alert-primary">
                                                <h4 class="alert-heading">Under Maintenance</h4>
                                                <p>Its Seems Like Something Does\'t Setup Properly. Please Contact Administrator</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                        }
                        // Close the statement and connection
                        $stmt->close();
                    } else {
                        echo '
                        <div class="col-12 mt-3">
                            <div class="card" style="background-color: rgba(255, 255, 255, 0); border: none;">
                                <div class="card-content">
                                    <div class="card-body">
                                        <div class="alert alert-primary">
                                            <h4 class="alert-heading">Thank You!</h4>
                                            <p>Your vote has already been cast at <strong>' . date('d F Y h:i A', strtotime($timestamps)) . '</strong>.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>';
                    }
                    ?>
                </div>
                <?php require_once($_SERVER['DOCUMENT_ROOT'] . '/static/footer.php'); ?>
            </div>
        </div>
    </div>

    <!-- </div> -->

    <?php require_once($_SERVER['DOCUMENT_ROOT'] . '/static/script.php');
    ?>
</body>