<?php
session_start();

// Redirect to login if not logged in using 'email_account'
if (!isset($_SESSION['email_account'])) {
    header("Location: LOGIN.php");
    exit();
}

// Include the MySQLi database connection
require_once 'db_connect.php';

// Check if unit_no is provided
if (isset($_GET['unit_no']) && !empty($_GET['unit_no'])) {
    $unit_no = $_GET['unit_no'];

    // Prepare the SQL statement
    $stmt = $conn->prepare("
        SELECT 
            units.unit_no, 
            tenants.tenant_name, 
            tenant_unit.occupant_count,
            tenant_unit.start_date, 
            tenant_unit.end_date, 
            tenant_unit.payment_due, 
            units.monthly_rent_amount, 
            tenant_unit.security_deposit, 
            units.unit_size, 
            units.unit_type, 
            units.floor_level, 
            units.unit_status
        FROM units
        INNER JOIN tenant_unit ON tenant_unit.unit_no = units.unit_no
        INNER JOIN tenants ON tenants.tenant_ID = tenant_unit.tenant_ID
        WHERE units.unit_no = ?
    ");

    // Bind and execute
    $stmt->bind_param("s", $unit_no);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $unitData = $result->fetch_assoc();
    } else {
        echo "No data found for this unit.";
        exit();
    }

    $stmt->close();
} else {
    echo "Unit number not provided.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unit Information Overview</title>
    
    <!-- Include the layout CSS -->
    <link rel="stylesheet" href="layout.css">
    
    <!-- Unit Overview specific styles -->
    <style>
        .mainContent {
            padding: 20px;
            overflow-y: auto;
        }

        .unit-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .unit-form-container h2 {
            color: #004AAD;
            font-size: 25px;
            margin: 0;
            padding: 10px 15px;
            text-align: center;
        }

        .unit-form-container {
            max-width: 800px;
            margin: 0 auto;
            border: 3px solid #A6DDFF;
            border-radius: 10px;
            padding: 10px 30px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .unit-icon-header {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .unit-icon {
            width: 40px;
            height: 40px;
            margin-right: 15px;
        }

        .unit-info {
            text-align: center;
        }

        .unit-info h3 {
            color: #004AAD;
            font-size: 20px;
            margin: 0;
        }

        .unit-info p {
            color: #666;
            font-size: 14px;
            margin: 5px 0 0 0;
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            align-items: center;
        }

        .form-group {
            flex: 1;
            display: flex;
            align-items: center;
        }

        .form-group label {
            min-width: 150px;
            font-weight: bold;
            color: #004AAD;
            margin-right: 10px;
        }

        .form-group input {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
            background-color: #f8f9fa;
            color: #666;
        }

        .section-divider {
            border-top: 2px solid #A6DDFF;
            margin: 30px 0 20px 0;
            padding-top: 20px;
        }

        .section-title {
            color: #004AAD;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
            text-align: center;
        }

        .button-container {
            display: flex;
            justify-content: left;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            min-width: 120px;
        }

        .btn-back {
            background-color: #004AAD;
            color: white;
        }

        .btn-back:hover {
            background-color: #003080;
        }

        /* Mobile Responsive */
        @media (max-width: 1024px) {
            .mainContent {
                padding: 15px;
            }

            .form-row {
                flex-direction: column;
                gap: 15px;
            }

            .form-group {
                flex-direction: column;
                align-items: stretch;
            }

            .form-group label {
                min-width: auto;
                margin-bottom: 5px;
            }

            .unit-form-container {
                padding: 20px;
            }
        }

        @media (max-width: 480px) {
            .unit-form-container {
                padding: 20px;
                margin: 0 10px;
            }

            .unit-form-container h2 {
                font-size: 24px;
                padding: 10px;
            }

            .unit-icon {
                width: 30px;
                height: 30px;
                margin-right: 10px;
            }

            .unit-info h3 {
                font-size: 18px;
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
            <div class="unit-header">
                
            </div>
            
            <div class="unit-form-container">
                <h2>Unit Information</h2>
                
                <!-- Unit Icon and Basic Info -->
                <div class="unit-icon-header">
                    <img src="UnitsInfoIcons/OccupiedUnitIcon.png" alt="Occupied Unit Icon" class="unit-icon">
                    <div class="unit-info">
                        <h3>Unit No. <?php echo isset($unitData['unit_no']) ? htmlspecialchars($unitData['unit_no']) : 'N/A'; ?></h3>
                        <p>Unit Code: Tenant<?php echo isset($unitData['unit_no']) ? str_pad($unitData['unit_no'], 3, '0', STR_PAD_LEFT) : '001'; ?></p>
                    </div>
                </div>
                
                <!-- Tenant Information Section -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="tenant_name">Tenant Name:</label>
                        <input type="text" id="tenant_name" value="<?php echo isset($unitData['tenant_name']) ? htmlspecialchars($unitData['tenant_name']) : ''; ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="occupant_count">Occupant Count:</label>
                        <input type="text" id="occupant_count" value="<?php echo isset($unitData['occupant_count']) ? htmlspecialchars($unitData['occupant_count']) . ' Occupant/s' : ''; ?>" readonly>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="start_date">Lease Start Date:</label>
                        <input type="text" id="start_date" value="<?php echo isset($unitData['start_date']) ? htmlspecialchars($unitData['start_date']) : ''; ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="end_date">Lease End Date:</label>
                        <input type="text" id="end_date" value="<?php echo isset($unitData['end_date']) ? htmlspecialchars($unitData['end_date']) : ''; ?>" readonly>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="payment_due">Payment Due Date:</label>
                        <input type="text" id="payment_due" value="<?php echo isset($unitData['payment_due']) ? htmlspecialchars($unitData['payment_due']) : ''; ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="monthly_rent">Monthly Rent Amount:</label>
                        <input type="text" id="monthly_rent" value="₱<?php echo isset($unitData['monthly_rent_amount']) ? number_format($unitData['monthly_rent_amount'], 2) : '0.00'; ?>" readonly>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="security_deposit">Security Deposit:</label>
                        <input type="text" id="security_deposit" value="₱<?php echo isset($unitData['security_deposit']) ? number_format($unitData['security_deposit'], 2) : '0.00'; ?>" readonly>
                    </div>
                    <div class="form-group">
                        <!-- Empty space for layout balance -->
                    </div>
                </div>

                <!-- Unit Details Section -->
                <div class="section-divider">
                    <div class="section-title">Unit Details</div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="unit_size">Unit Size:</label>
                        <input type="text" id="unit_size" value="<?php echo isset($unitData['unit_size']) ? htmlspecialchars($unitData['unit_size']) : ''; ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="floor_level">Floor Level:</label>
                        <input type="text" id="floor_level" value="<?php echo isset($unitData['floor_level']) ? htmlspecialchars($unitData['floor_level']) : ''; ?>" readonly>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="unit_type">Unit Type:</label>
                        <input type="text" id="unit_type" value="<?php echo isset($unitData['unit_type']) ? htmlspecialchars($unitData['unit_type']) : ''; ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="card_status">Card Status:</label>
                        <input type="text" id="card_status" value="Active" readonly>
                    </div>
                </div>
                
                <div class="button-container">
                    <a href="UNITSINFORMATION.php" class="btn btn-back">← Back</a>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'chatfunctions/CHAT_COMPONENT.php'; ?>
</body>
</html>

<?php
if (isset($conn)) {
    $conn->close();
}
?>