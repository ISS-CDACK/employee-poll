<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/static/head.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/functions.php');

$remark = ["status" => true];

$LDAPResult = listLDAPUsers();

// Initialize a counter for successful inserts
$successCount = 0;

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Check if the 'users' input exists and is not empty
    if (isset($_POST['users']) && !empty(trim($_POST['users'])) && !empty(trim($_POST['groupId']))) {

        // Get the list of users from the form input
        $userArray = explode(',', $_POST['users']);
        $groupId = trim($_POST['groupId']);
        $groupPresent = false;

        if ($groupId === 'false') {
            $groupPresent = true;
        } elseif (is_numeric($groupId) && (int)$groupId == $groupId) {
            $sql = "SELECT COUNT(*) AS `count` FROM `groups` WHERE `Group_ID` = ?";
            $stmt = $conn->prepare($sql);

            // Bind the groupId as an integer parameter
            $stmt->bind_param("i", $groupId);
            $stmt->execute();

            // Get the result
            $result = $stmt->get_result();

            // Fetch the count from the result
            $row = $result->fetch_assoc();

            // Check if the count is 1
            if ($row['count'] == 1) {
                $groupPresent = true;
            } else {
                $groupPresent = false;
            }

            $stmt->close();
        }

        if ($groupPresent) {
            foreach ($userArray as $user) {
                $user = trim($user); // Trim any whitespace

                // Check if $user exists in LDAPResult as userMail
                $userExists = false;
                foreach ($LDAPResult['users'] as $ldapUser) {
                    if ($ldapUser['userMail'] === $user) {
                        $userExists = true;
                        $userName = $ldapUser['givenName']; // Get the name from LDAP
                        $userEmail = $ldapUser['userMail'];
                        break;
                    }
                }

                if ($userExists) {
                    if ($groupId === 'false') {
                        $sql = "INSERT IGNORE INTO employee (Employee_Name, email) VALUES (?, ?)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("ss", $userName, $userEmail);
                    } else {
                        $sql = "INSERT IGNORE INTO employee (Employee_Name, email, `Group_ID`) VALUES (?, ?, ?)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("ssi", $userName, $userEmail, $groupId);
                    }

                    if ($stmt->execute()) {
                        // Increment the counter on successful insert
                        if ($stmt->affected_rows > 0) {
                            $remark = ["status" => true, "type" => 'success', "message" => "Employee inserted successfully."];
                            $successCount++;
                        }
                    }
                    $stmt->close();
                }
            }
        }
    }


    if (isset($_POST['uploadExcel']) && isset($_POST['groupId'])) {


        $groupId = trim($_POST['groupId']);
        $groupPresent = false;

        if ($groupId === 'false') {
            $groupPresent = true;
        } elseif (is_numeric($groupId) && (int)$groupId == $groupId) {
            $sql = "SELECT COUNT(*) AS `count` FROM `groups` WHERE `Group_ID` = ?";
            $stmt = $conn->prepare($sql);

            // Bind the groupId as an integer parameter
            $stmt->bind_param("i", $groupId);
            $stmt->execute();

            // Get the result
            $result = $stmt->get_result();

            // Fetch the count from the result
            $row = $result->fetch_assoc();

            // Check if the count is 1
            if ($row['count'] == 1) {
                $groupPresent = true;
            } else {
                $groupPresent = false;
            }

            $stmt->close();
        }

        if ($groupPresent) {
            // File size validation (less than 10MB)
            if ($_FILES['excelFile']['size'] > 10485760) {
                $remark = ["status" => false, "type" => "error", "message" => "File size exceeds the 10MB limit."];
            } else {
                $fileExtension = strtolower(pathinfo($_FILES['excelFile']['name'], PATHINFO_EXTENSION));
                $validExtensions = ['xls', 'xlsx', 'csv', 'tsv'];

                // File extension validation
                if (!in_array($fileExtension, $validExtensions)) {
                    $remark = ["status" => false, "type" => "error", "message" => "Invalid file extension."];
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
                            $remark = ["status" => false, "type" => "error", "message" => "Invalid file content."];
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

                                // Prepare SQL for inserting new values, ignoring duplicates
                                $sql = $groupId != 'false'
                                    ? "INSERT IGNORE INTO `employee` (`Employee_Name`, `email`, `authType`, `Group_ID`) VALUES (?, ?, ?, ?)"
                                    : "INSERT IGNORE INTO `employee` (`Employee_Name`, `email`, `authType`) VALUES (?, ?, ?)";

                                $stmtInsert = $conn->prepare($sql);

                                foreach ($rowData as $row) {
                                    $name = htmlspecialchars(trim($row[1]), ENT_QUOTES, 'UTF-8'); // Assuming user name is in the first column
                                    $email = htmlspecialchars(trim($row[2]), ENT_QUOTES, 'UTF-8'); // Assuming user email is in the second column

                                    // Validate email format
                                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                        continue; // Skip invalid emails
                                    }

                                    // Ensure name is not blank
                                    if (empty($name)) {
                                        continue; // Skip rows with empty names
                                    }

                                    // Determine authType based on email domain
                                    $authType = (preg_match('/@cdac\.in$/', $email)) ? 'ldap' : 'self';

                                    if ($groupId != 'false') {
                                        // Bind parameters for the query with Group_ID
                                        $stmtInsert->bind_param("sssi", $name, $email, $authType, $groupId);
                                    } else {
                                        // Bind parameters for the query without Group_ID
                                        $stmtInsert->bind_param("sss", $name, $email, $authType);
                                    }

                                    $stmtInsert->execute();
                                }

                                // Close the prepared statement
                                $stmtInsert->close();

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
                                <h3>Import Users</h3>
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
                    // Step 1: Fetch all emails from the database
                    $query = "SELECT email FROM employee";
                    $employeeEmailsResult = $conn->query($query);

                    $employeeEmails = [];
                    if ($employeeEmailsResult && $employeeEmailsResult->num_rows > 0) {
                        while ($row = $employeeEmailsResult->fetch_assoc()) {
                            $employeeEmails[] = $row['email'];
                        }
                    }

                    // Check if $LDAPResult is valid and has users
                    if (isset($LDAPResult['status']) && $LDAPResult['status'] === true && isset($LDAPResult['users']) && is_array($LDAPResult['users'])) {
                        // Step 3: Filter LDAP users who are not in the database
                        $filteredUsers = [];
                        foreach ($LDAPResult['users'] as $ldapUser) {
                            if (isset($ldapUser['userMail']) && !in_array($ldapUser['userMail'], $employeeEmails)) {
                                $filteredUsers[] = $ldapUser;
                            }
                        }
                    } else {
                        // Handle the case where LDAP data is not as expected
                        echo "Error or no valid LDAP users found.";
                        die();
                    }

                    // Step 4: Display filtered LDAP users
                    ?>

                    <section class="section">
                        <div class="card mt-4">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table" id="ldapDataTable">
                                        <thead>
                                            <tr>
                                                <th class='text-center'><input type='checkbox' class='form-check-input form-check-secondary' id='selectAll'></th>
                                                <th class='text-center'>SL No.</th>
                                                <th class='text-center'>Name</th>
                                                <th class='text-center'>Email</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if (!empty($filteredUsers)) {
                                                $sl_no = 1;
                                                foreach ($filteredUsers as $user) {
                                                    echo "<tr>";
                                                    echo "<td class='text-center'><input type='checkbox' class='user-checkbox form-check-input form-check-secondary' value='" . htmlspecialchars($user['userMail']) . "'></td>";
                                                    echo "<td class='text-center'>" . $sl_no++ . "</td>";
                                                    echo "<td class='text-center'>" . htmlspecialchars($user['givenName']) . "</td>";
                                                    echo "<td class='text-center'>" . htmlspecialchars($user['userMail']) . "</td>";
                                                    echo "</tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='5' class='text-center'>No records found</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                                <form id="importUsersForm" action="<?php echo pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME); ?>" method="POST">
                                    <input type="hidden" name="users" id="usersInput">
                                    <input type="hidden" name="groupId" id="groupId">
                                </form>
                                <div class="d-flex justify-content-end mb-0 mt-4">
                                    <button id="logCheckedItems" class="btn btn-primary me-2">Import Selected User</button>
                                    <button class="btn btn-primary cursor-pointer ms-2" onclick="showExcelModal()">Import From Excel</button>
                                </div>
                            </div>
                        </div>
                    </section>


                </div>
                <?php require_once($_SERVER['DOCUMENT_ROOT'] . '/static/footer.php'); ?>
            </div>
        </div>

        <?php
        $query = "SELECT Group_ID, Group_Name FROM groups";
        $result = $conn->query($query);
        $groups = [];

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $groups[] = $row;
            }
        }
        ?>

        <div class="modal fade modal-borderless" id="modal-upload-excel" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
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
                            <div class="mb-2 mt-3">
                                <h6>Please Select A Group</h6>
                                <div class="form-group">
                                    <select class="choices form-select" id="group-select-2" name="groupId">
                                        <option value="false">Decide Letter</option>
                                        <?php foreach ($groups as $group): ?>
                                            <option value="<?= htmlspecialchars($group['Group_ID']) ?>">
                                                <?= htmlspecialchars($group['Group_Name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col mb-1">
                                    <a href="/assets/Excel Template/UserTemplate.xlsx" download>Download Excel Template</a>
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

        <div class="modal fade modal-borderless" id="modal-select-group" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Group Selector</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="selectGroupModalCancel()"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <h6>Please Select A Group</h6>
                            <div class="form-group">
                                <select class="choices form-select" id="group-select">
                                    <option value="false">Decide Letter</option>
                                    <?php foreach ($groups as $group): ?>
                                        <option value="<?= htmlspecialchars($group['Group_ID']) ?>">
                                            <?= htmlspecialchars($group['Group_Name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer mt-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="selectGroupModalCancel()">Close</button>
                        <button type="button" class="btn btn-primary" onclick="setGroupName()">Import Users</button>
                    </div>
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