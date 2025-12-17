<?php
session_start();

// Redirect to login if not logged in using 'email_account'
if (!isset($_SESSION['email_account'])) {
    header("Location: LOGIN.php");
    exit();
}

require_once 'db_connect.php';
date_default_timezone_set('Asia/Manila');

$card_data = [];
$page_error = "";
$form_message = "";
$message_type = "";
$unit_no_get = null;

// --- Retrieve parameter from GET ---
if (isset($_GET['unit_no'])) {
    $unit_no_get = trim($_GET['unit_no']);
} else {
    $page_error = "No unit number provided.";
}

// --- Data Retrieval Logic ---
if (empty($page_error)) {
    // SQL to fetch all cards for a specific unit
    $sql = "SELECT card_registration.card_no, card_registration.tenant_ID, 
                   card_registration.unit_no, card_registration.card_status,
                   tenants.tenant_name
            FROM card_registration 
            LEFT JOIN tenants ON card_registration.tenant_ID = tenants.tenant_ID
            WHERE card_registration.unit_no = ?
            ORDER BY card_registration.card_no";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $unit_no_get);
        $stmt->execute();
        $result_card = $stmt->get_result();
        
        if ($result_card && $result_card->num_rows > 0) {
            while($row = $result_card->fetch_assoc()) {
                $card_data[] = $row;
            }
        } else {
            $page_error = "No card registrations found for Unit: " . htmlspecialchars($unit_no_get);
        }
        $stmt->close();
    } else {
        $page_error = "Error preparing card details query: " . $conn->error;
    }
}

