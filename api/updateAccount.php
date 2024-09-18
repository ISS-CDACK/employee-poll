<?php
// Block direct access to the file
if ($_SERVER['REQUEST_METHOD'] === 'GET' && realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(404);
    include($_SERVER["DOCUMENT_ROOT"] . "/404.html");
    exit();
}

// Check if state and UID are provided in the POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['UID']) && isset($_POST['state'])) {
    
    // Check if the user is logged in
    require_once($_SERVER['DOCUMENT_ROOT'] . '/session.php');
    if (!$_loginInfo || ($_loginInfo['role'] !== 'Admin' && $_loginInfo['role'] !== 'Operator')) {
        http_response_code(404);
        include($_SERVER["DOCUMENT_ROOT"] . "/404.html");
        exit();
    }

    // Include necessary files
    require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
    require_once($_SERVER['DOCUMENT_ROOT'] . '/functions.php');

    // Extract and validate inputs
    $UID = decryptData($_POST['UID'], $key);;
    $state = isset($_POST['state']) ? $_POST['state'] : null;

    // Validate UID and state
    if (is_numeric($UID) && filter_var($UID, FILTER_VALIDATE_INT) !== false && ($state === 'true' || $state === 'false'))  {
        // Prepare and execute the SQL query
        $stmt = $conn->prepare("UPDATE employee SET isActive = ? WHERE ID = ?");
        $stmt->bind_param("si", $state, $UID);

        if ($stmt->execute()) {
            // If the query was successful, return a success response
            $response = [
                'status' => 'success',
                'message' => 'Account status updated successfully'
            ];
        } else {
            // If there was an error executing the query, return an error response
            $response = [
                'status' => 'error',
                'message' => 'Failed to update account status'
            ];
        }

        // Close the statement
        $stmt->close();
    } else {
        $response = [
            'status' => 'error',
            'message' => 'Failed to update account status1'
        ];
    }
    // Return the JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
}
