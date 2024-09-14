<?php
if ($_SERVER['REQUEST_METHOD'] == 'GET' && realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(404);
    include($_SERVER["DOCUMENT_ROOT"] . "/404.html");
    exit();
}

define('AES_256_CBC', 'aes-256-cbc');
function loginSimple($email, $password)
{
    global $conn;
    try {
        $sql = "SELECT e.ID, e.Employee_Name, e.isGroupHead, e.email, e.Group_ID, g.Group_Name, e.authType, e.role, e.password, e.displayImg, e.isActive 
                FROM employee e 
                LEFT JOIN groups g ON e.Group_ID = g.Group_ID 
                WHERE e.email = ?;";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            $hashedPassword = $row['password'];
            
            if (password_verify($password, $hashedPassword)) {
                if ($row['isActive'] === 'true') {
                    insertLog($conn, $row['ID'], 'Login Success');
                    $user = array(
                        'status' => true,
                        'message' => 'Login Success',
                        'data' => array(
                            'uid' => $row['ID'],
                            'username' => $row['Employee_Name'],
                            'email' => $row['email'],
                            'isHead' => $row['isGroupHead'] !== null ? $row['isGroupHead'] : 'None',
                            'groupId' => $row['Group_ID'] !== null ? $row['Group_ID'] : 'None',
                            'groupName' => $row['Group_Name'],
                            'role' => $row['role'],
                            'authType' => $row['authType'],
                            'active' => $row['isActive'],
                            'dp' => $row['displayImg'],
                        )
                    );
                    return $user;
                } else {
                    insertLog($conn, $row['ID'], 'Login Attempted While User Not Activated');
                    return array('status' => false, 'message' => 'User not activated');
                }
            } else {
                insertLog($conn, $row['ID'], 'Invalid login Attempt');
                return array('status' => false, 'message' => 'Invalid email or password');
            }
        } else {
            return array('status' => false, 'message' => 'Invalid email or password');
        }
    } catch (Exception $e) {
        handle_error($e);
        return array('status' => false, 'message' => 'Error during login');
    }
}


// Function to encrypt data
function encryptData($data, $encryption_key)
{
    // Generate an initialization vector
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(AES_256_CBC));

    // Encrypt data
    $encrypted = openssl_encrypt($data, AES_256_CBC, $encryption_key, 0, $iv);

    // Append a separator and base64-encoded initialization vector
    return $encrypted . ':' . base64_encode($iv);
}

// Function to decrypt data
function decryptData($encrypted_data, $encryption_key)
{
    // Define cipher method
    $cipher_method = 'AES-256-CBC';

    // Separate encrypted data and base64-encoded initialization vector
    $parts = explode(':', $encrypted_data);
    $encrypted = $parts[0];
    $iv = base64_decode($parts[1]);

    // Ensure the IV is the correct length
    if (strlen($iv) !== openssl_cipher_iv_length($cipher_method)) {
        $iv = str_pad($iv, openssl_cipher_iv_length($cipher_method), "\0");
    }

    // Decrypt data
    $decrypted = openssl_decrypt($encrypted, $cipher_method, $encryption_key, 0, $iv);

    if ($decrypted === false) {
        // Handle decryption failure
        return 'Decryption failed';
    }
    return $decrypted;
}


// Function to handle LDAP user login
function loginLDAP($email, $password)
{
    global $ldap_hostname, $ldapPort, $ldap_protocol, $ldap_rootDN, $ldap_root_password, $ldapBaseDn, $ldap_filter, $ldap_uft8;

    $ldapconn = ldap_connect($ldap_hostname, $ldapPort);
    ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, $ldap_protocol);
    ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);

    if ($ldap_uft8) {
        $email = mb_convert_encoding($email, 'UTF-8', mb_detect_encoding($email));
        $password = mb_convert_encoding($password, 'UTF-8', mb_detect_encoding($password));
    }

    if ($ldapconn) {
        if (@ldap_bind($ldapconn, $ldap_rootDN, $ldap_root_password)) {
            $searchResults = ldap_search($ldapconn, $ldapBaseDn, $ldap_filter);
            $entries = ldap_get_entries($ldapconn, $searchResults);

            if ($entries['count'] > 0) {
                $empID = findArrayElement($entries, $email);
                $ldapUserDN = $entries[$empID]['dn'];

                if (@ldap_bind($ldapconn, $ldapUserDN, $password)) {
                    $userMail = $entries[$empID]['mail'][0];
                    $givenName = $entries[$empID]['givenname'][0];
                    $ldapOutput = array(
                        "status" => true,
                        "userMail" => $userMail,
                        "givenName" => $givenName
                    );

                    ldap_unbind($ldapconn); // Unbind after successful authentication
                    return $ldapOutput;
                } else {
                    ldap_unbind($ldapconn); // Unbind on authentication failure
                    return array('status' => false, 'message' => 'LDAP authentication failed');
                }
            }
        }

        ldap_unbind($ldapconn); // Unbind if initial bind fails
    }

    return array('status' => false, 'message' => 'LDAP connection failed');
}

