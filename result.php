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
        <div id="main" class='layout-navbar navbar-fixed'>
            <?php require_once($_SERVER['DOCUMENT_ROOT'] . '/static/navbar.php'); ?>
            <div id="main-content">
                <div class="page-heading">
                    <div class="page-title">
                        <div class="row">
                            <div class="col-12 col-md-6 order-md-1 order-last">
                                <h3>Voting Results</h3>
                            </div>
                        </div>
                    </div>
                    <?php
                    // Run the query to get voting results
                    $sql = "SELECT employee_id, employee.Employee_Name, COUNT(*) AS total_votes 
                            FROM (
                                SELECT Group_Vote_ID AS employee_id FROM voting 
                                UNION ALL 
                                SELECT All_Vote_ID AS employee_id FROM voting
                            ) AS votes 
                            JOIN employee ON votes.employee_id = employee.ID 
                            GROUP BY employee_id 
                            ORDER BY total_votes DESC;";

                    $result = $conn->query($sql);
                    ?>


                    <!-- Results Display Start -->
                    <section class="section mt-3">
                        <div class="row justify-content-center mb-4">
                            <?php
                            $highestVotes = 0;
                            $topVoters = [];

                            // Find the highest vote count
                            while ($row = $result->fetch_assoc()) {
                                if ($row['total_votes'] > $highestVotes) {
                                    $highestVotes = $row['total_votes'];
                                    $topVoters = [$row]; // Reset the top voters list with the new highest
                                } elseif ($row['total_votes'] == $highestVotes) {
                                    $topVoters[] = $row; // Add to top voters if vote count matches
                                }
                            }

                            // Display the top voters
                            if (count($topVoters) > 0) {
                                foreach ($topVoters as $topVoter) {
                            ?>
                                    <div class="col-md-6">
                                        <div class="card mb-4 border-warning shadow-lg">
                                            <div class="card-body text-center">
                                                <h5 class="card-title text-warning">
                                                    <?php echo htmlspecialchars($topVoter['Employee_Name']); ?>
                                                </h5>
                                                <p class="card-text fw-bold">
                                                    Total Votes: <?php echo htmlspecialchars($topVoter['total_votes']); ?>
                                                </p>
                                                <span class="badge bg-warning text-dark">Top Voted</span>
                                            </div>
                                        </div>
                                    </div>
                            <?php
                                }
                            }
                            ?>
                        </div>

                        <div class="row">
                            <?php
                            // Re-run the query to display the other results
                            $result->data_seek(0); // Reset result pointer
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    // Skip the top voters since they are already displayed
                                    if ($row['total_votes'] == $highestVotes) {
                                        continue;
                                    }
                            ?>
                                    <div class="col-md-4">
                                        <div class="card mb-4">
                                            <div class="card-body text-center">
                                                <h5 class="card-title">
                                                    <?php echo htmlspecialchars($row['Employee_Name']); ?>
                                                </h5>
                                                <p class="card-text">
                                                    Total Votes: <?php echo htmlspecialchars($row['total_votes']); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php
                                }
                            } else {
                                ?>
                                <div class="col-12">
                                    <div class="alert alert-info">No results found</div>
                                </div>
                            <?php
                            }
                            ?>
                        </div>
                    </section>
                    <!-- Results Display End -->

                </div>
                <?php require_once($_SERVER['DOCUMENT_ROOT'] . '/static/footer.php'); ?>
            </div>
        </div>

    </div>

    <?php require_once($_SERVER['DOCUMENT_ROOT'] . '/static/script.php');
    ?>
</body>