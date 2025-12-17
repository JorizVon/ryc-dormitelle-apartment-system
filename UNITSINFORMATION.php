<?php
session_start();

// Redirect to login if not logged in using 'email_account'
if (!isset($_SESSION['email_account'])) {
    header("Location: LOGIN.php");
    exit();
}

require_once 'db_connect.php';

// Handle unit status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_unit_status'])) {
    $unit_no = $_POST['unit_no'];
    
    // Check if there's a pending reservation for this unit
    $check_sql = "SELECT pr.unit_no, pr.confirmation_status 
                  FROM `pending_reservation` pr
                  WHERE pr.unit_no = ? AND pr.confirmation_status = 'pending'";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $unit_no);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // There's a pending reservation, don't allow update
        $_SESSION['error_message'] = "Cannot update Unit $unit_no. There is a pending reservation that must be processed first.";
        $check_stmt->close();
    } else {
        // No pending reservation, proceed with update
        $check_stmt->close();
        $update_sql = "UPDATE `units` SET `unit_status`='Available' WHERE unit_no = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("s", $unit_no);
        
        if ($stmt->execute()) {
            $_SESSION['status_message'] = "Unit $unit_no status updated to Available successfully!";
        } else {
            $_SESSION['error_message'] = "Error updating unit status.";
        }
        $stmt->close();
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch units ordered correctly
$sql = "
    SELECT unit_no, unit_status
    FROM units
    ORDER BY 
        LEFT(unit_no, 1),
        LPAD(SUBSTRING(unit_no, 3), 3, '0')
";
$result = $conn->query($sql);

$units = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $units[] = $row;
    }
}

