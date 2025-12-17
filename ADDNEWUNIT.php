<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['email_account'])) {
    header("Location: LOGIN.php");
    exit();
}

require_once 'db_connect.php';

// Handle the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $unit_no = $_POST['unit_no'];
    $apartment_no = $_POST['apartment_no'];
    $unit_address = $_POST['unit_address'];
    $unit_size = $_POST['unit_size'];
    $floor_level = $_POST['floor_level'];
    $unit_type = $_POST['unit_type'];
    $occupant_capacity = $_POST['occupant_capacity'];
    $monthly_rent_amount = $_POST['monthly_rent_amount'];
    $unit_status = 'Available';

    // Server-side validation
    if (strlen($unit_address) <= 5) {
        echo "<script>alert('Unit Address must be more than 5 characters.'); window.history.back();</script>";
        exit();
    }
    
    // Server-side validation for floor level
    if ($floor_level < 1) {
        echo "<script>alert('Floor level must be at least 1.'); window.history.back();</script>";
        exit();
    }

    // Insert into units table
    $stmt = $conn->prepare("INSERT INTO `units`(`unit_no`, `apartment_no`, `unit_address`, `unit_size`, `floor_level`, `unit_type`, `occupant_capacity`, `monthly_rent_amount`, `unit_status`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssss", $unit_no, $apartment_no, $unit_address, $unit_size, $floor_level, $unit_type, $occupant_capacity, $monthly_rent_amount, $unit_status);
    $stmt->execute();
    $stmt->close();

    // Handle unit images
    if (isset($_FILES['unit_images'])) {
        $files = $_FILES['unit_images'];

        for ($i = 0; $i < count($files['name']); $i++) {
            $fileName = basename($files['name'][$i]);
            $targetDir = "unitImages/";
            $targetFilePath = $targetDir . $fileName;

            if (move_uploaded_file($files['tmp_name'][$i], $targetFilePath)) {
                $stmt = $conn->prepare("INSERT INTO `unit_images`(`unit_no`, `unit_image`) VALUES (?, ?)");
                $stmt->bind_param("ss", $unit_no, $fileName);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    echo "<script>alert('Unit and images inserted successfully!'); window.location.href='UNITSINFORMATION.php';</script>";
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Unit - RYC Dormitelle</title>
    <link rel="icon" type="image/png" href="otherIcons/pageicon.png">
    <link rel="stylesheet" href="layout.css">
    
    <style>
        .mainContent {
            padding: 20px;
            overflow-y: auto;
            height: calc(100vh - 13vh);
            overflow-x: hidden;
        }
        
        html, body {
            overflow: hidden;
            height: 100%;
        }

        .page-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .page-header h2 {
            color: #01214B;
            font-size: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .page-header img {
            height: 50px;
            width: 50px;
        }

        .form-wrapper {
            max-width: 900px;
            margin: 0 auto;
            padding-bottom: 80px;
        }

        .overviewContainer {
            border: 3px solid #A6DDFF;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            background-color: #fff;
        }

        .formContainer {
            width: 100%;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: inline-block;
            width: 200px;
            margin-bottom: 5px;
            padding: 2px;
            vertical-align: top;
            color: #004AAD;
            font-weight: 500;
        }

        input[type="text"],
        input[type="date"],
        input[type="number"],
        input[type="file"] {
            width: calc(100% - 210px);
            max-width: 400px;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }

        select {
            width: calc(100% - 210px);
            max-width: 418px;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
            background-color: white;
        }

        .unitphotoContainer {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px dashed #A6DDFF;
        }

        .unitImagesContainer {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .unitImageCard {
            width: 100%;
            height: 120px;
            border: 2px solid #ccc;
            border-radius: 8px;
            object-fit: cover;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .footbtnContainer {
            max-width: 900px;
            margin: 30px auto 0;
            display: flex;
            justify-content: space-between;
            gap: 20px;
        }

        .backbtn {
            padding: 12px 30px;
            background-color: #004AAD;
            color: #FFFFFF;
            text-decoration: none;
            border-radius: 5px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .backbtn:hover {
            background-color: #FFFFFF;
            color: #004AAD;
            border: 2px solid #004AAD;
        }

        .confirmbtn {
            padding: 12px 30px;
            background-color: #28a745;
            color: #FFFFFF;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .confirmbtn:hover {
            background-color: #218838;
        }

        /* Mobile and Tablet Responsive */
        @media (max-width: 1024px) {
            .mainContent {
                padding: 15px;
            }

            label {
                width: 100%;
                display: block;
                margin-bottom: 5px;
            }

            input[type="text"],
            input[type="date"],
            input[type="number"],
            input[type="file"],
            select {
                width: 100%;
                max-width: 100%;
            }

            .footbtnContainer {
                flex-direction: column;
            }

            .backbtn,
            .confirmbtn {
                width: 100%;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .page-header h2 {
                font-size: 24px;
            }

            .page-header img {
                height: 35px;
                width: 35px;
            }

            .overviewContainer {
                padding: 20px 15px;
            }

            .unitImagesContainer {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }

            .unitImageCard {
                height: 100px;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.html'; ?>
    
    <div class="mainBody">
        <?php include 'header.php'; ?>
        
        <div class="mainContent">
            <div class="page-header">
                <h2>
                    <img src="UnitsInfoIcons/UnoccupiedUnitIcon.png" alt="Unit Icon">
                    Add New Unit
                </h2>
            </div>

            <form action="" method="POST" enctype="multipart/form-data">
                <div class="form-wrapper">
                    <div class="overviewContainer">
                        <div class="formContainer">
                            
                            <!-- Apartment No. -->
                            <div class="form-group">
                                <label for="apartment_no">Apartment No. <span style="color: red;">*</span></label>
                                <input placeholder="eg. APT-001" type="text" name="apartment_no" id="apartment_no" required>
                            </div>

                            <!-- Unit No. -->
                            <div class="form-group">
                                <label for="unit_no">Unit No. <span style="color: red;">*</span></label>
                                <input placeholder="eg. A-001" type="text" name="unit_no" id="unit_no" required>
                            </div>

                            <div class="form-group">
                                <label for="unit_address">Unit Address <span style="color: red;">*</span></label>
                                <input placeholder="Enter complete address (min 6 chars)" type="text" name="unit_address" id="unit_address" minlength="6" required>
                            </div>

                            <div class="form-group">
                                <label for="unit_size">Unit Size (sqm) <span style="color: red;">*</span></label>
                                <input placeholder="eg. 25" type="number" min="0" step="0.01" oninput="this.value = Math.abs(this.value)" name="unit_size" id="unit_size" required>
                            </div>

                            <div class="form-group">
                                <label for="floor_level">Floor Level <span style="color: red;">*</span></label>
                                <!-- UPDATED: Set min to 1 -->
                                <input placeholder="eg. 1, 2, 3" type="number" min="1" oninput="this.value = Math.abs(this.value)" name="floor_level" id="floor_level" required>
                            </div>

                            <div class="form-group">
                                <label for="unit_type">Unit Type <span style="color: red;">*</span></label>
                                <select name="unit_type" id="unit_type" required>
                                    <option value="" disabled selected>Select Unit Type</option>
                                    <option value="Studio">Studio</option>
                                    <option value="1BR">1BR</option>
                                    <option value="2BR">2BR</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="occupant_capacity">Occupant Capacity <span style="color: red;">*</span></label>
                                <select name="occupant_capacity" id="occupant_capacity" required>
                                    <option value="" disabled selected>Select Capacity</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="5">5</option>
                                    <option value="6">6</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="monthly_rent_amount">Monthly Rent Amount <span style="color: red;">*</span></label>
                                <input placeholder="eg. 5000" type="number" min="0" step="0.01" name="monthly_rent_amount" id="monthly_rent_amount" required>
                            </div>

                            <div class="form-group">
                                <label for="unit_images">Upload Unit Images</label>
                                <input type="file" name="unit_images[]" id="unit_images" accept="image/*" multiple required onchange="previewImages()">
                                <small style="display: block; margin-left: 210px; color: #666; margin-top: 5px;">
                                    Maximum 8 images
                                </small>
                            </div>

                            <div class="unitphotoContainer">
                                <div class="unitImagesContainer" id="unitImagesContainer"></div>
                            </div>
                        </div>
                    </div>

                    <div class="footbtnContainer">
                        <a href="UNITSINFORMATION.php" class="backbtn">← Back</a>
                        <button type="submit" class="confirmbtn" onclick="return confirm('Are you sure you want to add this unit?')">Confirm & Add Unit</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function previewImages() {
            const preview = document.getElementById('unitImagesContainer');
            const files = document.getElementById('unit_images').files;

            preview.innerHTML = '';

            if (files.length > 8) {
                alert("You can only upload up to 8 images.");
                document.getElementById('unit_images').value = '';
                return;
            }

            if (files.length === 0) {
                return;
            }

            Array.from(files).forEach(file => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.alt = 'Unit Image Preview';
                    img.className = 'unitImageCard';
                    preview.appendChild(img);
                };
                reader.readAsDataURL(file);
            });
        }
    </script>
    
    <?php include 'chatfunctions/CHAT_COMPONENT.php'; ?>
</body>
</html>