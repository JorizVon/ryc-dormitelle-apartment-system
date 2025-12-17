<?php
session_start();

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check session after handling potential ESP32 requests (if kept)
if (!isset($_SESSION['email_account'])) {
    header("Location: LOGIN.php");
    exit();
}

require_once 'db_connect.php'; // Ensure this path is correct
date_default_timezone_set('Asia/Manila');

$form_message = "";
$message_type = "";
$tenant_options = [];

// Fetch all Tenant IDs for the select dropdown (remains the same)
$sql_tenants = "SELECT
                    t.tenant_ID,
                    t.tenant_name,
                    tu.unit_no
                FROM
                    tenants AS t
                -- First, ensure the tenant is assigned to a unit
                INNER JOIN
                    tenant_unit AS tu ON LEFT(t.tenant_ID, 12) = LEFT(tu.tenant_ID, 12)
                -- Now, check for an existing activated card
                LEFT JOIN
                    card_registration AS cr ON t.tenant_ID = cr.tenant_ID AND cr.card_status = 'Activated'
                WHERE
                    -- Only include tenants where the LEFT JOIN found NO activated card
                    cr.card_no IS NULL
                ORDER BY
                    t.tenant_name ASC"; // Ordering by name is more user-friendly

$result_tenants = $conn->query($sql_tenants);
if ($result_tenants && $result_tenants->num_rows > 0) {
    while ($row = $result_tenants->fetch_assoc()) {
        $tenant_options[] = $row;
    }
}

