<?php
// This must be at the very top, before any other output.
header('Content-Type: application/json');
require_once '../db_connect.php';

// We need month, year, and unit_no to check schedules accurately
if (!isset($_GET['month']) || !isset($_GET['year']) || !isset($_GET['unit_no'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Missing required parameters']);
    exit();
}

$month = $_GET['month'];
$year = $_GET['year'];
$unit_no = $_GET['unit_no'];
$date_like = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-%';

$availability = [];

// Query the visitation_dates table
$sql = "SELECT visitation_date, visitation_time FROM visitation_dates WHERE unit_no = ? AND visitation_date LIKE ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Failed to prepare SQL statement.']);
    exit();
}
$stmt->bind_param("ss", $unit_no, $date_like);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $date = $row['visitation_date'];
    $time = $row['visitation_time'];

    if (!isset($availability[$date])) {
        $availability[$date] = ['morning' => false, 'afternoon' => false];
    }
    
    if ($time === 'morning') {
        $availability[$date]['morning'] = true;
    } elseif ($time === 'afternoon') {
        $availability[$date]['afternoon'] = true;
    }
}

$stmt->close();
$conn->close();

echo json_encode($availability);
?>