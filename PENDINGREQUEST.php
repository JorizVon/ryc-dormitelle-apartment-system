<?php
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Redirect to login if not logged in using 'email_account'
if (!isset($_SESSION['email_account'])) {
    header("Location: LOGIN.php");
    exit();
}

require_once 'db_connect.php';
require_once __DIR__ . "/dompdf/autoload.inc.php";
use Dompdf\Dompdf;

date_default_timezone_set('Asia/Manila');

// Function to log to both PHP error log and browser console
function debug_log($message, $to_browser = true) {
    error_log($message); // Always log to server's PHP error log
    if ($to_browser) {
        echo "<script>console.log(" . json_encode($message) . ");</script>\n"; // Log to browser console only if requested
    }
}

// Function to generate monthly due dates for payment checklist - UPDATED to start from next month
function generateMonthlyDueDates($start_date, $end_date) {
    $due_dates = [];
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    
    // Get the day from start_date to use as due day
    $due_day = (int)$start->format('j');
    
    // Start from NEXT MONTH after contract start
    $start->modify('first day of next month');
    
    // Get starting year and month
    $current_year = (int)$start->format('Y');
    $current_month = (int)$start->format('n');
    
    $end_year = (int)$end->format('Y');
    $end_month = (int)$end->format('n');
    
    // Generate dates for each month by iterating year/month separately
    while (($current_year < $end_year) || ($current_year == $end_year && $current_month <= $end_month)) {
        // Get the last day of the current month
        $last_day_of_month = cal_days_in_month(CAL_GREGORIAN, $current_month, $current_year);
        
        // Use the due_day or last day of month, whichever is smaller
        $actual_day = min($due_day, $last_day_of_month);
        
        // Create the due date
        $due_date = new DateTime();
        $due_date->setDate($current_year, $current_month, $actual_day);
        
        // Only add if it's within the range
        if ($due_date >= $start && $due_date <= $end) {
            $due_dates[] = $due_date->format('Y-m-d');
        }
        
        // Move to next month
        $current_month++;
        if ($current_month > 12) {
            $current_month = 1;
            $current_year++;
        }
    }
    
    return $due_dates;
}

// Handle row deletion - UPDATED to delete tenant_unit and contract_information
if (isset($_POST['cancel_request']) && isset($_POST['contract_id'])) {
    $contract_id = $_POST['contract_id'];

    // First, get the unit_no and email_account using contract_id
    $get_info_sql = "SELECT ci.`email_account`, pr.`unit_no` 
                     FROM `contract_information` ci
                     LEFT JOIN `pending_reservation` pr ON ci.email_account = pr.email_account
                     WHERE ci.`contract_id` = ? AND ci.`contract_status` = 'pending'";
    $get_info_stmt = $conn->prepare($get_info_sql);
    $unit_no_to_update = null;
    $email_account = null;
    
    if ($get_info_stmt) {
        $get_info_stmt->bind_param("i", $contract_id);
        $get_info_stmt->execute();
        $info_result = $get_info_stmt->get_result();
        if ($info_result && $info_result->num_rows > 0) {
            $info_row = $info_result->fetch_assoc();
            $unit_no_to_update = $info_row['unit_no'];
            $email_account = $info_row['email_account'];
        }
        $get_info_stmt->close();
    } else {
        debug_log("Failed to prepare get info statement: " . $conn->error);
    }

    if ($email_account) {
        $conn->begin_transaction();
        try {
            // Delete the contract information using contract_id
            $delete_contract_sql = "DELETE FROM `contract_information` WHERE `contract_id` = ? AND contract_status = 'pending'";
            $delete_contract_stmt = $conn->prepare($delete_contract_sql);
            if ($delete_contract_stmt) {
                $delete_contract_stmt->bind_param("i", $contract_id);
                if (!$delete_contract_stmt->execute()) {
                    throw new Exception("Failed to delete contract for contract_id: " . $contract_id . " - Error: " . $delete_contract_stmt->error);
                }
                $delete_contract_stmt->close();
                debug_log("Contract information deleted successfully for contract_id: " . $contract_id);
            } else {
                throw new Exception("Failed to prepare contract delete statement: " . $conn->error);
            }
            
            // Delete the pending reservation
            $delete_sql = "DELETE FROM `pending_reservation` WHERE `email_account` = ? AND confirmation_status = 'pending'";
            $delete_stmt = $conn->prepare($delete_sql);
            if ($delete_stmt) {
                $delete_stmt->bind_param("s", $email_account);
                if (!$delete_stmt->execute()) {
                    throw new Exception("Failed to delete pending reservation for email: " . $email_account . " - Error: " . $delete_stmt->error);
                }
                $delete_stmt->close();
                debug_log("Pending reservation deleted successfully for email: " . $email_account);
            } else {
                throw new Exception("Failed to prepare pending reservation delete statement: " . $conn->error);
            }
            
            // Delete tenant_unit entries (NEW)
            if ($unit_no_to_update) {
                $delete_tenant_unit_sql = "DELETE FROM `tenant_unit` WHERE `unit_no` = ? AND `status` = 'pending'";
                $delete_tenant_unit_stmt = $conn->prepare($delete_tenant_unit_sql);
                if ($delete_tenant_unit_stmt) {
                    $delete_tenant_unit_stmt->bind_param("s", $unit_no_to_update);
                    if (!$delete_tenant_unit_stmt->execute()) {
                        throw new Exception("Failed to delete tenant_unit for unit: " . $unit_no_to_update . " - Error: " . $delete_tenant_unit_stmt->error);
                    }
                    $delete_tenant_unit_stmt->close();
                    debug_log("Tenant_unit deleted successfully for unit: " . $unit_no_to_update);
                } else {
                    throw new Exception("Failed to prepare delete tenant_unit statement: " . $conn->error);
                }
            }
            
            // Update unit status to 'Available' if unit_no was found
            if ($unit_no_to_update) {
                $update_unit_sql = "UPDATE `units` SET `unit_status`='Available' WHERE `unit_no` = ?";
                $update_unit_stmt = $conn->prepare($update_unit_sql);
                if ($update_unit_stmt) {
                    $update_unit_stmt->bind_param("s", $unit_no_to_update);
                    if (!$update_unit_stmt->execute()) {
                        throw new Exception("Failed to update unit status for unit: " . $unit_no_to_update . " - Error: " . $update_unit_stmt->error);
                    }
                    $update_unit_stmt->close();
                    debug_log("Unit status updated to 'Available' successfully for unit: " . $unit_no_to_update);
                } else {
                    throw new Exception("Failed to prepare update unit status statement: " . $conn->error);
                }
            }
            
            $conn->commit();
            
            // Redirect to refresh the page and show updated data
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            debug_log("Error during cancellation: " . $e->getMessage());
        }
    }
}