// Handle Form Submission (INSERT Logic) - remains the same
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_registration'])) {
    $card_no = trim($_POST['card_no']);
    $selected_tenant_id = trim($_POST['tenant_id']);
    $unit_no = trim($_POST['unit_no']);
    $card_status = $_POST['card_status'];

    // Validate inputs
    if (empty($card_no) || empty($selected_tenant_id) || empty($unit_no) || empty($card_status)) {
        $form_message = "Please fill in all required fields.";
        $message_type = "error";
    } elseif (strlen($card_no) < 5) { // Assuming RFID tags are typically longer
        $form_message = "RFID Card No. seems too short.";
        $message_type = "error";
    } else {
        // Check if this card_no is already registered
        $stmt_check_card = $conn->prepare("SELECT card_no, unit_no FROM card_registration WHERE card_no = ? AND card_status = 'Activated'");
        if ($stmt_check_card) {
            $stmt_check_card->bind_param("s", $card_no);
            $stmt_check_card->execute();
            $result_check_card = $stmt_check_card->get_result();
            if ($result_check_card->num_rows > 0) {
                $existing_card = $result_check_card->fetch_assoc();
                $form_message = "This RFID Card No. ('" . htmlspecialchars($card_no) . "') is already registered to Unit: " . htmlspecialchars($existing_card['unit_no']);
                $message_type = "error";
            }
            $stmt_check_card->close();
        }

        if (empty($form_message)) {
            // Insert with tenant_ID included
            $insert_sql = "INSERT INTO card_registration (card_no, tenant_ID, unit_no, card_status) 
                           VALUES (?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($insert_sql);

            if ($stmt_insert) {
                $stmt_insert->bind_param("ssss", $card_no, $selected_tenant_id, $unit_no, $card_status);

                if ($stmt_insert->execute()) {
                    if ($stmt_insert->affected_rows > 0) {
                        $form_message = "New card registered successfully for Tenant: " . htmlspecialchars($selected_tenant_id) . " (Unit: " . htmlspecialchars($unit_no) . ")!";
                        $message_type = "success";
                        
                        // Redirect after 2 seconds
                        echo "<script>
                                setTimeout(function() {
                                    window.location.href = 'CARDREGISTRATION.php';
                                }, 2000);
                              </script>";
                    } else {
                        $form_message = "Could not register the card. No rows affected.";
                        $message_type = "error";
                    }
                } else {
                    if ($conn->errno == 1062) { // Duplicate entry error code
                        $form_message = "This card (No: ".htmlspecialchars($card_no).") might already have an Activated registration. Please check existing records.";
                    } else {
                        $form_message = "Could not register the card. " . $stmt_insert->error;
                    }
                    $message_type = "error";
                }
                $stmt_insert->close();
            } else {
                $form_message = "Error preparing insert statement: " . $conn->error;
                $message_type = "error";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Card Registration - RYC Dormitelle</title>
    <link rel="icon" type="image/png" href="otherIcons/pageicon.png">
    
    <!-- Include the layout CSS -->
    <link rel="stylesheet" href="layout.css">
    
        <style>
        /* ... Your existing CSS here ... */
        .mainContent {
            padding: 20px;
            overflow-y: auto;
        }

        .card-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .card-form-container {
            max-width: 800px;
            margin: 0 auto;
            border: 3px solid #A6DDFF;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .card-form-container h2 {
            color: #004AAD;
            font-size: 25px;
            margin: 0;
            padding: 15px;
            text-align: center;
        }

        .card-icon-container {
            width: 150px;
            height: 150px;
            border: 2px solid #ccc;
            border-radius: 8px;
            margin: 0 auto 30px;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            font-size: 14px;
        }

        .card-icon-container img {
            width: 120px;
            height: 120px;
            object-fit: contain;
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
            min-width: 120px;
            font-weight: bold;
            color: #004AAD;
            margin-right: 10px;
        }

        .form-group input, .form-group select {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-group input[readonly] {
            background-color: #f8f9fa;
            color: #666;
        }

        .editable-field {
            background-color: #fff !important;
            border-color: #004AAD !important;
        }

        .auto-fill-indicator {
            background-color: #e3f2fd !important;
            border: 2px solid #2196f3 !important;
            transition: all 0.3s ease;
        }

        .button-container {
            display: flex;
            justify-content: space-between;
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

        .btn-register {
            background-color: #28a745;
            color: white;
        }

        .btn-register:hover {
            background-color: #218838;
        }

        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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

            .button-container {
                flex-direction: column;
                gap: 15px;
            }
        }

        @media (max-width: 480px) {
            .card-form-container {
                padding: 20px;
                margin: 0 10px;
            }

            .card-form-container h2 {
                font-size: 20px;
                padding: 10px;
            }

            .card-icon-container {
                width: 120px;
                height: 120px;
            }

            .card-icon-container img {
                width: 100px;
                height: 100px;
            }
        }
        .auto-fill-indicator {
            background-color: #d4edda !important;
            border: 2px solid #28a745 !important;
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
            <div class="card-header">
                
            </div>
            
            <?php if (!empty($form_message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($form_message); ?>
                </div>
            <?php endif; ?>
            
            <div class="card-form-container">
                <h2>New Card to Register</h2>
                
                <!-- Card Reader Icon -->
                <div class="card-icon-container">
                    <img src="otherIcons/cardreaderIcon.png" alt="Card Reader Icon" id="rfidImage">
                </div>
                
                <form method="POST" onsubmit="return confirm('Are you sure you want to register this card?')">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="tenant_id_select">Tenant ID:</label>
                            <select name="tenant_id" id="tenant_id_select" onchange="loadUnitNo()" required>
                                <option value="">-- Select Tenant ID --</option>
                                <?php foreach ($tenant_options as $tenant): ?>
                                    <option value="<?php echo htmlspecialchars($tenant['tenant_ID']); ?>">
                                        <?php echo htmlspecialchars($tenant['tenant_ID']) . " - " . htmlspecialchars($tenant['tenant_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="unit_no">Unit No.:</label>
                            <input type="text" name="unit_no" id="unit_no" placeholder="Auto-filled from Tenant ID" readonly required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="card_no">RFID Card No.:</label>
                            <input type="text" name="card_no" id="card_no" placeholder="Scan RFID card or enter manually" class="editable-field" required>
                        </div>
                        <div class="form-group">
                            <label for="card_status">Card Status:</label>
                            <select id="card_status" name="card_status" required>
                                <option value="">-- Select Card Status --</option>
                                <option value="Activated" selected>Activated</option>
                                <option value="Deactivated">Deactivated</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="button-container">
                        <a href="CARDREGISTRATION.php" class="btn btn-back">← Back</a>
                        <button type="submit" name="confirm_registration" class="btn btn-register">Confirm Registration</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
<!-- In CARDREGISTRATION.php -->
<script>
    // Define the API endpoint
    const rfidApiEndpoint = 'esp32_handler.php';

    // This function remains the same, but it no longer starts the polling.
    function loadUnitNo() {
        const tenantSelect = document.getElementById('tenant_id_select');
        const unitInput = document.getElementById('unit_no');
        const tenantId = tenantSelect.value;
        
        if (tenantId) {
            fetch(`${rfidApiEndpoint}?get_unit_no=1&tenant_id=${encodeURIComponent(tenantId)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        unitInput.value = data.unit_no;
                    } else {
                        unitInput.value = '';
                        alert(data.message || 'Error loading unit number');
                    }
                })
                .catch(error => {
                    console.error('Error fetching unit number:', error);
                    unitInput.value = '';
                });
        } else {
            unitInput.value = '';
        }
    }

    // Function to check for the latest RFID scan (no unit number needed)
    function pollForLatestRFIDScan() {
        // The URL is now simpler and doesn't include the unit number
        fetch(`${rfidApiEndpoint}?check_rfid_scan=1`)
            .then(response => response.json())
            .then(data => {
                const cardInput = document.getElementById('card_no');
                if (data.status === 'new_scan' && data.rfid_tag) {
                    // A new tag was found, so populate the input field
                    cardInput.value = data.rfid_tag;
                    cardInput.classList.add('auto-fill-indicator');
                    showRFIDMessage('RFID card auto-filled: ' + data.rfid_tag, 'success');
                    
                    setTimeout(() => {
                        cardInput.classList.remove('auto-fill-indicator');
                    }, 3000);
                }
            })
            .catch(error => {
                console.error('Polling error:', error);
            });
    }

    function showRFIDMessage(message, type) {
        let messageDiv = document.getElementById('rfid-scan-message');
        if (!messageDiv) {
            messageDiv = document.createElement('div');
            messageDiv.id = 'rfid-scan-message';
            messageDiv.style.cssText = 'text-align: center; padding: 8px; margin: 10px auto; border-radius: 4px; font-weight: bold; max-width: 90%;';
            document.querySelector('.card-form-container').insertBefore(messageDiv, document.querySelector('form'));
        }
        
        messageDiv.textContent = message;
        messageDiv.style.backgroundColor = type === 'success' ? '#d4edda' : '#f8d7da';
        messageDiv.style.color = type === 'success' ? '#155724' : '#721c24';
        messageDiv.style.border = type === 'success' ? '1px solid #c3e6cb' : '1px solid #f5c6cb';
        
        setTimeout(() => {
            if (messageDiv) messageDiv.remove();
        }, 5000);
    }

    // This is the main change: start polling as soon as the page is ready.
    document.addEventListener('DOMContentLoaded', function() {
        // Start polling for new RFID scans every 2 seconds.
        setInterval(pollForLatestRFIDScan, 2000);
    });
</script>
    <?php include 'chatfunctions/CHAT_COMPONENT.php'; ?>
</body>
</html>
<?php
if (isset($conn)) {
    $conn->close();
}
?>