<?php

if ($_SERVER['REQUEST_METHOD'] == 'GET' && realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(404);
    include($_SERVER["DOCUMENT_ROOT"]."/404.html");
    exit();
}


$servername = "localhost";
$username = "root";
$password = "";
$database = "cdac_emp_voting";
$debug_mode = true;


// Create connection
$conn = new mysqli($servername, $username, $password, $database);
// Check connection
// if ($conn->connect_error) {
    // die("Connection failed: " . $conn->connect_error);
// }
// echo "Connected successfully";


$key = "IZLcSd3iAUWaqAAk1zY1jIFjJXlSOU/sCVgrE1X5y5E=";

// This part is not required when ldap connection is not in use
$ldap_hostname = "central.ds.cdac.in";
$ldapBaseDn = "ou=User,dc=cdac,dc=in";
$ldapPort = 389;
$ldap_protocol = 3;
$ldap_rootDN = null; // The DN for the ROOT Account Set to null for anonymous LDAP binding
$ldap_root_password = null;
$ldap_uft8 = true;

$ldap_filter = '(objectClass=*)';


function handle_error($e)
{
    global $debug_mode;
    if ($debug_mode) {
        die('debug: ' . $e->getMessage());
    } else {
        echo 'error';
        die();
    }
}
