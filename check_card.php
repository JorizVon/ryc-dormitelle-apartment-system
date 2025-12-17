<?php
// Simple error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connect to database
require_once 'db_connect.php';

// Get parameters
$tag = isset($_GET['tag']) ? $_GET['tag'] : '';
$unit_no = isset($_GET['unit']) ? $_GET['unit'] : '';

// Check if parameters exist
if (empty($tag) || empty($unit_no)) {
    echo "MISSING_DATA";
    exit();
}

// Check database connection
if ($conn->connect_error) {
    echo "DB_ERROR";
    exit();
}

// Set timezone
date_default_timezone_set("Asia/Manila");
$current_time = date("Y-m-d H:i:s");

// Simple query to check card
$sql = "SELECT card_no, unit_no, tenant_ID 
        FROM card_registration 
        WHERE card_no = '$tag' 
        AND unit_no = '$unit_no' 
        AND card_status = 'Active'";

$result = $conn->query($sql);

if ($result === false) {
    echo "QUERY_ERROR";
    exit();
}

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $tenant_ID = $row['tenant_ID'];
    $card_no = $row['card_no'];
    
    // Try to log successful access
    $log_sql = "INSERT INTO access_logs (tenant_ID, card_no, date_and_time, access_status)
                VALUES ('$tenant_ID', '$card_no', '$current_time', 'Success')";
    
    $conn->query($log_sql); // Don't stop if logging fails
    
    echo "FOUND";
} else {
    // Try to log failed access
    $log_sql = "INSERT INTO access_logs (card_no, date_and_time, access_status)
                VALUES ('$tag', '$current_time', 'Failed')";
    
    $conn->query($log_sql); // Don't stop if logging fails
    
    echo "NOT_FOUND";
}

$conn->close();
?>