// --- Handle Form Submission (Bulk Update or Delete) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($card_data)) {
    
    if (isset($_POST['update_all_cards'])) {
        // Bulk update all cards for the unit
        $new_card_status = $_POST['bulk_card_status'];
        
        if (empty($new_card_status)) {
            $form_message = "Error: Please select a card status.";
            $message_type = "error";
        } else {
            $update_sql = "UPDATE card_registration 
                           SET card_status = ? 
                           WHERE unit_no = ?";
            $stmt_update = $conn->prepare($update_sql);

            if ($stmt_update) {
                $stmt_update->bind_param("ss", $new_card_status, $unit_no_get);
                if ($stmt_update->execute()) {
                    if ($stmt_update->affected_rows > 0) {
                        $form_message = "All cards for Unit " . htmlspecialchars($unit_no_get) . " updated to " . htmlspecialchars($new_card_status) . "!";
                        $message_type = "success";
                        
                        // Update local data
                        foreach($card_data as &$card) {
                            $card['card_status'] = $new_card_status;
                        }
                    } else {
                        $form_message = "No changes were made. Status might be the same.";
                        $message_type = "error";
                    }
                } else {
                    $form_message = "Could not update card status. " . $stmt_update->error;
                    $message_type = "error";
                }
                $stmt_update->close();
            } else {
                $form_message = "Error preparing update statement: " . $conn->error;
                $message_type = "error";
            }
        }
    } elseif (isset($_POST['delete_selected_cards'])) {
        // Delete selected cards
        if (isset($_POST['selected_cards']) && is_array($_POST['selected_cards']) && count($_POST['selected_cards']) > 0) {
            $placeholders = implode(',', array_fill(0, count($_POST['selected_cards']), '?'));
            $delete_sql = "DELETE FROM card_registration WHERE card_no IN ($placeholders)";
            $stmt_delete = $conn->prepare($delete_sql);
            
            if ($stmt_delete) {
                $types = str_repeat('s', count($_POST['selected_cards']));
                $stmt_delete->bind_param($types, ...$_POST['selected_cards']);
                
                if ($stmt_delete->execute()) {
                    $deleted_count = $stmt_delete->affected_rows;
                    if ($deleted_count > 0) {
                        $form_message = "$deleted_count card(s) deleted successfully!";
                        $message_type = "success";
                        
                        // Remove deleted cards from display
                        $card_data = array_filter($card_data, function($card) {
                            return !in_array($card['card_no'], $_POST['selected_cards']);
                        });
                        
                        // If all cards deleted, redirect
                        if (empty($card_data)) {
                            header("Location: CARDREGISTRATION.php?message=" . urlencode("All cards deleted for Unit: $unit_no_get"));
                            exit();
                        }
                    } else {
                        $form_message = "Cards not found or already deleted.";
                        $message_type = "error";
                    }
                } else {
                    $form_message = "Could not delete cards. " . $stmt_delete->error;
                    $message_type = "error";
                }
                $stmt_delete->close();
            } else {
                $form_message = "Error preparing delete statement: " . $conn->error;
                $message_type = "error";
            }
        } else {
            $form_message = "No cards selected for deletion.";
            $message_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Card Management - RYC Dormitelle</title>
    <link rel="icon" type="image/png" href="otherIcons/pageicon.png">
    
    <!-- Include the layout CSS -->
    <link rel="stylesheet" href="layout.css">
    
        <style>
        .mainContent {
            padding: 20px;
            overflow-y: auto;
        }

        .card-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .card-form-container h2 {
            color: #004AAD;
            font-size: 25px;
            margin: 0;
            padding: 15px;
            text-align: center;
        }

        .card-form-container {
            max-width: 800px;
            margin: 0 auto;
            border: 3px solid #A6DDFF;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .card-icon-placeholder {
            width: 150px;
            height: 150px;
            border: 2px solid #ccc;
            margin: 0 auto 30px;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            font-size: 14px;
            border-radius: 8px;
        }

        .card-icon-placeholder img {
            width: 100%;
            height: 100%;
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
            background-color: #545b62;
        }

        .btn-update {
            background-color: #28a745;
            color: white;
        }

        .btn-update:hover {
            background-color: #218838;
        }

        .btn-delete {
            background-color: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background-color: #c82333;
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

        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
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

            .action-buttons {
                flex-direction: column;
                gap: 10px;
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

            .card-icon-placeholder {
                width: 120px;
                height: 120px;
            }
        }
                .cards-container {
            max-width: 900px;
            margin: 20px auto;
        }
        
        .card-item {
            background: #f8f9fa;
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            border: 2px solid #dee2e6;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .card-item-selected {
            border-color: #007bff;
            background: #e7f3ff;
        }
        
        .card-info {
            flex: 1;
        }
        
        .card-checkbox {
            margin-right: 15px;
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .bulk-actions {
            background: #fff;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            border: 2px solid #007bff;
        }
        
        .selection-info {
            text-align: center;
            margin: 10px 0;
            font-weight: bold;
            color: #007bff;
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
                <h2>Manage Cards for Unit: <?php echo htmlspecialchars($unit_no_get ?? 'N/A'); ?></h2>
            </div>
            
            <?php if (!empty($form_message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($form_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($page_error): ?>
                <div class="message error">
                    <?php echo htmlspecialchars($page_error); ?>
                </div>
            <?php elseif (!empty($card_data)): ?>
                <div class="cards-container">
                    <!-- Bulk Actions Section -->
                    <div class="bulk-actions">
                        <h3>Bulk Actions</h3>
                        <form method="POST" id="bulkForm">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="bulk_card_status">Update All Cards Status:</label>
                                    <select id="bulk_card_status" name="bulk_card_status">
                                        <option value="">-- Select Status --</option>
                                        <option value="Activated">Activated</option>
                                        <option value="Deactivated">Deactivated</option>
                                    </select>
                                    <button type="submit" name="update_all_cards" class="btn btn-update" onclick="return confirm('Update status for ALL cards in this unit?')">
                                        Update All Cards
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Individual Cards Section -->
                    <form method="POST" id="cardsForm">
                        <div class="selection-info" id="selectionInfo" style="display:none;">
                            <span id="selectedCount">0</span> card(s) selected
                        </div>
                        
                        <?php foreach ($card_data as $card): ?>
                            <div class="card-item" id="card-<?php echo htmlspecialchars($card['card_no']); ?>">
                                <input type="checkbox" 
                                       name="selected_cards[]" 
                                       value="<?php echo htmlspecialchars($card['card_no']); ?>" 
                                       class="card-checkbox"
                                       onchange="updateSelection()">
                                
                                <div class="card-info">
                                    <strong>Card No:</strong> <?php echo htmlspecialchars($card['card_no']); ?><br>
                                    <strong>Tenant ID:</strong> <?php echo htmlspecialchars($card['tenant_ID'] ?? 'N/A'); ?><br>
                                    <strong>Tenant Name:</strong> <?php echo htmlspecialchars($card['tenant_name'] ?? 'N/A'); ?><br>
                                    <strong>Status:</strong> 
                                    <span style="color: <?php echo ($card['card_status'] == 'Activated') ? 'green' : 'red'; ?>; font-weight: bold;">
                                        <?php echo htmlspecialchars($card['card_status']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="button-container" style="margin-top: 20px;">
                            <a href="CARDREGISTRATION.php" class="btn btn-back">← Back</a>
                            <button type="submit" 
                                    name="delete_selected_cards" 
                                    class="btn btn-delete" 
                                    id="deleteBtn"
                                    style="display:none;"
                                    onclick="return confirm('Are you sure you want to delete the selected card(s)? This action cannot be undone.')">
                                Delete Selected Cards
                            </button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <?php if(empty($page_error)): ?>
                    <div class="message error">Could not load card details.</div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function updateSelection() {
            const checkboxes = document.querySelectorAll('.card-checkbox');
            const deleteBtn = document.getElementById('deleteBtn');
            const selectionInfo = document.getElementById('selectionInfo');
            const selectedCount = document.getElementById('selectedCount');
            
            let count = 0;
            checkboxes.forEach(checkbox => {
                const cardItem = checkbox.closest('.card-item');
                if (checkbox.checked) {
                    count++;
                    cardItem.classList.add('card-item-selected');
                } else {
                    cardItem.classList.remove('card-item-selected');
                }
            });
            
            selectedCount.textContent = count;
            
            if (count > 0) {
                deleteBtn.style.display = 'inline-block';
                selectionInfo.style.display = 'block';
            } else {
                deleteBtn.style.display = 'none';
                selectionInfo.style.display = 'none';
            }
        }

        // Auto-hide notification messages after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const messageElement = document.querySelector('.message');
            if (messageElement) {
                setTimeout(function() {
                    messageElement.style.opacity = '0';
                    messageElement.style.transition = 'opacity 0.5s ease-out';
                    setTimeout(function() {
                        messageElement.style.display = 'none';
                    }, 500);
                }, 3000);
            }
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