if (isset($_POST['generate_pdf']) && isset($_POST['contract_id'])) {
    $contract_id = $_POST['contract_id'];
    
    $admin_sql = "SELECT admin_name, civil_status, admin_nationality FROM admin_profile LIMIT 1";
    $admin_result = $conn->query($admin_sql);
    $admin_data = [];
    if ($admin_result && $admin_result->num_rows > 0) {
        $admin_data = $admin_result->fetch_assoc();
    } else {
        // Provide default values if admin profile is not found
        debug_log("Warning: Admin profile not found. Using default values for contract.", false);
        $admin_data = [
            'admin_name' => 'ADMIN NAME NOT FOUND',
            'civil_status' => 'N/A',
            'admin_nationality' => 'N/A'
        ];
    }

    // Query to get contract information using contract_id
    $pdf_sql = "SELECT ci.`contract_id`, ci.`email_account`, ci.`contract_date`, ci.full_name, ci.`citizenship`, 
                 CONCAT(pr.`permanent_address`, ', ', ci.`postal_address`) AS tenant_address, ci.`contract_term`, ci.`start_date`, ci.`end_date`, ci.`monthly_rate`, ci.`security_deposit`
                FROM `contract_information` ci
                INNER JOIN `pending_reservation` pr ON ci.email_account = pr.email_account
                WHERE ci.`contract_id` = ? AND ci.`contract_status` = 'pending' AND pr.confirmation_status = 'pending'";

    $stmt = $conn->prepare($pdf_sql);
    if ($stmt) {
        $stmt->bind_param("i", $contract_id);
        $stmt->execute();
        $pdf_result = $stmt->get_result();

        if ($pdf_result && $pdf_result->num_rows > 0) {
            $contract_data = $pdf_result->fetch_assoc();
            $selected_email = $contract_data['email_account'];

            // Number to words function
            function numberToWords($num) {
                $num = (int)$num; if ($num === 0) return "Zero";
                $ones = [0 => "Zero", 1 => "One", 2 => "Two", 3 => "Three", 4 => "Four", 5 => "Five", 6 => "Six", 7 => "Seven", 8 => "Eight", 9 => "Nine", 10 => "Ten", 11 => "Eleven", 12 => "Twelve", 13 => "Thirteen", 14 => "Fourteen", 15 => "Fifteen", 16 => "Sixteen", 17 => "Seventeen", 18 => "Eighteen", 19 => "Nineteen"];
                $tens = [2 => "Twenty", 3 => "Thirty", 4 => "Forty", 5 => "Fifty", 6 => "Sixty", 7 => "Seventy", 8 => "Eighty", 9 => "Ninety"];
                $powers = ["", "Thousand", "Million", "Billion"];
                $numStr = str_pad((string)$num, ceil(strlen((string)$num)/3)*3, "0", STR_PAD_LEFT);
                $groups = str_split($numStr, 3); $parts = [];
                foreach ($groups as $i => $grp) {
                    $n = (int)$grp; if ($n === 0) continue;
                    $hundreds = intdiv($n, 100); $rem = $n % 100; $chunk = [];
                    if ($hundreds) $chunk[] = $ones[$hundreds] . " Hundred";
                    if ($rem) { if ($rem < 20) { $chunk[] = $ones[$rem]; } else { $chunk[] = $tens[intdiv($rem, 10)] . (($rem % 10) ? " " . $ones[$rem % 10] : ""); }}
                    $powerIdx = count($groups) - $i - 1; if ($powerIdx > 0) $chunk[] = $powers[$powerIdx];
                    $parts[] = implode(" ", $chunk);
                } return implode(" ", $parts);
            }

            // Normalize numeric inputs
            function toNumber($val) {
                if ($val === null || $val === '') return 0.0;
                $val = preg_replace('/[^\d.\-]/', '', (string)$val);
                return (float)$val;
            }

            // Prepare template data
            $rentAmount = toNumber($contract_data['monthly_rate'] ?? 0);
            $securityDeposit = toNumber($contract_data['security_deposit'] ?? 0);

            $tpl = [
                'tenant_name'        => trim($contract_data['full_name'] ?? ''),
                'citizenship'        => trim($contract_data['citizenship'] ?? ''),
                'tenant_address'     => trim($contract_data['tenant_address'] ?? ''),
                'lease_term'         => trim($contract_data['contract_term'] ?? ''),
                'start_date'         => $contract_data['start_date'] ?? '',
                'end_date'           => $contract_data['end_date'] ?? '',
                'rent_amount'        => $rentAmount,
                'rent_words'         => $rentAmount > 0 ? numberToWords((int)$rentAmount) : '',
                'security_deposit'   => $securityDeposit,
                'day'                => date('d'),
                'month'              => date('F'),
                'year'               => date('Y'),
                'property_address'   => 'Daet, Camarines Norte',
                'admin_name'         => strtoupper(trim($admin_data['admin_name'])),
                'admin_civil_status' => trim($admin_data['civil_status']),
                'admin_nationality'  => trim($admin_data['admin_nationality'])
            ];

            $tpl['start_date_formatted'] = !empty($tpl['start_date']) ? date("F j, Y", strtotime($tpl['start_date'])) : 'N/A';
            $tpl['end_date_formatted']   = !empty($tpl['end_date']) ? date("F j, Y", strtotime($tpl['end_date'])) : 'N/A';
            
            // Calculate lease duration
            $duration_parts = [];
            if (!empty($tpl['start_date']) && !empty($tpl['end_date'])) {
                $start_dt = new DateTime($tpl['start_date']);
                $end_dt   = new DateTime($tpl['end_date']);
                $interval = $start_dt->diff($end_dt);
                $years = $interval->y;
                $months = $interval->m;
                
                if ($years > 0) $duration_parts[] = $years . " " . ($years === 1 ? "YEAR" : "YEARS");
                if ($months > 0) $duration_parts[] = $months . " " . ($months === 1 ? "MONTH" : "MONTHS");
            }
            $tpl['lease_duration'] = !empty($duration_parts) ? implode(" and ", $duration_parts) : "0 MONTHS";

            // Get representative data for tenant insertion
            $rep_sql = "SELECT `unit_no`, `full_name`, `contact_no`, `email_account`, `pref_move_date`, `permanent_address`, `ec_person`, `ec_no`
                        FROM `pending_reservation`
                        WHERE `email_account` = ? AND `role` = 'representative' AND `confirmation_status` = 'pending'";
            $rep_stmt = $conn->prepare($rep_sql);
            if ($rep_stmt) {
                $rep_stmt->bind_param("s", $selected_email);
                $rep_stmt->execute();
                $rep_result = $rep_stmt->get_result();
                $rep_data = $rep_result->fetch_assoc();
                $rep_stmt->close();
            } else {
                debug_log("CRITICAL: Failed to prepare representative query: " . $conn->error, false);
                $rep_data = null;
            }

            if ($rep_data) {
                // Generate payment checklist entries - UPDATED with unit_no
                $monthly_due_dates = generateMonthlyDueDates($contract_data['start_date'], $contract_data['end_date']);
                
                foreach ($monthly_due_dates as $due_date) {
                    $insert_checklist_sql = "INSERT INTO `payment_checklist`(`unit_no`, `email_account`, `monthly_due_dates`, `pay_status`) VALUES (?, ?, ?, 0)";
                    $insert_checklist_stmt = $conn->prepare($insert_checklist_sql);
                    if ($insert_checklist_stmt) {
                        $insert_checklist_stmt->bind_param("sss", $rep_data['unit_no'], $selected_email, $due_date);
                        if ($insert_checklist_stmt->execute()) {
                            debug_log("Payment checklist entry created for date: " . $due_date, false);
                        } else {
                            debug_log("Failed to create payment checklist entry: " . $insert_checklist_stmt->error, false);
                        }
                        $insert_checklist_stmt->close();
                    } else {
                        debug_log("Failed to prepare payment checklist insert: " . $conn->error, false);
                    }
                }

                // Get all tenants (representative + companions) for the same unit and with pending status
                $all_tenants_sql = "SELECT `full_name`, `role`, `contact_no`, `email_account`, `permanent_address`, `ec_person`, `ec_no`
                                   FROM `pending_reservation`
                                   WHERE `unit_no` = ? AND `confirmation_status` = 'pending'
                                   ORDER BY CASE WHEN `role` = 'representative' THEN 1 ELSE 2 END, `email_account`";
                $all_tenants_stmt = $conn->prepare($all_tenants_sql);
                if ($all_tenants_stmt) {
                    $all_tenants_stmt->bind_param("s", $rep_data['unit_no']);
                    $all_tenants_stmt->execute();
                    $all_tenants_result = $all_tenants_stmt->get_result();

                    $tenant_counter = 1;
                    $representative_tenant_id = "";
                    $occupant_count = 0;
                    $tenants_data = array();

                    // Collect all tenant data first
                    while ($tenant = $all_tenants_result->fetch_assoc()) {
                        $tenants_data[] = $tenant;
                        $occupant_count++;
                    }
                    $all_tenants_stmt->close();

                    // Insert each tenant into tenants table
                    foreach ($tenants_data as $tenant) {
                        // Generate tenant_ID format: YYYYMMDDUNITNUMBERPOSITION
                        $current_date = date('Ymd');
                        $clean_unit_no = preg_replace('/[^a-zA-Z0-9]/', '', $rep_data['unit_no']);
                        $tenant_id = $current_date . $clean_unit_no . str_pad($tenant_counter, 2, '0', STR_PAD_LEFT);

                        if ($tenant['role'] === 'representative') {
                            $representative_tenant_id = $tenant_id;
                        }

                        // Insert into tenants table
                        $insert_tenant_sql = "INSERT INTO `tenants`(`tenant_ID`, `tenant_name`, `role`, `contact_no`, `email`, `permanent_address`, `ec_person`, `ec_no`)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                        $insert_tenant_stmt = $conn->prepare($insert_tenant_sql);
                        if ($insert_tenant_stmt) {
                            $insert_tenant_stmt->bind_param("ssssssss",
                                $tenant_id,
                                $tenant['full_name'],
                                $tenant['role'],
                                $tenant['contact_no'],
                                $tenant['email_account'],
                                $tenant['permanent_address'],
                                $tenant['ec_person'],
                                $tenant['ec_no']
                            );

                            if ($insert_tenant_stmt->execute()) {
                                debug_log("Tenant inserted successfully: " . $tenant_id . " - Name: " . $tenant['full_name'] . " - Role: " . $tenant['role'], false);
                            } else {
                                debug_log("Failed to insert tenant: " . $tenant_id . " - Error: " . $insert_tenant_stmt->error, false);
                            }
                            $insert_tenant_stmt->close();
                        } else {
                            debug_log("CRITICAL: Failed to prepare insert tenant statement: " . $conn->error, false);
                        }
                        $tenant_counter++;
                    }
                } else {
                    debug_log("CRITICAL: Failed to prepare all tenants query: " . $conn->error, false);
                }

               if (!empty($representative_tenant_id)) {
                    // First, get the current tenant_ID from tenant_unit for this unit
                    $get_current_tenant_id_sql = "SELECT `tenant_ID` FROM `tenant_unit` WHERE `unit_no` = ? AND `status` = 'pending' LIMIT 1";
                    $get_current_tenant_id_stmt = $conn->prepare($get_current_tenant_id_sql);
                    
                    if ($get_current_tenant_id_stmt) {
                        $get_current_tenant_id_stmt->bind_param("s", $rep_data['unit_no']);
                        $get_current_tenant_id_stmt->execute();
                        $current_tenant_id_result = $get_current_tenant_id_stmt->get_result();
                        
                        if ($current_tenant_id_result && $current_tenant_id_result->num_rows > 0) {
                            $current_tenant_id_row = $current_tenant_id_result->fetch_assoc();
                            $old_tenant_id = $current_tenant_id_row['tenant_ID'];
                            
                            debug_log("Found existing tenant_ID in tenant_unit: " . $old_tenant_id . ", will update to: " . $representative_tenant_id, false);
                            
                            // UPDATE tenant_unit with the new tenant_ID and change status from 'pending' to 'Active'
                            $update_tenant_unit_sql = "UPDATE `tenant_unit` 
                                                      SET `tenant_ID` = ?, `status` = 'Active' 
                                                      WHERE `unit_no` = ? AND `status` = 'pending'";
                            $update_tenant_unit_stmt = $conn->prepare($update_tenant_unit_sql);
                            
                            if ($update_tenant_unit_stmt) {
                                $update_tenant_unit_stmt->bind_param("ss", $representative_tenant_id, $rep_data['unit_no']);
                                
                                if ($update_tenant_unit_stmt->execute()) {
                                    debug_log("Tenant unit updated successfully - tenant_ID changed from " . $old_tenant_id . " to " . $representative_tenant_id . " and status set to 'Active' for unit: " . $rep_data['unit_no'], false);
                                } else {
                                    debug_log("CRITICAL: Failed to update tenant_unit for unit: " . $rep_data['unit_no'] . " - MySQL Error: " . $update_tenant_unit_stmt->error, false);
                                }
                                $update_tenant_unit_stmt->close();
                            } else {
                                debug_log("CRITICAL: Failed to prepare update tenant_unit statement: " . $conn->error, false);
                            }
                        } else {
                            debug_log("WARNING: No pending tenant_unit record found for unit: " . $rep_data['unit_no'], false);
                        }
                        $get_current_tenant_id_stmt->close();
                    } else {
                        debug_log("CRITICAL: Failed to prepare get current tenant_ID statement: " . $conn->error, false);
                    }
                } else {
                    debug_log("WARNING: Representative tenant_ID is empty, cannot update tenant_unit", false);
                }
            }
        } else {
            debug_log("CRITICAL: No contract information found for contract_id: " . $contract_id, false);
            echo "Error: Contract information not found. Cannot generate PDF.";
            exit();
        }
        $stmt->close();
    } else {
        debug_log("CRITICAL: Failed to prepare contract information query for PDF generation: " . $conn->error, false);
        echo "Error: Database query failed. Cannot generate PDF.";
        exit();
    }

    // Render HTML from contract template
    ob_start();
    include __DIR__ . "/contract_template.php";
    $html = ob_get_clean();

    // Generate PDF
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // Save PDF to contracts folder
    $contractsDir = __DIR__ . "/contracts";
    if (!is_dir($contractsDir)) mkdir($contractsDir, 0777, true);

    $safe_tenant_name = preg_replace('/[^a-zA-Z0-9_]/', '_', $tpl['tenant_name'] ?? 'UnknownTenant');
    $pdf_file_name = "Contract_" . $safe_tenant_name . "_" . date('Ymd_His') . ".pdf";
    $server_file_path = $contractsDir . "/" . $pdf_file_name;
    file_put_contents($server_file_path, $dompdf->output());

    // Update contract status using contract_id
    $update_contract_sql = "UPDATE `contract_information` SET `contract_status` = 'First Contract' WHERE `contract_id` = ?";
    $update_contract_stmt = $conn->prepare($update_contract_sql);
    if ($update_contract_stmt) {
        $update_contract_stmt->bind_param("i", $contract_id);

        if ($update_contract_stmt->execute()) {
            debug_log("Contract confirmation status updated successfully for contract_id: " . $contract_id, false);
        } else {
            debug_log("Failed to update contract confirmation status for contract_id: " . $contract_id . " - Error: " . $update_contract_stmt->error, false);
        }
        $update_contract_stmt->close();
    } else {
        debug_log("Failed to prepare update contract_information status statement: " . $conn->error, false);
    }

    // Update unit status to 'Occupied'
    if (isset($rep_data['unit_no'])) {
        $update_unit_sql = "UPDATE `units` SET `unit_status`='Occupied' WHERE `unit_no` = ?";
        $update_unit_stmt = $conn->prepare($update_unit_sql);
        if ($update_unit_stmt) {
            $update_unit_stmt->bind_param("s", $rep_data['unit_no']);

            if ($update_unit_stmt->execute()) {
                debug_log("Unit status updated to 'Occupied' successfully for unit: " . $rep_data['unit_no'], false);
            } else {
                debug_log("Failed to update unit status for unit: " . $rep_data['unit_no'] . " - Error: " . $update_unit_stmt->error, false);
            }
            $update_unit_stmt->close();
        } else {
            debug_log("Failed to prepare update unit status statement: " . $conn->error, false);
        }
    }

    // Update user_type to 'tenant' for all tenants in the unit
    if (isset($rep_data['unit_no'])) {
        $update_accounts_sql = "UPDATE `accounts` 
                               INNER JOIN `pending_reservation` 
                               ON accounts.email_account = pending_reservation.email_account 
                               SET accounts.user_type = 'tenant' 
                               WHERE pending_reservation.unit_no = ?";
        $update_accounts_stmt = $conn->prepare($update_accounts_sql);
        if ($update_accounts_stmt) {
            $update_accounts_stmt->bind_param("s", $rep_data['unit_no']);

            if ($update_accounts_stmt->execute()) {
                debug_log("User type updated to 'tenant' successfully for all accounts in unit: " . $rep_data['unit_no'], false);
            } else {
                debug_log("Failed to update user type for accounts in unit: " . $rep_data['unit_no'] . " - Error: " . $update_accounts_stmt->error, false);
            }
            $update_accounts_stmt->close();
        } else {
            debug_log("Failed to prepare update accounts user_type statement: " . $conn->error, false);
        }
    }

    if (isset($rep_data['unit_no'])) { 
        $delete_sql = "DELETE FROM `pending_reservation` WHERE `email_account` = ? AND confirmation_status = 'pending'";
        $delete_stmt = $conn->prepare($delete_sql);
        if ($delete_stmt) {
            $delete_stmt->bind_param("s", $selected_email);

            if ($delete_stmt->execute()) {
                debug_log("Pending reservation deleted successfully for email: " . $selected_email);
            } else {
                debug_log("Failed to delete pending reservation for email: " . $selected_email . " - Error: " . $delete_stmt->error);
            }
            $delete_stmt->close();
        } else {
            debug_log("Failed to prepare pending reservation delete statement: " . $conn->error);
        }
    }
    

    header("Content-Type: application/pdf");
    header("Content-Disposition: inline; filename=\"" . $pdf_file_name . "\"");
    header("Content-Length: " . filesize($server_file_path));
    header("Cache-Control: private, max-age=0, must-revalidate");
    header("Pragma: public");
    ob_clean();
    flush();
    readfile($server_file_path);
    exit;
}