// DON'T CLOSE CONNECTION HERE - MOVE TO AFTER CHAT COMPONENT

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Units Information - RYC Dormitelle</title>
    
    <!-- Include the layout CSS -->
    <link rel="stylesheet" href="layout.css">
    
    <!-- Units Information specific styles -->
    <style>
        /* Units Information Specific Styles */
        .mainContent {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
            overflow-y: auto;
        }

        .Unitslegend {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
            margin-left: 20px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .legendsIcon {
            height: 30px;
            width: 30px;
        }

        .legend-item p {
            margin: 0;
            font-size: 14px;
            color: #333;
        }

        .grid-container {
            display: grid;
            margin-left: 20px;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .statcardOccupiedUnit,
        .statcardAvailableUnit,
        .statcardOnHoldUnit {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease;
            min-height: 160px;
            display: flex;
            flex-direction: column;
        }

        .statcardOccupiedUnit:hover,
        .statcardAvailableUnit:hover,
        .statcardOnHoldUnit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .statcardOccupiedUnit {
            background-color: #FFFFFF;
            border: 3px solid #A6DDFF;
        }

        .statcardAvailableUnit {
            background-color: #A6DDFF;
            border: 3px solid #A6DDFF;
        }

        .statcardOnHoldUnit {
            background-color: #FFF5E6;
            border: 3px solid #FFB366;
        }

        .statsInfoOccupiedUnit,
        .statsInfoAvailableUnit,
        .statsInfoOnHoldUnit {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .UnitInfocontentIcons {
            height: 60px;
            width: 60px;
            margin-bottom: 10px;
        }

        .unit_no {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin: 0;
        }

        .viewandInfo {
            height: 40px;
            background-color: #0056B3;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .onHoldBtn {
            height: 40px;
            background-color: #FF8C42;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            width: 100%;
        }

        .viewandInfo:hover {
            background-color: #003D7A;
            color: #FFFFFF;
        }

        .onHoldBtn:hover {
            background-color: #E57A35;
        }

        .action-buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
            flex-wrap: wrap;
            margin-left: 20px;
            gap: 15px;
        }

        .backbtn,
        .addUnitbtn {
            height: 40px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .backbtn {
            min-width: 110px;
            background-color: #004AAD;
        }

        .addUnitbtn {
            min-width: 200px;
            background-color: #004AAD;
        }

        .backbtn a,
        .addUnitbtn a {
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #004AAD;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            padding: 0 15px;
            transition: all 0.3s ease;
            min-width: 110px;
        }

        .addUnitbtnIcon {
            height: 16px;
            width: 16px;
            margin-right: 8px;
        }

        .backbtn a:hover,
        .addUnitbtn a:hover {
            background-color: white;
            color: #004AAD;
            border: 2px solid #004AAD;
        }

        .addUnitbtn a:hover .addUnitbtnIcon {
            content: url('UnitsInfoIcons/plusblue.png');
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 30px;
            border: none;
            border-radius: 10px;
            width: 90%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s;
        }

        .modal h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 20px;
        }

        .modal p {
            color: #666;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .btn-confirm,
        .btn-cancel {
            padding: 10px 25px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-confirm {
            background-color: #28a745;
            color: white;
        }

        .btn-confirm:hover {
            background-color: #218838;
        }

        .btn-cancel {
            background-color: #dc3545;
            color: white;
        }

        .btn-cancel:hover {
            background-color: #c82333;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Status Messages */
        .status-message {
            padding: 15px;
            margin: 20px;
            border-radius: 5px;
            text-align: center;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Chat Component Compatibility - Ensure chat has higher z-index than modals */
        .chat-component,
        .chat-component * {
            z-index: 10000 !important;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .mainContent {
                padding: 15px;
            }

            .grid-container {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                gap: 15px;
            }

            .Unitslegend {
                justify-content: center;
                text-align: center;
            }

            .action-buttons {
                justify-content: center;
            }

            .backbtn {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .mainContent {
                padding: 10px;
            }

            .grid-container {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 10px;
            }

            .UnitInfocontentIcons {
                height: 50px;
                width: 50px;
            }

            .unit_no {
                font-size: 20px;
            }

            .viewandInfo,
            .onHoldBtn {
                height: 35px;
                font-size: 12px;
            }

            .addUnitbtn {
                min-width: 180px;
            }

            .backbtn a,
            .addUnitbtn a {
                font-size: 14px;
            }

            .modal-content {
                margin: 20% auto;
                padding: 20px;
            }
        }

        @media (max-width: 480px) {
            .mainContent {
                padding: 8px;
            }

            .grid-container {
                grid-template-columns: repeat(2, 1fr);
                gap: 8px;
            }

            .statcardOccupiedUnit,
            .statcardAvailableUnit,
            .statcardOnHoldUnit {
                min-height: 140px;
            }

            .statsInfoOccupiedUnit,
            .statsInfoAvailableUnit,
            .statsInfoOnHoldUnit {
                padding: 15px;
            }

            .UnitInfocontentIcons {
                height: 40px;
                width: 40px;
                margin-bottom: 8px;
            }

            .unit_no {
                font-size: 18px;
            }

            .viewandInfo,
            .onHoldBtn {
                height: 30px;
                font-size: 11px;
            }

            .legend-item p {
                font-size: 12px;
            }

            .legendsIcon {
                height: 16px;
                width: 16px;
            }

            .modal-content {
                margin: 30% auto;
                padding: 15px;
                width: 95%;
            }

            .modal-buttons {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Include Sidebar -->
    <?php include 'sidebar.html'; ?>
    
    <div class="mainBody">
        <!-- Include Header -->
        <?php include 'header.php'; ?>
        
        <div class="mainContent">
            <h4>Units Information</h4>
            
            <?php
            // Display status messages
            if (isset($_SESSION['status_message'])) {
                echo "<div class='status-message success-message'>" . $_SESSION['status_message'] . "</div>";
                unset($_SESSION['status_message']);
            }
            if (isset($_SESSION['error_message'])) {
                echo "<div class='status-message error-message'>" . $_SESSION['error_message'] . "</div>";
                unset($_SESSION['error_message']);
            }
            ?>
            
            <div class="Unitslegend">
                <div class="legend-item">
                    <img src="UnitsInfoIcons/OccupiedUnitIcon.png" alt="Occupied Unit Icon" class="legendsIcon">
                    <p>Occupied</p>
                </div>
                <div class="legend-item">
                    <img src="UnitsInfoIcons/UnoccupiedUnitIcon.png" alt="Available Unit Icon" class="legendsIcon">
                    <p>Available Unit</p>
                </div>
                <div class="legend-item">
                    <img src="UnitsInfoIcons/onHoldUnitIcon.png" alt="On Hold Unit Icon" class="legendsIcon">
                    <p>On Hold</p>
                </div>
            </div>
            
            <div class="grid-container">
                <?php
                    foreach ($units as $unit) {
                        if ($unit['unit_status'] === 'Occupied') {
                            echo "
                            <div class='statcardOccupiedUnit'>
                                <div class='statsInfoOccupiedUnit'>
                                    <img src='UnitsInfoIcons/OccupiedUnitIcon.png' alt='Occupied Unit Icon' class='UnitInfocontentIcons'>
                                    <h1 class='unit_no'>{$unit['unit_no']}</h1>
                                </div>
                                <a href='OCCUPIEDUNITOVERVIEW.php?unit_no=" . urlencode($unit['unit_no']) . "' class='viewandInfo'>View</a>
                            </div>";
                        } else if ($unit['unit_status'] === 'Available') {
                            echo "
                            <div class='statcardAvailableUnit'>
                                <div class='statsInfoAvailableUnit'>
                                    <img src='UnitsInfoIcons/UnoccupiedUnitIcon.png' alt='Available Unit Icon' class='UnitInfocontentIcons'>
                                    <h1 class='unit_no'>{$unit['unit_no']}</h1>
                                </div>
                                <a href='AVAILABLEUNITOVERVIEW.php?unit_no=" . urlencode($unit['unit_no']) . "' class='viewandInfo'>Info</a>
                            </div>";
                        } else if ($unit['unit_status'] === 'pending') {
                            echo "
                            <div class='statcardOnHoldUnit'>
                                <div class='statsInfoOnHoldUnit'>
                                    <img src='UnitsInfoIcons/onHoldUnitIcon.png' alt='On Hold Unit Icon' class='UnitInfocontentIcons'>
                                    <h1 class='unit_no'>{$unit['unit_no']}</h1>
                                </div>
                                <button class='onHoldBtn' onclick='openModal(\"{$unit['unit_no']}\")'>On Hold</button>
                            </div>";
                        }
                    }
                ?>
            </div>
            
            <div class="action-buttons">
                <div class="backbtn">
                    <a href="DASHBOARD.php">&#10558; Back</a>
                </div>
                <div class="addUnitbtn">
                    <a href="ADDNEWUNIT.php">
                        <img src="UnitsInfoIcons/pluswht.png" alt="Add Unit Icon" class="addUnitbtnIcon">
                        Add New Unit
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Unit Status Update -->
    <div id="updateModal" class="modal">
        <div class="modal-content">
            <h3>Update Unit's Availability</h3>
            <p>Are you sure you want to update this unit's status to 'Available'?</p>
            <div class="modal-buttons">
                <button class="btn-confirm" onclick="confirmUpdate()">Yes, Update</button>
                <button class="btn-cancel" onclick="closeModal()">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Hidden form for unit status update -->
    <form id="updateForm" method="POST" style="display: none;">
        <input type="hidden" name="update_unit_status" value="1">
        <input type="hidden" name="unit_no" id="updateUnitNo">
    </form>

    <script>
        let currentUnitNo = '';

        function openModal(unitNo) {
            currentUnitNo = unitNo;
            document.getElementById('updateModal').style.display = 'block';
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        }

        function closeModal() {
            document.getElementById('updateModal').style.display = 'none';
            document.body.style.overflow = 'auto'; // Restore scrolling
            currentUnitNo = '';
        }

        function confirmUpdate() {
            if (currentUnitNo) {
                document.getElementById('updateUnitNo').value = currentUnitNo;
                document.getElementById('updateForm').submit();
            }
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('updateModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });

        // Auto-hide status messages after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const statusMessages = document.querySelectorAll('.status-message');
            statusMessages.forEach(function(message) {
                setTimeout(function() {
                    message.style.opacity = '0';
                    setTimeout(function() {
                        message.remove();
                    }, 300);
                }, 5000);
            });
        });
    </script>
    
    <?php include 'chatfunctions/CHAT_COMPONENT.php'; ?>
    
    <?php
    // Close database connection AFTER chat component
    if (isset($conn)) {
        $conn->close();
    }
    ?>
</body>
</html>