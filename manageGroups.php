<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/static/head.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');

$remark = ["status" => true];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['groupID'])) {
    try {
        $sql = "DELETE FROM `groups` WHERE Group_ID=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $_POST['groupID']);
        if ($stmt->execute()) {
            $remark = ["status" => true, "type" => "success", "message" => "Group Deleted Successfully."];
        }
        $stmt->close();
    } catch (Exception $e) {
        handle_error($e);
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['uploadExcel'])) {
    // File size validation (less than 10MB)
    if ($_FILES['excelFile']['size'] > 10485760) {
        $remark = ["status" => false, "type" => "error", "message" => "File size exceeds the 10MB limit."];
    } else {
        $fileExtension = strtolower(pathinfo($_FILES['excelFile']['name'], PATHINFO_EXTENSION));
        $validExtensions = ['xls', 'xlsx', 'csv', 'tsv'];

        // File extension validation
        if (!in_array($fileExtension, $validExtensions)) {
            $remark = ["status" => false, "type" => "delete", "message" => "Invalid file extension."];
        } else {
            $fileMagicNumber = file_get_contents($_FILES['excelFile']['tmp_name'], false, null, 0, 4);
            $mimeType = mime_content_type($_FILES['excelFile']['tmp_name']);

            // MIME type validation
            $validMimeTypes = [
                'application/vnd.ms-excel', // xls
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // xlsx
                'text/csv', // csv
                'text/tab-separated-values' // tsv
            ];
            if (!in_array($mimeType, $validMimeTypes)) {
                $remark = ["status" => false, "type" => "error", "message" => "Invalid file type."];
            } else {
                // Check magic number (optional, depends on file type)
                $validMagicNumbers = [
                    'D0CF11E0', // Excel 97-2003 format (xls)
                    '504B0304'  // Excel 2007+ format (xlsx)
                ];
                if ($fileExtension != 'csv' && $fileExtension != 'tsv' && !in_array(strtoupper(bin2hex($fileMagicNumber)), $validMagicNumbers)) {
                    $remark = ["status" => false, "type" => "delete", "message" => "Invalid file content."];
                } else {
                    // All validations passed, proceed with file upload
                    $randNamePart = bin2hex(random_bytes(5));
                    $newFileName = 'File_' . $randNamePart . '.' . $fileExtension;
                    $newFilePath = $_SERVER["DOCUMENT_ROOT"] . '/' . $newFileName;
                    if (move_uploaded_file($_FILES['excelFile']['tmp_name'], $newFilePath)) {
                        if ($fileExtension == 'xlsx' || $fileExtension == 'xls') {
                            // Include the PHPSpreadsheet autoload file
                            require_once $_SERVER['DOCUMENT_ROOT'] . '/assets/vendor/libs/php/autoload.php';

                            // Create a reader for the Excel file
                            $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($newFilePath);
                            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
                            $spreadsheet = $reader->load($newFilePath);

                            // Get the first sheet
                            $worksheet = $spreadsheet->getActiveSheet();
                            $rowData = $worksheet->toArray();
                        } else {
                            // Read CSV or TSV file
                            $rowData = [];
                            $delimiter = ($fileExtension == 'csv') ? ',' : "\t";
                            if (($handle = fopen($newFilePath, 'r')) !== false) {
                                while (($data = fgetcsv($handle, 1000, $delimiter)) !== false) {
                                    $rowData[] = $data;
                                }
                                fclose($handle);
                            }
                        }

                        // Skip the header row
                        array_shift($rowData);

                        // Prepare SQL for checking existing values
                        $stmtCheck = $conn->prepare("SELECT COUNT(*) as count FROM `groups` WHERE `Group_Name` = ?");

                        // Prepare SQL for inserting new values
                        $stmtInsert = $conn->prepare("INSERT INTO `groups`(`Group_Name`) VALUES (?)");

                        foreach ($rowData as $row) {
                            $name = htmlspecialchars(trim($row[1]), ENT_QUOTES, 'UTF-8'); // Assuming name is in the first column

                            // Check if value already exists in the database
                            $stmtCheck->bind_param("s", $name);
                            $stmtCheck->execute();
                            $result = $stmtCheck->get_result();
                            $exists = $result->fetch_assoc()['count'];

                            if ($exists == 0) {
                                // Insert new value if it does not exist
                                $stmtInsert->bind_param("s", $name);
                                $stmtInsert->execute();
                            }
                        }

                        // Delete the uploaded file
                        unlink($newFilePath);

                        $remark = ["status" => true, "type" => "success", "message" => "File Imported Successfully"];
                    } else {
                        $remark = ["status" => false, "type" => "error", "message" => "Failed to upload file."];
                    }
                }
            }
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['editGroup']) && isset($_POST['setName'])) {
    if (isset($_POST['groupName'])) {
        $group = $_POST['groupName'];
    } else {
        $group = '';
    }

    if (strlen($group) <= 1) {
        $remark = ["status" => false, "type" => 'warning', "message" => "Please enter a group name for update."];
    }

    if ($remark['status']) {
        try {
            $sql = "UPDATE `groups` SET `Group_Name`=? WHERE Group_ID=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $group, $_POST['setName']);
            if ($stmt->execute()) {
                $remark = ["status" => true, "type" => 'success', "message" => "Group Name Updated Successfully."];
            }
            $stmt->close();
        } catch (Exception $e) {
            if ($conn->errno === 1062) {
                // Handle duplicate entry error
                $remark = ["status" => false, "type" => 'error', "message" => "Duplicate Group Name. Please choose another name."];
            } else {
                handle_error($e);
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['addNewGroup'])) {
    if (isset($_POST['groupName'])) {
        $group = $_POST['groupName'];
    } else {
        $group = '';
    }

    if (strlen($group) <= 1) {
        $remark = ["status" => false,  "type" => 'warning', "message" => "Please enter a group name for group creation."];
    }

    if ($remark['status']) {
        try {
            $sql = "INSERT INTO `groups`(`Group_Name`) VALUES (?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $group);
            if ($stmt->execute()) {
                $remark = ["status" => true, "type" => "success", "message" => "New Group Added Successfully."];
            }
            $stmt->close();
        } catch (Exception $e) {
            if ($conn->errno === 1062) {
                // Handle duplicate entry error
                $remark = ["status" => false, "type" => 'error', "message" => "Duplicate Group Name. Please choose another name."];
            } else {
                handle_error($e);
            }
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
                                <h3>All Groups List</h3>
                                <!-- <p class="text-subtitle text-muted">Navbar will appear on the top of the page.</p> -->
                                <!-- <li class="breadcrumb-item"><a href="index.html">Dashboard</a></li> -->
                                <!-- <li class="breadcrumb-item active" aria-current="page">Layout Vertical Navbar</li> -->
                            </div>
                            <div class="col-12 col-md-6 order-md-2 order-first">
                                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                                    <ol class="breadcrumb">
                                        <button class="btn btn-primary cursor-pointer me-2" onclick="showAddGroupModal()">Add New Group</button>
                                        <button class="btn btn-primary cursor-pointer ms-2" onclick="showExcelModal()">Import New Group(s)</button>
                                    </ol>
                                </nav>
                            </div>
                        </div>
                    </div>
                    <?php
                    $sql = "SELECT g.Group_ID, g.Group_Name, COUNT(e.ID) AS `User_Count` FROM `groups` g LEFT JOIN `employee` e ON g.Group_ID = e.Group_ID GROUP BY g.Group_ID, g.Group_Name;";
                    $result = $conn->query($sql);
                    ?>
                    <!-- Responsive tables start -->
                    <section class="section">
                        <div class="row" id="table-responsive">
                            <div class="col-12 mt-2">
                                <div class="card">
                                    <div class="card-content">
                                        <?php if ($result->num_rows == 0) { ?>
                                            <div class="card-body">
                                                <div class="alert alert-primary">
                                                    <h4 class="alert-heading">No Groups Found</h4>
                                                    <p>No data available to display in the table. To add a new group, <a class="cursor-pointer" onclick="showAddGroupModal()">click here</a>.</p>
                                                </div>
                                            </div>
                                        <?php } else { ?>
                                            <!-- table responsive -->
                                            <div class="table-responsive">
                                                <table class="table mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th scope="col" class='text-center'>SL No.</th>
                                                            <th scope="col" class='text-center'>Group Name</th>
                                                            <th scope="col" class='text-center'>User Count</th>
                                                            <th scope="col" class='text-center'>Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        $counter = 1; // Initialize the counter
                                                        // Loop through the results and output a row for each group
                                                        while ($row = $result->fetch_assoc()) {
                                                            echo "<tr>";
                                                            echo "<td class='text-center'>" . $counter++ . "</td>"; // Display the counter and increment it
                                                            echo "<td class='text-center'>" . htmlspecialchars($row['Group_Name']) . "</td>";
                                                            echo "<td class='text-center'>" . htmlspecialchars($row['User_Count']) . "</td>";
                                                            echo "<td class='text-center'>";
                                                            echo '<div class="btn-group" role="group">';
                                                            echo '<button type="button" class="btn btn-sm btn-warning me-1" onclick="editGroup(' . $row['Group_ID'] . ', \'' . $row['Group_Name'] . '\')">';
                                                            echo '<span class="bi bi-pencil"></span>';
                                                            echo '</button>';
                                                            echo '<button type="button" class="btn btn-sm btn-danger ms-1" onclick="deleteGroup(' . $row['Group_ID'] . ')">';
                                                            echo '<span class="bi bi-trash"></span>';
                                                            echo '</button>';
                                                            echo '</div>';
                                                            echo "</td>";
                                                            echo "</tr>";
                                                        }
                                                        ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                    <!-- Responsive tables end -->
                </div>
                <?php require_once($_SERVER['DOCUMENT_ROOT'] . '/static/footer.php'); ?>
            </div>
        </div>

        <div class="modal fade modal-borderless" id="modal-add-group" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <form id="addNewGroup" method="post" action="<?php echo pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME); ?>">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modal-title">Add New Group</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col mb-3">
                                    <label for="groupName" class="form-label">Group Name</label>
                                    <input type="text" id="groupName" name="groupName" class="form-control" placeholder="Enter group name" required autocomplete="off" />
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <input type="hidden" id="setName" name="setName">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" onclick="groupCancel()">Cancel</button>
                            <button type="submit" id="addGroup" class="btn btn-primary">Add New Group</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>


        <div class="modal fade modal-borderless" id="modal-upload-excel" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
                <form id="uploadExcelForm" name="uploadExcelForm" method="POST" enctype="multipart/form-data" action="<?php echo pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME); ?>">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modal-title">Upload Excel File</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="excelCancel()"></button>
                        </div>
                        <div class="modal-body mb-0">
                            <div class="row">
                                <div class="col mb-1">
                                    <label for="excelFile" class="form-label">Select Excel File</label>
                                    <input id="excelFile" name="excelFile" class="form-control" type="file" placeholder="Please select an Excel file" aria-describedby="excelFile" accept=".xls,.xlsx,.csv,.tsv" required />
                                    <input type="hidden" name="uploadExcel" value="true">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col mb-1">
                                    <a href="/assets/Excel Template/GroupTemplate.xlsx" download>Download Excel Template</a>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer mt-1">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" onclick="excelCancel()">Cancel</button>
                            <button type="submit" id="uploadExcel" class="btn btn-primary">Upload Excel File</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>


    </div>

    <?php require_once($_SERVER['DOCUMENT_ROOT'] . '/static/script.php');
    if (isset($remark['status']) && isset($remark['type'])) {
        echo "<script type='text/javascript'>showToast(5000, '" . $remark['type'] . "', '" . $remark['message'] . "');</script>";
    }
    ?>
</body>