function findArrayElement($array, $email)
{
    foreach ($array as $key => $element) {
        if (isset($element["mail"]["count"]) && $element["mail"]["count"] === 1 && $element["mail"][0] === $email) {
            return $key;
        }
    }
    return false;
}

function insertLog($conn, $userId, $remark)
{
    try {
        if ($remark == 'Login Success') {
            // Generate timestamp with custom suffix
            $currentTs = time() . '+cdac_voting_portal_timestmap_hash';
            // Generate hash for timestamp
            $tsHash = hash('crc32b', $currentTs);
            $sql = "INSERT INTO logs__auth (users_id, ts_hash, remark) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iss", $userId, $tsHash, $remark);
            $success = $stmt->execute();
            $stmt->close();
            if ($success) {
                return array('status' => true, 'hash' => $tsHash);
            } else {
                return array('status' => false);
            }
        } else {
            $sql = "INSERT INTO logs__auth (users_id, remark) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $userId, $remark);
            $success = $stmt->execute();
            $stmt->close();
        }
        return $success ? true : false;
    } catch (Exception $e) {
        handle_error($e);
    }
}

// Function to list all LDAP users
function listLDAPUsers()
{
    global $ldap_hostname, $ldapPort, $ldap_protocol, $ldap_rootDN, $ldap_root_password, $ldapBaseDn;
    
    // Adjusted LDAP filter to include only those entries where 'centre' is 'KL'
    $ldap_filter = '(centre=KL)';
    
    $ldapconn = ldap_connect($ldap_hostname, $ldapPort);
    ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, $ldap_protocol);
    ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);
    
    if ($ldapconn) {
        if (@ldap_bind($ldapconn, $ldap_rootDN, $ldap_root_password)) {
            // Perform search to retrieve all entries based on the given filter
            $searchResults = ldap_search($ldapconn, $ldapBaseDn, $ldap_filter);
            $entries = ldap_get_entries($ldapconn, $searchResults);
            
            if ($entries['count'] > 0) {
                $ldapUsers = array();
                
                for ($i = 0; $i < $entries['count']; $i++) {
                    // Retrieve attributes
                    $userMail = $entries[$i]['mail'][0] ?? null;
                    $givenName = $entries[$i]['givenname'][0] ?? null;
                    $centre = $entries[$i]['centre'][0] ?? null;

                    // Only include users if 'centre' is 'KL' and all 'givenName', 'userMail', and 'centre' are not null
                    if ($centre === 'KL' && $userMail !== null && $givenName !== null && $centre !== null) {
                        $ldapUsers[] = array(
                            "userMail" => $userMail,
                            "givenName" => $givenName,
                            "centre" => $centre,
                        );
                    }
                }
                
                ldap_unbind($ldapconn); // Unbind after successful retrieval
                
                if (!empty($ldapUsers)) {
                    return array('status' => true, 'users' => $ldapUsers);
                } else {
                    return array('status' => false, 'message' => 'No LDAP users found');
                }
            } else {
                ldap_unbind($ldapconn); // Unbind if no entries are found
                return array('status' => false, 'message' => 'No LDAP users found');
            }
        }
        ldap_unbind($ldapconn); // Unbind if initial bind fails
    }
    
    return array('status' => false, 'message' => 'LDAP connection failed');
}

