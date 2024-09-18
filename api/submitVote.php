<?php
// Block direct access to the file
if ($_SERVER['REQUEST_METHOD'] == 'GET' && realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(404);
    include($_SERVER["DOCUMENT_ROOT"] . "/404.html");
    exit();
}

function validateAndConvertToInt($value) {
    // Check if value is a positive integer
    if (filter_var($value, FILTER_VALIDATE_INT, array('options' => array('min_range' => 1))) !== false) {
        return (int) $value; // Convert to integer
    } else {
        return false; // Return false if not a positive integer
    }
}

// Ensure the request is POST and contains the required data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ownGroupVotedEmployeeId']) && isset($_POST['otherGroupVotedEmployeeId'])) {
    
    // Validate and convert POST values
    $ownGroupVotedEmployeeId = validateAndConvertToInt($_POST['ownGroupVotedEmployeeId']);
    $otherGroupVotedEmployeeId = validateAndConvertToInt($_POST['otherGroupVotedEmployeeId']);

    // Check if validation was successful
    if ($ownGroupVotedEmployeeId == false or $otherGroupVotedEmployeeId == false) {
        echo json_encode(['success' => false, 'message' => 'Unexpected Values.']);
        exit();
    }

    require_once($_SERVER['DOCUMENT_ROOT'] . '/session.php');

    // Check if the user is logged in
    if (!$_loginInfo || $_loginInfo['role'] !== 'User' ) {
        http_response_code(404);
        include($_SERVER["DOCUMENT_ROOT"] . "/404.html");
        exit();
    }

    // Get the logged-in user's group and employee ID
    $userGroup = $_loginInfo['groupId'];
    $userEmpId = $_loginInfo['uid'];

    // Validate that both votes are submitted
    if (!$ownGroupVotedEmployeeId || !$otherGroupVotedEmployeeId) {
        echo json_encode(['success' => false, 'message' => 'Both votes are required.']);
        exit;
    }

    require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');

    // Validate the votes
    $validVote = true;
    $errors = [];

    // Prepare the SQL statement to validate the votes
    $sql = "SELECT e.ID, e.Group_ID, e.isGroupHead 
            FROM employee e 
            WHERE e.ID IN (?, ?) AND e.ID != ? AND (e.isGroupHead IS NULL OR e.isGroupHead = 'false')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iii', $ownGroupVotedEmployeeId, $otherGroupVotedEmployeeId, $userEmpId);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if the result has exactly 2 rows (one for each valid vote)
    if ($result->num_rows !== 2) {
        $validVote = false;
        $errors[] = 'Invalid votes or unauthorized attempt.';
    } else {
        $ownGroupFound = false;
        $otherGroupFound = false;
        $ownGroupVoteGroupId = null;
        $otherGroupVoteGroupId = null;
        
        while ($row = $result->fetch_assoc()) {
            if ($row['ID'] == $ownGroupVotedEmployeeId) {
                $ownGroupVoteGroupId = $row['Group_ID'];
                if ($row['Group_ID'] != $userGroup) {
                    $validVote = false;
                    $errors[] = 'The vote in your own group is invalid.';
                }
                $ownGroupFound = true;
            }
            if ($row['ID'] == $otherGroupVotedEmployeeId) {
                $otherGroupVoteGroupId = $row['Group_ID'];
                if ($row['Group_ID'] == $userGroup) {
                    $validVote = false;
                    $errors[] = 'The vote in another group is invalid.';
                }
                $otherGroupFound = true;
            }
        }

        // Ensure the votes are in different groups
        if ($ownGroupFound && $otherGroupFound && $ownGroupVoteGroupId == $otherGroupVoteGroupId) {
            $validVote = false;
            $errors[] = 'Both votes cannot be in the same group.';
        }
    }

    // Check if the employee has already been voted for
    if ($validVote) {
        $sql = "SELECT COUNT(*) AS vote_count FROM voting WHERE `Emp_ID` = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $userEmpId);
        $stmt->execute();
        $result = $stmt->get_result();
        $voteCount = $result->fetch_assoc()['vote_count'];

        if ($voteCount > 0) {
            $validVote = false;
            $errors[] = 'You have already voted';
        } else {
            // Insert votes into the database
            $sql = "INSERT INTO voting (Group_Vote_ID, All_Vote_ID, Emp_ID) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('iii', $ownGroupVotedEmployeeId, $otherGroupVotedEmployeeId, $userEmpId);
            $stmt->execute();
        }
    }

    // Provide the response
    if ($validVote) {
        echo json_encode(['success' => true, 'message' => 'Your votes have been submitted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    }

    // Close the statement and connection
    $stmt->close();
} else {
    // If the request method or required data is missing, show 404
    http_response_code(404);
    include($_SERVER["DOCUMENT_ROOT"] . "/404.html");
    exit();
}
