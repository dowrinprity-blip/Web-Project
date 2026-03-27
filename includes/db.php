<?php
require_once __DIR__ . '/config.php';

// Create connection
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}

// Global connection
$conn = getDBConnection();

function checkDatabaseExists() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    $result = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'");
    return $result->num_rows > 0;
}
?>