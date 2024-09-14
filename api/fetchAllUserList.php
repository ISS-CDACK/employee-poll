<?php
if ($_SERVER['REQUEST_METHOD'] == 'GET' && realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(404);
    include($_SERVER["DOCUMENT_ROOT"] . "/404.html");
    exit();
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read and decode the JSON data from the request body
    $rawData = file_get_contents('php://input');
    $data = json_decode($rawData, true);

    // Check for JSON decoding errors
    if (json_last_error() === JSON_ERROR_NONE) {
        $groupId = isset($data['groupId']) ? $data['groupId'] : null;

        if ($groupId !== null && filter_var($groupId, FILTER_VALIDATE_INT) !== false) {
            require_once($_SERVER['DOCUMENT_ROOT'] . '/session.php');
            // Check if the user is logged in
            if (!$_loginInfo || ($_loginInfo['role'] !== 'Admin' && $_loginInfo['role'] !== 'Operator')) {
                http_response_code(404);
                include($_SERVER["DOCUMENT_ROOT"] . "/404.html");
                exit();
            }

            // Include database configuration
            require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');

            // Prepare the SQL query to prevent SQL injection
            $sql = "SELECT `ID`, `Employee_Name`, `email`, `role`, `Group_ID`, `isGroupHead` FROM `employee` WHERE `role` = 'user' ORDER BY `isGroupHead` DESC, `Employee_Name` ASC;";
            $stmt = $conn->prepare($sql);
            $stmt->execute();

            // Fetch the results
            $result = $stmt->get_result();
            $empNames = [];

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $empNames[] = [
                        "value" => $row['ID'], // Use email as value
                        "label" => $row['Employee_Name'] . " (" . $row['email'] . ")",
                        "isGroupMember" => ($row['Group_ID'] == $groupId) ? true : false,
                        "isGroupHead" => ($row['Group_ID'] == $groupId && $row['isGroupHead'] === "true") ? true : false,
                    ];
                }
            }

            // Send a JSON response
            header("Content-Type: application/json; charset=UTF-8");
            echo json_encode($empNames);
        }
    }
}
