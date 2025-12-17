<?php
session_start();

// Redirect to login if not logged in using 'email_account'
if (!isset($_SESSION['email_account'])) {
    header("Location: LOGIN.php");
    exit();
}

require_once 'db_connect.php';

// Initialize $result and $query_error
$result = null;
$query_error = "";

// Fetch the card registration data with updated query
$sql = "SELECT card_registration.card_no, card_registration.tenant_ID, 
               card_registration.unit_no, card_registration.card_status,
               tenants.tenant_name
        FROM card_registration 
        LEFT JOIN tenants ON card_registration.tenant_ID = tenants.tenant_ID
        ORDER BY card_registration.unit_no ASC, card_registration.card_no ASC";

$query_exec_result = $conn->query($sql);

if ($query_exec_result === false) {
    $query_error = "Error fetching card registration data: " . $conn->error;
    error_log($query_error);
} else {
    $result = $query_exec_result;
}

// Group cards by unit for easier management
$cards_by_unit = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $unit = $row['unit_no'];
        if (!isset($cards_by_unit[$unit])) {
            $cards_by_unit[$unit] = [];
        }
        $cards_by_unit[$unit][] = $row;
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
        /* Card Registration Specific Styles */
        .mainContent {
            padding: 20px;
            overflow-y: auto;
        }

        .tenantHistoryHead {
            display: flex;
            justify-content: right;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .searbar {
            height: 30px;
            width: 270px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 12px;
            padding: 0 10px;
            box-sizing: border-box;
        }

        ::placeholder {
            color: #B7B5B5;
            opacity: 1;
        }

        .table-container {
            max-width: 100%;
            margin: 0 auto;
            border: 3px solid #A6DDFF;
            border-radius: 8px;
            height: 57vh;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .table-scroll {
            height: 100%;
            overflow-y: auto;
            overflow-x: auto;
            scrollbar-width: none;
        }

        .table-scroll::-webkit-scrollbar {
            display: none;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            font-size: 14px;
            border-bottom: 1px solid #e0e0e0;
            white-space: nowrap;
        }

        th {
            background-color: #e3f2fd;
            font-weight: bold;
            position: sticky;
            top: 0;
            z-index: 1;
            font-size: 12px;
        }

        .action-btn {
            background-color: #2196f3;
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }

        .action-btn:hover {
            background-color: #1976d2;
        }

        .footbtnContainer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .backbtn,
        .addtenantbtn {
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
        }

        .backbtn {
            min-width: 110px;
        }

        .addtenantbtn {
            min-width: 200px;
        }

        .addtenantbtnIcon {
            height: 20px;
            width: 20px;
            margin-right: 5px;
        }

        .footbtnContainer a:hover {
            background-color: white;
            color: #004AAD;
            border: 2px solid #004AAD;
        }

        .footbtnContainer a:hover .addtenantbtnIcon {
            content: url('UnitsInfoIcons/plusblue.png');
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .mainContent {
                padding: 15px;
            }

            .tenantHistoryHead {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
            }

            .searbar {
                width: 100%;
            }

            .table-container {
                border-left: none;
                border-right: none;
                border-radius: 0;
                max-height: calc(100vh - 280px);
            }

            .footbtnContainer {
                flex-direction: column;
                align-items: center;
            }

            .backbtn {
                order: 2;
                width: 80%;
                max-width: 280px;
            }

            .addtenantbtn {
                order: 1;
                width: 80%;
                max-width: 280px;
            }
        }

        @media (max-width: 768px) {
            .mainContent {
                padding: 10px;
            }

            table th, table td {
                font-size: 11px;
                padding: 10px 5px;
            }

            .action-btn {
                font-size: 10px;
                padding: 6px 8px;
            }
        }

        @media (max-width: 480px) {
            table th, table td {
                font-size: 10px;
                padding: 8px 3px;
            }

            .action-btn {
                font-size: 9px;
                padding: 5px 7px;
            }
        }
                .unit-group {
            background: #f8f9fa;
            padding: 10px;
            margin: 5px 0;
            border-left: 4px solid #007bff;
        }
        
        .unit-header {
            font-weight: bold;
            color: #007bff;
            margin-bottom: 5px;
        }
        
        .card-count {
            font-size: 0.9em;
            color: #666;
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
            <h4>Card Registration</h4>
            
            <div class="tenantHistoryHead">
                <input type="text" id="searchInput" placeholder="Search" class="searbar">
            </div>
            
            <div class="table-container">
                <div class="table-scroll">
                    <table id="cardTable">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Tenant ID</th>
                                <th>Tenant Name</th>
                                <th>Unit No</th>
                                <th>Card No</th>
                                <th>Card Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (!empty($query_error)) {
                                echo "<tr><td colspan='7' style='color:red; text-align:center;'>" . htmlspecialchars($query_error) . "</td></tr>";
                            } elseif (!empty($cards_by_unit)) {
                                $count = 1;
                                $displayed_units = [];
                                
                                foreach($cards_by_unit as $unit => $cards) {
                                    foreach($cards as $index => $row) {
                                        echo "<tr>";
                                        echo "<td>" . $count++ . "</td>";
                                        echo "<td>" . htmlspecialchars($row['tenant_ID'] ?? 'N/A') . "</td>";
                                        echo "<td>" . htmlspecialchars($row['tenant_name'] ?? 'N/A') . "</td>";
                                        echo "<td>" . htmlspecialchars($row['unit_no'] ?? 'N/A') . "</td>";
                                        echo "<td>" . htmlspecialchars($row['card_no'] ?? 'N/A') . "</td>";
                                        echo "<td>" . htmlspecialchars($row['card_status'] ?? 'N/A') . "</td>";
                                        
                                        // Show "Manage Unit Cards" link only once per unit
                                        if (!in_array($unit, $displayed_units)) {
                                            $view_details_params = "unit_no=" . urlencode($row['unit_no'] ?? '');
                                            echo "<td><a href='CARDRENEWORDELETE.php?" . $view_details_params . "' class='action-btn'>Manage Unit Cards (" . count($cards) . ")</a></td>";
                                            $displayed_units[] = $unit;
                                        } else {
                                            echo "<td style='color: #999;'>↑ Same Unit</td>";
                                        }
                                        
                                        echo "</tr>";
                                    }
                                }
                            } else {
                                echo "<tr><td colspan='7' style='text-align:center;'>No card registration records found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="footbtnContainer">
                <a href="DASHBOARD.php" class="backbtn">⤾ Back</a>
                <a href="CARDREGISTER.php" class="addtenantbtn">
                    <img src="UnitsInfoIcons/pluswht.png" alt="Plus Sign" class="addtenantbtnIcon">
                    New Card to Register
                </a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('searchInput').addEventListener('keyup', function () {
            var input, filter, table, tr, td, i, j, txtValue;
            input = document.getElementById("searchInput");
            filter = input.value.toLowerCase().trim();
            table = document.getElementById("cardTable");
            tr = table.getElementsByTagName("tr");
            let foundDataRow = false;

            for (i = 1; i < tr.length; i++) {
                let displayRow = false;
                if (tr[i].cells.length === 1 && tr[i].cells[0].colSpan === 7) {
                    continue;
                }

                td = tr[i].getElementsByTagName("td");
                for (j = 1; j < td.length - 1; j++) {
                    if (td[j]) {
                        txtValue = td[j].textContent || td[j].innerText;
                        if (txtValue.toLowerCase().indexOf(filter) > -1) {
                            displayRow = true;
                            break; 
                        }
                    }
                }
                if (displayRow) {
                    tr[i].style.display = "";
                    foundDataRow = true;
                } else {
                    tr[i].style.display = "none";
                }
            }

            const noRecordsRow = table.querySelector('td[colspan="7"]');
            if (noRecordsRow) {
                const originalDataRowsExist = Array.from(tr).slice(1).some(row => row.cells.length > 1 && row.cells[0].colSpan !== 7);

                if (filter !== "" && !foundDataRow && originalDataRowsExist) {
                    noRecordsRow.textContent = "No matching records found for your search.";
                    noRecordsRow.parentNode.style.display = "";
                } else if (!originalDataRowsExist) {
                    noRecordsRow.textContent = "No card registration records found";
                    noRecordsRow.parentNode.style.display = "";
                } else if (filter === "" && originalDataRowsExist) {
                     noRecordsRow.parentNode.style.display = "none";
                } else if (!foundDataRow && filter === "" && originalDataRowsExist){
                     noRecordsRow.parentNode.style.display = "none";
                } else {
                    noRecordsRow.parentNode.style.display = "none";
                }
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