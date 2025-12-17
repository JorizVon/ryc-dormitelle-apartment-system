<?php
session_start();

// Redirect to login if not logged in using 'email_account'
if (!isset($_SESSION['email_account'])) {
    header("Location: LOGIN.php");
    exit();
}

require_once 'db_connect.php';

$unit_no = $_GET['unit_no'] ?? '';

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_unit'])) {
    $unit_to_delete = $_POST['unit_no'] ?? '';
    
    if ($unit_to_delete !== '') {
        // Start transaction to ensure both deletions succeed or fail together
        $conn->begin_transaction();
        
        try {
            // First, delete associated unit images
            $stmt = $conn->prepare("DELETE FROM `unit_images` WHERE unit_no = ?");
            $stmt->bind_param("s", $unit_to_delete);
            $stmt->execute();
            $stmt->close();
            
            // Then, delete the unit itself
            $stmt = $conn->prepare("DELETE FROM `units` WHERE unit_no = ?");
            $stmt->bind_param("s", $unit_to_delete);
            $stmt->execute();
            $stmt->close();
            
            // Commit the transaction
            $conn->commit();
            
            // Redirect back to units information page after successful deletion
            header("Location: UNITSINFORMATION.php?deleted=success");
            exit();
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $error_message = "Error deleting unit: " . $e->getMessage();
        }
    }
}

$unit_size = '';
$floor_level = '';
$unit_type = '';
$occupant_capacity = '';
$monthly_rent_amount = '';

if ($unit_no !== '') {
    $stmt = $conn->prepare("SELECT `unit_no`, `unit_size`, `floor_level`, `unit_type`, `occupant_capacity`, `monthly_rent_amount` FROM `units` WHERE `unit_no` = ?");
    $stmt->bind_param("s", $unit_no);
    $stmt->execute();
    $stmt->bind_result($fetched_unit_no, $unit_size, $floor_level, $unit_type, $occupant_capacity, $monthly_rent_amount);
    if ($stmt->fetch()) {
        // Data fetched successfully
    }
    $stmt->close();
}

$unit_images = [];