// Initialize $result to null and $query_error
$result = null;
$query_error = "";

// Updated SQL query to include contract_id for proper identification
$sql = "SELECT
            pr.`request_type`,
            pr.`reservation_date`,
            pr.`unit_no`,
            pr.`full_name`,
            pr.`contact_no`,
            pr.`email_account`,
            pr.`pref_move_date`,
            pr.`permanent_address`,
            pr.`ec_person`,
            pr.`ec_no`,
            ci.`contract_id`
        FROM `pending_reservation` pr
        INNER JOIN `contract_information` ci ON pr.email_account = ci.email_account
        WHERE pr.`role` = 'representative' 
        AND pr.`confirmation_status` = 'pending'
        AND ci.`contract_status` = 'pending'
        ORDER BY pr.`reservation_date` DESC";

$query_exec_result = $conn->query($sql);

if ($query_exec_result === false) {
    debug_log("Error fetching pending inquiries: " . $conn->error);
    $query_error = "Error fetching pending inquiries: " . $conn->error;
} else {
    $result = $query_exec_result;
}

$adminDisplayIdentifier = "ADMIN";
if (isset($_SESSION['email_account'])) {
    $adminDisplayIdentifier = htmlspecialchars(strtok($_SESSION['email_account'], '@'));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Request - RYC Dormitelle</title>
    <link rel="icon" type="image/png" href="otherIcons/pageicon.png">
    
    <!-- Include the layout CSS -->
    <link rel="stylesheet" href="layout.css">

        <style>
        /* Pending Request Specific Styles */
        .mainContent {
            padding: 20px;
            overflow-y: auto;
        }

        .pageHeader {
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
            font-size: 12px;
            margin-right: 5px;
        }

        .action-btn:hover {
            background-color: #1976d2;
        }

        .confirm-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 12px;
        }

        .confirm-btn:hover {
            background-color: #45a049;
        }

        .cancel-btn {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 12px;
        }

        .cancel-btn:hover {
            background-color: #d32f2f;
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

        /* Responsive Design */
        @media (max-width: 1024px) {
            .mainContent {
                padding: 15px;
            }

            .pageHeader {
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
                font-size: 11px;
                padding: 8px 5px;
            }

            .action-btn, .confirm-btn, .cancel-btn {
                font-size: 10px;
                padding: 6px 8px;
            }
        }

        @media (max-width: 480px) {
            table th, table td {
                font-size: 10px;
                padding: 6px 3px;
            }

            .action-btn, .confirm-btn, .cancel-btn {
                font-size: 9px;
                padding: 5px 6px;
            }
        }
         .modal {
        display: none; 
        position: fixed; 
        z-index: 1000; 
        left: 0;
        top: 0;
        width: 100%; 
        height: 100%; 
        overflow: auto; 
        background-color: rgb(0,0,0); 
        background-color: rgba(0,0,0,0.4); 
    }

    .modal-content {
        background-color: #fefefe;
        margin: 15% auto; 
        padding: 20px;
        border: 1px solid #888;
        width: 80%; 
        max-width: 400px;
        text-align: center;
        border-radius: 8px;
    }
    
    .modal-content p {
        margin-bottom: 20px;
    }

    .modal-buttons {
        display: flex;
        justify-content: center;
        gap: 20px;
    }

    .modal-buttons button {
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
    }

    #confirmYesBtn {
        background-color: #4CAF50;
        color: white;
    }

    #confirmNoBtn {
        background-color: #f44336;
        color: white;
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
            <h4>Pending Request</h4>
            
            <div class="pageHeader">
                <input type="text" id="searchInput" placeholder="Search" class="searbar">
            </div>
            
            <div class="table-container">
                <div class="table-scroll">
                    <table id="pendingInquiryTable">
                        <thead>
                            <tr>
                                <th>Request Type</th>
                                <th>Reservation Date</th>
                                <th>Unit No</th>
                                <th>Full Name</th>
                                <th>Contact No</th>
                                <th>Email</th>
                                <th>Pref. Move-In</th>
                                <th>Permanent Address</th>
                                <th>Emergency Contact Person</th>
                                <th>Emergency Contact No</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (!empty($query_error)) {
                                echo "<tr><td colspan='11' style='color:red; text-align:center;'>" . htmlspecialchars($query_error) . "</td></tr>";
                            } elseif ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                            ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['request_type'] ?? 'N/A'); ?></td>
                                        <td><?php echo $row['reservation_date'] ? htmlspecialchars(date("M d, Y h:i A", strtotime($row['reservation_date']))) : 'N/A'; ?></td>
                                        <td><?php echo htmlspecialchars($row['unit_no'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($row['full_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($row['contact_no'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($row['email_account'] ?? 'N/A'); ?></td>
                                        <td><?php echo $row['pref_move_date'] ? htmlspecialchars(date("M d, Y", strtotime($row['pref_move_date']))) : 'N/A'; ?></td>
                                        <td><?php echo htmlspecialchars($row['permanent_address'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($row['ec_person'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($row['ec_no'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php
                                                $contract_id = $row["contract_id"] ?? '';
                                                $request_type = strtolower($row["request_type"] ?? '');
                                            ?>
                                            <?php if (!empty($contract_id)): ?>
                                                <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to cancel this request? This will:\n\n- Delete the contract information\n- Delete the pending reservation\n- Set the unit status back to Available\n\nThis action cannot be undone.');">
                                                    <input type="hidden" name="contract_id" value="<?php echo htmlspecialchars($contract_id); ?>">
                                                    <button type="submit" name="cancel_request" class="cancel-btn">Cancel</button>
                                                </form>
                                                <?php if ($request_type === 'reservation'): ?>
                                                    <form method="post" class="confirm-form" style="display: inline;">
                                                        <input type="hidden" name="contract_id" value="<?php echo htmlspecialchars($contract_id); ?>">
                                                        <input type="hidden" name="generate_pdf" value="true">
                                                        <button type="submit" class="confirm-btn">Confirm</button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span style="color: #999; font-size: 12px;">No Action Available</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                            <?php
                                }
                            } else {
                                echo "<tr><td colspan='11' style='text-align:center;'>No pending inquiries found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="footbtnContainer">
                <a href="DASHBOARD.php" class="backbtn">⤾ Back</a>
            </div>
        </div>
    </div>
        <div id="confirmationModal" class="modal">
        <div class="modal-content">
            <p>Are you sure you want to confirm this user's reservation request?</p>
            <div class="modal-buttons">
                <button id="confirmYesBtn">Yes</button>
                <button id="confirmNoBtn">No</button>
            </div>
        </div>
    </div>
    
    <div id="successModal" class="modal">
        <div class="modal-content">
            <p>Success! The reservation has been confirmed. The contract form download will now commence .</p>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
    const confirmationModal = document.getElementById('confirmationModal');
    const successModal = document.getElementById('successModal');
    const confirmYesBtn = document.getElementById('confirmYesBtn');
    const confirmNoBtn = document.getElementById('confirmNoBtn');
    let formToSubmit = null;

    document.querySelectorAll('.confirm-form').forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            formToSubmit = event.target;
            confirmationModal.style.display = 'block';
        });
    });

    confirmNoBtn.addEventListener('click', function() {
        confirmationModal.style.display = 'none';
        formToSubmit = null;
    });

    confirmYesBtn.addEventListener('click', function() {
        confirmationModal.style.display = 'none';
        successModal.style.display = 'block';

        setTimeout(function() {
            successModal.style.display = 'none';
            if (formToSubmit) {
                formToSubmit.submit();
            }
        }, 2000);
    });

    window.addEventListener('click', function(event) {
        if (event.target == confirmationModal) {
            confirmationModal.style.display = "none";
            formToSubmit = null;
        }
        if (event.target == successModal) {
            successModal.style.display = "none";
        }
    });
    
    if(document.getElementById("searchInput")) {
        document.getElementById("searchInput").addEventListener("keyup", searchTable);
    }

    if(document.getElementById("pendingInquiryTable")) {
        searchTable();
    }
});
    function searchTable() {
        const input = document.getElementById("searchInput").value.toLowerCase().trim();
        const table = document.getElementById("pendingInquiryTable");
        const tr = table.getElementsByTagName("tr");
        let found = false;

        for (let i = 1; i < tr.length; i++) {
            const row = tr[i];
            if (row.cells.length > 1 && row.cells[0].colSpan !== 11) {
                const requestType = row.cells[0].textContent.toLowerCase();
                const reservationDate = row.cells[1].textContent.toLowerCase();
                const unitNo = row.cells[2].textContent.toLowerCase();
                const fullName = row.cells[3].textContent.toLowerCase();
                const contactNo = row.cells[4].textContent.toLowerCase();
                const email = row.cells[5].textContent.toLowerCase();
                const prefMoveIn = row.cells[6].textContent.toLowerCase();
                const permanentAddress = row.cells[7].textContent.toLowerCase();
                const ecPerson = row.cells[8].textContent.toLowerCase();
                const ecNo = row.cells[9].textContent.toLowerCase();

                let rowVisible = false;

                if (requestType.includes(input)) rowVisible = true;
                if (reservationDate.includes(input)) rowVisible = true;
                if (unitNo.includes(input)) rowVisible = true;
                if (fullName.includes(input)) rowVisible = true;
                if (contactNo.includes(input)) rowVisible = true;
                if (email.includes(input)) rowVisible = true;
                if (prefMoveIn.includes(input)) rowVisible = true;
                if (permanentAddress.includes(input)) rowVisible = true;
                if (ecPerson.includes(input)) rowVisible = true;
                if (ecNo.includes(input)) rowVisible = true;

                row.style.display = rowVisible ? "" : "none";
                if (rowVisible) found = true;
            }
        }

        const noRecordsRow = table.querySelector('td[colspan="11"]');
        if (noRecordsRow) {
            const dataRowsPresent = Array.from(tr).slice(1).some(r => r.cells.length > 1 && r.cells[0].colSpan !== 11);
            if (!found && input !== "" && dataRowsPresent) {
                noRecordsRow.textContent = "No matching inquiries found for your search.";
                noRecordsRow.parentNode.style.display = "";
            } else if (input === "" && !dataRowsPresent) {
                noRecordsRow.textContent = "No pending inquiries found.";
                noRecordsRow.parentNode.style.display = "";
            } else if (input === "" && dataRowsPresent) {
                noRecordsRow.parentNode.style.display = "none";
            } else if (!dataRowsPresent && input === ""){
                noRecordsRow.textContent = "No pending inquiries found.";
                noRecordsRow.parentNode.style.display = "";
            } else if (!found && !dataRowsPresent && input !== ""){
                noRecordsRow.textContent = "No matching inquiries found for your search.";
                noRecordsRow.parentNode.style.display = "";
            } else {
                noRecordsRow.parentNode.style.display = "none";
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        if(document.getElementById("pendingInquiryTable")) {
            searchTable();
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