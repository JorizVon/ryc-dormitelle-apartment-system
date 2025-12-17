<?php
session_start();

// Redirect to login if not logged in using 'email_account'
if (!isset($_SESSION['email_account'])) {
    header("Location: LOGIN.php");
    exit();
}

require_once 'db_connect.php';

// --- UPDATED DEFAULT SQL QUERY ---
// Fetches all access logs and joins with the tenants table to get the tenant's name.
// Using LEFT JOIN ensures that all logs are displayed, even if the tenant_ID is null or doesn't exist in the tenants table.
$sql = "SELECT
            access_logs.access_ID,
            access_logs.tenant_ID,
            tenants.tenant_name, -- Added tenant_name
            access_logs.unit_no,
            access_logs.date_and_time,
            access_logs.access_status
        FROM access_logs
        LEFT JOIN tenants ON access_logs.tenant_ID = tenants.tenant_ID
        ORDER BY access_logs.access_ID DESC";

// --- UPDATED SEARCH SQL QUERY ---
if (!empty($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $sql = "SELECT
                access_logs.access_ID,
                access_logs.tenant_ID,
                tenants.tenant_name, -- Added tenant_name
                access_logs.unit_no,
                access_logs.date_and_time,
                access_logs.access_status
            FROM access_logs
            LEFT JOIN tenants ON access_logs.tenant_ID = tenants.tenant_ID
            WHERE
               tenants.tenant_name LIKE '%$search%' -- Added search by tenant_name
               OR access_logs.tenant_ID LIKE '%$search%'
               OR access_logs.unit_no LIKE '%$search%'
               OR access_logs.date_and_time LIKE '%$search%'
               OR access_logs.access_status LIKE '%$search%'
            ORDER BY access_logs.access_ID DESC";
}

$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Point Logs - RYC Dormitelle</title>
    <link rel="icon" type="image/png" href="otherIcons/pageicon.png">
    
    <!-- Include the layout CSS -->
    <link rel="stylesheet" href="layout.css">
    
    <!-- Access Point Logs specific styles -->
    <style>
        /* Your existing CSS remains unchanged... */
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
            border-bottom: 1px solid #e0e0e0;
            font-size: 14px;
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
            justify-content: flex-start;
            align-items: center;
            margin-top: 20px;
        }

        .backbtn {
            height: 40px;
            min-width: 110px;
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

        .footbtnContainer a:hover {
            background-color: white;
            color: #004AAD;
            border: 2px solid #004AAD;
        }

        .na-value {
            color: #999;
            font-style: italic;
        }

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
                overflow-x: auto;
                max-height: calc(100vh - 280px);
            }

            .footbtnContainer {
                justify-content: center;
            }

            .backbtn {
                width: 80%;
                max-width: 250px;
            }
        }

        @media (max-width: 768px) {
            .mainContent {
                padding: 10px;
            }

            table th, table td {
                font-size: 12px;
                padding: 10px 8px;
            }
        }

        @media (max-width: 480px) {
            table th, table td {
                font-size: 10px;
                padding: 8px 5px;
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
            <h4>Access Point Logs</h4>
            
            <div class="tenantHistoryHead">
                <input type="text" id="searchInput" placeholder="Search by Name, Tenant ID, Unit No, Status..." class="searbar">
            </div>
            
            <div class="table-container">
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Date and Time</th>
                                <th>Tenant ID</th>
                                <th>Tenant Name</th> <!-- UPDATED: Added Table Header -->
                                <th>Unit No.</th>
                                <th>Access Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result && $result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['access_ID']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['date_and_time']) . "</td>";
                                    
                                    // Display N/A for tenant_ID if null or empty
                                    $tenant_ID = (!empty($row['tenant_ID'])) ? htmlspecialchars($row['tenant_ID']) : '<span class="na-value">N/A</span>';
                                    echo "<td>" . $tenant_ID . "</td>";
                                    
                                    // --- UPDATED: Display Tenant Name ---
                                    // Display N/A for tenant_name if null or empty (e.g., for a failed log where tenant is unknown)
                                    $tenant_name = (!empty($row['tenant_name'])) ? htmlspecialchars($row['tenant_name']) : '<span class="na-value">N/A</span>';
                                    echo "<td>" . $tenant_name . "</td>";
                                    
                                    // Display N/A for unit_no if null or empty
                                    $unit_no = (!empty($row['unit_no'])) ? htmlspecialchars($row['unit_no']) : '<span class="na-value">N/A</span>';
                                    echo "<td>" . $unit_no . "</td>";
                                    
                                    echo "<td>" . htmlspecialchars($row['access_status']) . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' style='text-align: center;'>No records found</td></tr>"; // Changed colspan to 6
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="footbtnContainer">
                <a href="DASHBOARD.php" class="backbtn">&#10558; Back</a>
            </div>
        </div>
    </div>
    
    <script>

        document.getElementById('searchInput').addEventListener('input', function() {
            const searchValue = this.value;

            // AJAX request
            const xhr = new XMLHttpRequest();
            // Using POST can be slightly better for sending data, but GET is fine here.
            xhr.open('GET', 'ACCESSPOINTLOGS.php?search=' + encodeURIComponent(searchValue), true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    // Find the table body and update it with the new results
                    const parser = new DOMParser();
                    const htmlDoc = parser.parseFromString(xhr.responseText, 'text/html');
                    const newTbody = htmlDoc.querySelector('tbody');
                    const oldTbody = document.querySelector('tbody');
                    if (newTbody && oldTbody) {
                        oldTbody.innerHTML = newTbody.innerHTML;
                    }
                }
            };
            xhr.send();
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