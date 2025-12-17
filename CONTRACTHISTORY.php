<?php
session_start();

// Redirect to login if not logged in using 'email_account'
if (!isset($_SESSION['email_account'])) {
    header("Location: LOGIN.php");
    exit();
}

require_once 'db_connect.php';

// Initialize variables
$result = null;
$query_error = "";
$contracts_data = [];

// Modified SQL query to get contracts that are not confirmed and not contract renewal
$contracts_sql = "SELECT `contract_id`, `email_account`, `contract_date`, `full_name`, `citizenship`, 
                  `postal_address`, `contract_term`, `start_date`, `end_date`, `monthly_rate`, 
                  `security_deposit`, `contract_status` 
                  FROM `contract_information` 
                  WHERE contract_status != 'First Contract' AND contract_status != 'Contract Renewal' AND contract_status != 'pending'
                  ORDER BY contract_date DESC";

// Execute the query for contracts
$contracts_result = $conn->query($contracts_sql);

// Check if the query was successful
if ($contracts_result === false) {
    $query_error = "Error executing query: " . $conn->error;
    error_log($query_error);
} else {
    // Process contracts
    while ($contract_row = $contracts_result->fetch_assoc()) {
        $contracts_data[] = $contract_row;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contract History - RYC Dormitelle</title>
    <link rel="icon" type="image/png" href="otherIcons/pageicon.png">
    
    <!-- Include the layout CSS -->
    <link rel="stylesheet" href="layout.css">
    
    <!-- Contract History specific styles -->
    <style>
        /* Contract History Specific Styles */
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
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            overflow: hidden;
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
            min-width: 1200px;
        }
        
        th, td {
            padding: 10px 12px;
            text-align: left;
            font-size: 13px;
            border-bottom: 1px solid #e0e0e0;
            white-space: nowrap;
            vertical-align: middle;
            line-height: 1.4;
        }
        
        th {
            background-color: #e3f2fd;
            font-weight: bold;
            position: sticky;
            top: 0;
            z-index: 1;
            font-size: 12px;
        }
        
        /* Status styling */
        .status-active {
            background-color: #d4edda;
            color: #155724;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
        }
        
        .status-ended {
            background-color: #f8d7da;
            color: #721c24;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
        }
        
        /* Action button styling */
        .action-btn {
            padding: 4px 8px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 11px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            min-width: 60px;
            background-color: #ffc107;
            color: black;
        }
        
        .action-btn:hover {
            background-color: #e0a800;
        }
        
        .footbtnContainer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .backbtn {
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

        .footbtnContainer a:hover {
            background-color: white;
            color: #004AAD;
            border: 2px solid #004AAD;
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
                padding: 8px 5px;
            }
        }

        @media (max-width: 480px) {
            table th, table td {
                font-size: 10px;
                padding: 6px 3px;
            }

            .footbtnContainer {
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
            <h4>Contract History</h4>
            
            <div class="tenantHistoryHead">
                <input type="text" id="searchInput" placeholder="Search by Contract ID, Name, Email..." class="searbar">
            </div>
            
            <div class="table-container">
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Contract ID</th>
                                <th>Contract Date</th>
                                <th>Email Account</th>
                                <th>Full Name</th>
                                <th>Citizenship</th>
                                <th>Postal Address</th>
                                <th>Contract Term</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Monthly Rate</th>
                                <th>Security Deposit</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Check if there was a query error before trying to use data
                            if (!empty($query_error)) {
                                echo "<tr><td colspan='13' style='color: red; text-align: center;'>Error loading contracts: " . htmlspecialchars($query_error) . "</td></tr>";
                            } elseif (!empty($contracts_data)) {
                                foreach ($contracts_data as $contract) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($contract["contract_id"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($contract["contract_date"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($contract["email_account"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($contract["full_name"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($contract["citizenship"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($contract["postal_address"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($contract["contract_term"]) . " months</td>";
                                    echo "<td>" . htmlspecialchars($contract["start_date"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($contract["end_date"]) . "</td>";
                                    echo "<td>₱" . number_format((float)$contract["monthly_rate"], 2) . "</td>";
                                    echo "<td>₱" . number_format((float)$contract["security_deposit"], 2) . "</td>";
                                    
                                    // Status with styling
                                    $status_class = '';
                                    switch(strtolower($contract["contract_status"])) {
                                        case 'active':
                                            $status_class = 'status-active';
                                            break;
                                        case 'contract ended':
                                            $status_class = 'status-ended';
                                            break;
                                        default:
                                            $status_class = 'status-ended';
                                            break;
                                    }
                                    echo "<td><span class='" . $status_class . "'>" . htmlspecialchars($contract["contract_status"]) . "</span></td>";
                                    
                                    // Action button - View Deductions (read-only)
                                    echo "<td>";
                                    echo "<a href='SECURITYDEPOSITDEDUCTIONREPORT.php?contract_id=" . $contract['contract_id'] . "&view_only=1' class='action-btn'>View Deductions</a>";
                                    echo "</td>";
                                    
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='13' style='text-align: center;'>No contract history found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="footbtnContainer">
                <a href="CONTRACTMANAGEMENT.php" class="backbtn">⤾ Back</a>
            </div>
        </div>
    </div>
    
    <script>
        // Enhanced search functionality
        document.getElementById('searchInput').addEventListener('keyup', function () {
            const filter = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll('table tbody tr');

            rows.forEach(row => {
                let rowVisible = false;
                
                // Check if any cell in the row matches the search filter
                row.querySelectorAll('td').forEach(cell => {
                    if (cell.textContent.toLowerCase().includes(filter)) {
                        rowVisible = true;
                    }
                });
                
                // Show/hide row based on match
                row.style.display = rowVisible ? '' : 'none';
            });
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