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
$tenants_data = [];

// Modified SQL query to get data from tenant_history table
$history_sql = "SELECT `unit_no`, `name`, `role`, `contact_no`, `permanent_address`, 
                `emergency_person`, `emergency_contact`, `start_date`, `end_date` 
                FROM `tenant_history`
                ORDER BY `unit_no`, `role` DESC";

// Execute the query
$history_result = $conn->query($history_sql);

// Check if the query was successful
if ($history_result === false) {
    $query_error = "Error executing query: " . $conn->error;
    error_log($query_error);
} else {
    // Process results and group by unit_no
    while ($row = $history_result->fetch_assoc()) {
        $unit_no = $row['unit_no'];
        
        if (!isset($tenants_data[$unit_no])) {
            $tenants_data[$unit_no] = [
                'representative' => null,
                'companions' => []
            ];
        }
        
        // Separate representatives and companions
        if ($row['role'] === 'representative') {
            $tenants_data[$unit_no]['representative'] = $row;
        } else {
            $tenants_data[$unit_no]['companions'][] = $row;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenants History - RYC Dormitelle</title>
    
    <!-- Include the layout CSS -->
    <link rel="stylesheet" href="layout.css">
    
    <!-- Tenants History specific styles -->
    <style>
        /* Tenants History Specific Styles */
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
        
        /* Dropdown styles */
        .dropdown-toggle {
            cursor: pointer;
            color: #004AAD;
            font-size: 14px;
            font-weight: bold;
            display: inline-block;
            width: 16px;
            text-align: center;
            user-select: none;
            transition: transform 0.3s ease;
            margin-right: 8px;
        }
        
        .dropdown-toggle.expanded {
            transform: rotate(90deg);
        }
        
        .dropdown-toggle:hover {
            color: #0066FF;
        }
        
        .dropdown-placeholder {
            display: inline-block;
            width: 16px;
            margin-right: 8px;
        }
        
        /* Companion rows styling */
        .companion-row {
            display: none;
            background-color: #f8f9fa;
        }
        
        .companion-row.show {
            display: table-row;
        }
        
        .companion-row td {
            color: #666;
            border-bottom: 1px solid #e9ecef;
        }
        
        .unit-cell {
            position: relative;
        }
        
        .companion-row .unit-cell {
            padding-left: 35px;
        }
        
        th:first-child {
            padding-left: 36px;
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
        }

        .backbtn {
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
                order: 2;
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
            <h4>Tenants History</h4>
            
            <div class="tenantHistoryHead">
                <input type="text" id="searchInput" placeholder="Search by Name, Unit, Contact, Address..." class="searbar">
            </div>
            
            <div class="table-container">
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Unit No</th>
                                <th>Name</th>
                                <th>Contact Number</th>
                                <th>Permanent Address</th>
                                <th>Emergency Person</th>
                                <th>Emergency Contact</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Check if there was a query error before trying to use data
                            if (!empty($query_error)) {
                                echo "<tr><td colspan='8' style='color: red; text-align: center;'>Error loading tenants: " . htmlspecialchars($query_error) . "</td></tr>";
                            } elseif (!empty($tenants_data)) {
                                foreach ($tenants_data as $unit_no => $unit_data) {
                                    $representative = $unit_data['representative'];
                                    $companions = $unit_data['companions'];
                                    
                                    if ($representative) {
                                        // Display representative row
                                        echo "<tr class='representative-row'>";
                                        
                                        // Unit No column with dropdown toggle if there are companions
                                        echo "<td class='unit-cell'>";
                                        if (count($companions) > 0) {
                                            echo "<span class='dropdown-toggle' onclick='toggleCompanions(\"unit_" . htmlspecialchars($unit_no) . "\")'>▶</span>";
                                        } else {
                                            echo "<span class='dropdown-placeholder'></span>";
                                        }
                                        echo htmlspecialchars($unit_no) . "</td>";
                                        
                                        echo "<td>" . htmlspecialchars($representative["name"]) . "</td>";
                                        echo "<td>" . htmlspecialchars($representative["contact_no"]) . "</td>";
                                        echo "<td>" . htmlspecialchars($representative["permanent_address"]) . "</td>";
                                        echo "<td>" . htmlspecialchars($representative["emergency_person"]) . "</td>";
                                        echo "<td>" . htmlspecialchars($representative["emergency_contact"]) . "</td>";
                                        echo "<td>" . htmlspecialchars($representative["start_date"]) . "</td>";
                                        echo "<td>" . htmlspecialchars($representative["end_date"]) . "</td>";
                                        echo "</tr>";
                                        
                                        // Display companion rows (initially hidden)
                                        foreach ($companions as $companion) {
                                            echo "<tr class='companion-row unit_" . htmlspecialchars($unit_no) . "'>";
                                            echo "<td class='unit-cell'>" . htmlspecialchars($unit_no) . "</td>";
                                            echo "<td>" . htmlspecialchars($companion["name"]) . "</td>";
                                            echo "<td>" . htmlspecialchars($companion["contact_no"]) . "</td>";
                                            echo "<td>" . htmlspecialchars($companion["permanent_address"]) . "</td>";
                                            echo "<td>" . htmlspecialchars($companion["emergency_person"]) . "</td>";
                                            echo "<td>" . htmlspecialchars($companion["emergency_contact"]) . "</td>";
                                            echo "<td>" . htmlspecialchars($companion["start_date"]) . "</td>";
                                            echo "<td>" . htmlspecialchars($companion["end_date"]) . "</td>";
                                            echo "</tr>";
                                        }
                                    }
                                }
                            } else {
                                echo "<tr><td colspan='8' style='text-align: center;'>No tenant history found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="footbtnContainer">
                <a href="TENANTSLIST.php" class="backbtn">⤾ Back</a>
            </div>
        </div>
    </div>
    
    <script>
        // Function to toggle companion rows
        function toggleCompanions(unitClass) {
            const companionRows = document.querySelectorAll('.' + unitClass);
            const dropdownToggle = document.querySelector('[onclick="toggleCompanions(\'' + unitClass + '\')"]');
            
            companionRows.forEach(row => {
                row.classList.toggle('show');
            });
            
            // Rotate the dropdown arrow
            if (dropdownToggle) {
                dropdownToggle.classList.toggle('expanded');
            }
        }
        
        // Enhanced search functionality
        document.getElementById('searchInput').addEventListener('keyup', function () {
            const filter = this.value.toLowerCase().trim();
            const representativeRows = document.querySelectorAll('.representative-row');
            const companionRows = document.querySelectorAll('.companion-row');
            
            // Hide all companion rows first when searching
            companionRows.forEach(row => {
                row.classList.remove('show');
            });
            
            // Reset all dropdown toggles
            document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
                toggle.classList.remove('expanded');
            });

            representativeRows.forEach(row => {
                let rowVisible = false;
                let unitVisible = false;
                
                // Check if representative matches
                row.querySelectorAll('td').forEach(cell => {
                    if (cell.textContent.toLowerCase().includes(filter)) {
                        rowVisible = true;
                        unitVisible = true;
                    }
                });
                
                // Get the unit class for this representative
                const dropdownToggle = row.querySelector('.dropdown-toggle');
                if (dropdownToggle) {
                    const onclickAttr = dropdownToggle.getAttribute('onclick');
                    const match = onclickAttr.match(/'([^']+)'/);
                    if (match) {
                        const unitClass = match[1];
                        const unitCompanions = document.querySelectorAll('.' + unitClass);
                        
                        // Check if any companion matches
                        unitCompanions.forEach(companionRow => {
                            let companionMatches = false;
                            companionRow.querySelectorAll('td').forEach(cell => {
                                if (cell.textContent.toLowerCase().includes(filter)) {
                                    companionMatches = true;
                                    unitVisible = true;
                                }
                            });
                            
                            // Show/hide individual companion based on match
                            if (filter === '' || companionMatches) {
                                companionRow.style.display = unitVisible ? '' : 'none';
                                if (companionMatches && filter !== '') {
                                    companionRow.classList.add('show');
                                    dropdownToggle.classList.add('expanded');
                                }
                            } else {
                                companionRow.style.display = 'none';
                            }
                        });
                    }
                }
                
                // Show/hide representative row
                row.style.display = unitVisible ? '' : 'none';
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