if ($unit_no !== '') {
    // Fetch unit images from database
    $stmt = $conn->prepare("SELECT `unit_image` FROM `unit_images` WHERE `unit_no` = ?");
    $stmt->bind_param("s", $unit_no);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $unit_images[] = 'unitImages/' . $row['unit_image']; // add folder path
    }
    
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unit Overview - RYC Dormitelle</title>
    <link rel="icon" type="image/png" href="otherIcons/pageicon.png">
    
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
            padding: 15px;
            text-align: center;
        }

        .unit-form-container {
            max-width: 800px;
            margin: 0 auto;
            border: 3px solid #A6DDFF;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .form-content {
            display: flex;
            gap: 30px;
        }

        .form-left {
            flex: 1;
            min-width: 350px;
        }

        .form-right {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .form-group {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .form-group label {
            min-width: 150px;
            font-weight: 10000;
            color: #004AAD;
            margin-right: 10px;
        }

        .form-group input, .form-group select {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
            background-color: #f8f9fa;
            color: #666;
            max-width: 250px;
        }

        .section-title {
            color: #004AAD;
            font-size: 16px;
            font-weight: 10000;
            margin: 20px 0 10px 0;
        }

        .photos-section {
            text-align: center;
        }

        .photos-title {
            color: #004AAD;
            font-size: 16px;
            font-weight: 10000;
            margin-bottom: 15px;
        }

        .unit-images-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            max-width: 300px;
            margin: 0 auto;
        }

        .unit-image {
            width: 90px;
            height: 90px;
            border: 2px solid #ddd;
            border-radius: 8px;
            object-fit: cover;
            background-color: #f8f9fa;
        }

        .add-photo {
            width: 90px;
            height: 90px;
            border: 2px dashed #004AAD;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
            color: #004AAD;
            font-size: 24px;
            cursor: pointer;
        }

        .add-photo:hover {
            background-color: #e9ecef;
        }

        .button-container {
            display: flex;
            justify-content: space-between;
            gap: 15px;
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

        .btn-remove {
            background-color: #dc3545;
            color: white;
        }

        .btn-remove:hover {
            background-color: #c82333;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        /* Mobile Responsive */
        @media (max-width: 1024px) {
            .mainContent {
                padding: 15px;
            }

            .form-content {
                flex-direction: column;
                gap: 20px;
            }

            .form-left {
                min-width: auto;
            }

            .form-group {
                flex-direction: column;
                align-items: stretch;
            }

            .form-group label {
                min-width: auto;
                margin-bottom: 5px;
            }

            .form-group input, .form-group select {
                max-width: none;
            }

            .unit-images-grid {
                grid-template-columns: repeat(2, 1fr);
                max-width: 200px;
            }

            .unit-image, .add-photo {
                width: 80px;
                height: 80px;
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

            .unit-images-grid {
                grid-template-columns: repeat(3, 1fr);
                max-width: 240px;
            }

            .unit-image, .add-photo {
                width: 70px;
                height: 70px;
            }

            .button-container {
                flex-direction: column;
            }

            .btn {
                width: 100%;
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
                <h2>Unit Overview</h2>
                
                <?php if (isset($error_message)): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                
                <div class="form-content">
                    <!-- Left side - Unit Information -->
                    <div class="form-left">
                        <div class="form-group">
                            <label for="unit_no">Unit No.:</label>
                            <input type="text" id="unit_no" value="<?php echo htmlspecialchars($unit_no); ?>" readonly>
                        </div>

                        <div class="section-title">Unit Details</div>

                        <div class="form-group">
                            <label for="unit_size">Unit Size:</label>
                            <input type="text" id="unit_size" value="<?php echo htmlspecialchars($unit_size); ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label for="floor_level">Floor Level:</label>
                            <select id="floor_level" disabled>
                                <option value="<?php echo htmlspecialchars($floor_level); ?>" selected><?php echo htmlspecialchars($floor_level); ?></option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="occupant_capacity">Capacity:</label>
                            <input type="text" id="occupant_capacity" value="Maximum of <?php echo htmlspecialchars($occupant_capacity); ?> occupant" readonly>
                        </div>

                        <div class="form-group">
                            <label for="unit_type">Type:</label>
                            <select id="unit_type" disabled>
                                <option value="<?php echo htmlspecialchars($unit_type); ?>" selected><?php echo htmlspecialchars($unit_type); ?></option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="monthly_rent_amount">Monthly Rent Amount:</label>
                            <input type="text" id="monthly_rent_amount" value="₱<?php echo number_format($monthly_rent_amount, 2); ?>" readonly>
                        </div>
                    </div>

                    <!-- Right side - Unit Photos -->
                    <div class="form-right">
                        <div class="photos-section">
                            <div class="photos-title">Unit Photos</div>
                            <div class="unit-images-grid">
                                <?php if (!empty($unit_images)): ?>
                                    <?php 
                                    $imageCount = 0;
                                    foreach ($unit_images as $image): 
                                        if ($imageCount < 8): // Limit to 8 images
                                    ?>
                                        <img src="<?php echo htmlspecialchars($image); ?>" alt="Unit Photo" class="unit-image">
                                    <?php 
                                        $imageCount++;
                                        endif;
                                    endforeach; ?>
                                    
                                    <?php 
                                    // Fill remaining slots with add photo placeholders
                                    for ($i = $imageCount; $i < 8; $i++): 
                                    ?>
                                        <div class="add-photo">+</div>
                                    <?php endfor; ?>
                                <?php else: ?>
                                    <!-- If no images found, show placeholders -->
                                    <?php for ($i = 0; $i < 6; $i++): ?>
                                        <img src="UnitsInfoIcons/UnoccupiedUnitIcon.png" alt="Placeholder" class="unit-image">
                                    <?php endfor; ?>
                                    <div class="add-photo">+</div>
                                    <div class="add-photo">+</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="button-container">
                    <a href="UNITSINFORMATION.php" class="btn btn-back">← Back</a>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this unit? This action cannot be undone.');">
                        <input type="hidden" name="unit_no" value="<?php echo htmlspecialchars($unit_no); ?>">
                        <button type="submit" name="delete_unit" class="btn btn-remove">Remove</button>
                    </form>
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