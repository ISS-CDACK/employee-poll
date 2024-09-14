<?php
// Block direct access to the file
if ($_SERVER['REQUEST_METHOD'] == 'GET' && realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(404);
    include($_SERVER["DOCUMENT_ROOT"] . "/404.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    require_once($_SERVER['DOCUMENT_ROOT'] . '/session.php');

    // Check if the user is logged in
    if (!$_loginInfo || ($_loginInfo['role'] !== 'Admin' && $_loginInfo['role'] !== 'Operator')) {
        http_response_code(404);
        include($_SERVER["DOCUMENT_ROOT"] . "/404.html");
        exit();
    }
    else {
        require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
        header('Content-Type: application/json');
        // Process POST data if needed
        $input = json_decode(file_get_contents('php://input'), true);

        // Your SQL query remains the same
        $query = "SELECT g.Group_Name, COUNT(e.ID) AS Total_Users, 
                COUNT(CASE WHEN e.isActive = 'true' THEN 1 END) AS Active_Users,
                COUNT(CASE WHEN e.isActive = 'false' OR e.isActive IS NULL THEN 1 END) AS Inactive_Users
                FROM groups g 
                LEFT JOIN employee e ON g.Group_ID = e.Group_ID 
                GROUP BY g.Group_ID, g.Group_Name";

        $result = $conn->query($query);
        $data = [];

        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        echo json_encode($data);
    }
}
