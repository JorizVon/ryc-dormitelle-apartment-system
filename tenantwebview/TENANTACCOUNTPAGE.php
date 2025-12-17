<?php
session_start();

if (!isset($_SESSION['email_account'])) {
    header("Location: ../LOGIN.php");
    exit();
}

require_once '../db_connect.php';

$email = $_SESSION['email_account']; 
$user_image_filename = null;

// --- HANDLE IMAGE UPLOAD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['new_profile_image'])) {
    if ($_FILES['new_profile_image']['error'] === UPLOAD_ERR_OK) {
        $image = $_FILES['new_profile_image'];
        if ($image['size'] > 2097152) { // 2MB
            echo "<script>alert('Error: Image is too large. Max size is 2MB.');</script>";
        } else {
            $img_ext = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
            if (in_array($img_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                $old_img_stmt = $conn->prepare("SELECT user_image FROM accounts WHERE email_account = ?");
                $old_img_stmt->bind_param("s", $email);
                $old_img_stmt->execute();
                $old_img_result = $old_img_stmt->get_result()->fetch_assoc();
                if (!empty($old_img_result['user_image']) && file_exists('../user_images/' . $old_img_result['user_image'])) {
                    unlink('../user_images/' . $old_img_result['user_image']);
                }
                $old_img_stmt->close();

                $new_img_name = uniqid('user_', true) . '.' . $img_ext;
                $destination = '../user_images/' . $new_img_name;
                if (move_uploaded_file($image['tmp_name'], $destination)) {
                    $update_stmt = $conn->prepare("UPDATE accounts SET user_image = ? WHERE email_account = ?");
                    $update_stmt->bind_param("ss", $new_img_name, $email);
                    $update_stmt->execute();
                    $update_stmt->close();
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit();
                }
            } else {
                 echo "<script>alert('Error: Invalid image format.');</script>";
            }
        }
    }
}


// --- FETCH ALL DATA ---
// Default values
$tenant_name = "Not Available"; $contact_no = "Not Available"; $tenant_ID = "Not Available";
$payment_due = "Not Available"; $unit_no = "Not Available"; $security_deposit = "₱ 0.00";
$balance = "₱ 0.00"; $monthly_rent_amount = "₱ 0.00"; $card_status = "Not Available";
$total_rent_paid = "₱ 0.00";

try {
    // 1. Get user_image from ACCOUNTS table
    $imageStmt = $conn->prepare("SELECT user_image FROM accounts WHERE email_account = ?");
    $imageStmt->bind_param("s", $email);
    $imageStmt->execute();
    $imageResult = $imageStmt->get_result();
    if($imageRow = $imageResult->fetch_assoc()) {
        $user_image_filename = $imageRow['user_image'];
    }
    $imageStmt->close();
    
    // 2. Get tenant-specific data (Basic Info)
    $tenantSql = "SELECT t.tenant_name, t.contact_no, tu.tenant_ID, tu.unit_no, 
                         tu.total_rent_paid, tu.security_deposit,
                         tu.balance, cr.card_status, u.monthly_rent_amount
                  FROM tenants t 
                  JOIN tenant_unit tu ON t.tenant_ID = tu.tenant_ID
                  LEFT JOIN units u ON tu.unit_no = u.unit_no
                  LEFT JOIN card_registration cr ON tu.unit_no = cr.unit_no
                  WHERE t.email = ? AND t.role = 'representative'";
    
    $tenantStmt = $conn->prepare($tenantSql);
    $tenantStmt->bind_param("s", $email);
    $tenantStmt->execute();
    $tenantResult = $tenantStmt->get_result();
    
    if ($tenantRow = $tenantResult->fetch_assoc()) {
        $tenant_ID = !empty($tenantRow['tenant_ID']) ? htmlspecialchars($tenantRow['tenant_ID']) : "Not Available";
        $tenant_name = !empty($tenantRow['tenant_name']) ? htmlspecialchars($tenantRow['tenant_name']) : "Not Available";
        $contact_no = !empty($tenantRow['contact_no']) ? htmlspecialchars($tenantRow['contact_no']) : "Not Available";
        $unit_no = !empty($tenantRow['unit_no']) ? htmlspecialchars($tenantRow['unit_no']) : "Not Available";
        // NOTE: We no longer fetch payment_due from tenant_unit here, it will be handled by the checklist query below
        
        $security_deposit = '₱ ' . number_format($tenantRow['security_deposit'] ?? 0, 2);
        $balance = '₱ ' . number_format($tenantRow['balance'] ?? 0, 2);
        $monthly_rent_amount = '₱ ' . number_format($tenantRow['monthly_rent_amount'] ?? 0, 2);
        $card_status = !empty($tenantRow['card_status']) ? htmlspecialchars($tenantRow['card_status']) : "Not Available";
        $total_rent_paid = '₱ ' . number_format($tenantRow['total_rent_paid'] ?? 0, 2);
    }
    $tenantStmt->close();

    // 3. Get Payment Due from Payment Checklist (New Logic)
    // This query gets the unpaid months, loops, and takes the first one found.
    $checklistSql = "SELECT `checklist_ID`, `unit_no`, `email_account`, `monthly_due_dates`, `pay_status` 
                     FROM `payment_checklist` 
                     WHERE pay_status = 0 and email_account = ?";
    
    $checklistStmt = $conn->prepare($checklistSql);
    $checklistStmt->bind_param("s", $email);
    $checklistStmt->execute();
    $checklistResult = $checklistStmt->get_result();
    
    $found_unpaid = false;
    
    // Loop through the results as requested
    while ($chkRow = $checklistResult->fetch_assoc()) {
        // Display the first payment due unpaid
        $payment_due = htmlspecialchars($chkRow['monthly_due_dates']);
        $found_unpaid = true;
        
        // Break the loop immediately after finding the first one
        break; 
    }
    
    // Optional: If no unpaid rows found, set to specific text (or keep "Not Available")
    if (!$found_unpaid) {
        $payment_due = "Up to Date";
    }

    $checklistStmt->close();
    
} catch (Exception $e) {
    error_log("Error in TENANTACCOUNTPAGE.php: " . $e->getMessage());
}

$profile_image_path = !empty($user_image_filename) ? '../user_images/' . $user_image_filename : '../otherIcons/adminIcon.png';

$page_title = "Account - RYC Dormitelle";
include 'tenant_header.php';
?>

<style>

  /* Main Body Styles */
  .mainBody {
    position: relative;
    top: 92px;
    width: 100%;
    min-height: calc(100vh - 92px);
    background: #f8fafc;
  }

  .mainBodyContiner {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem;
  }

  .pageTitle {
    height: 100px;
    display: flex;
    align-items: center;
    border-bottom: solid 1px #2262B8;
    margin-bottom: 2rem;
  }

  .pageTitle h1 {
    margin-left: 0;
    margin-top: 0;
    font-size: 2.5rem;
    color: #1e3c72;
    font-weight: 700;
  }

  /* Profile Header Section */
  .profileContent {
    width: 100%;
    display: flex;
    justify-content: center;
    margin-bottom: 2rem;
  }

  .profileHeader {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    color: white;
    padding: 2rem;
    border-radius: 15px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    width: 100%;
    max-width: 600px;
    min-height: 120px;
  }

  .profile {
    height: 80px;
    width: 80px;
    margin-right: 1.5rem;
    flex-shrink: 0;
  }

  .profile img {
    height: 100%;
    width: 100%;
    border-radius: 50%;
    border: 3px solid rgba(255, 255, 255, 0.3);
    object-fit: cover;
  }

  .accInfo h5 {
    font-size: 1.3rem;
    margin: 0 0 0.5rem 0;
    font-weight: 600;
  }

  .accInfo h6 {
    font-size: 1rem;
    margin: 0 0 0.3rem 0;
    opacity: 0.9;
    font-weight: 500;
  }

  .accInfo p {
    font-size: 0.9rem;
    margin: 0.2rem 0;
    opacity: 0.8;
  }

  /* Account Information Section */
  .profileFormContainer {
    width: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 4rem;
  }

  .profileForm {
    width: 100%;
    max-width: 600px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.08);
    overflow: hidden;
  }

  .boxContainer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #e2e8f0;
    margin: 0;
    padding: 1rem 2rem;
    line-height: 15px;
    transition: background-color 0.3s;
  }

  .boxContainer:hover {
    background-color: #f8fafc;
  }

  .boxContainer:last-child {
    border-bottom: none;
  }

  .box {
    width: 100%;
    min-height: 40px;
  }

  .box a {
    text-decoration: none;
  }

  .notif {
    font-size: 17px;
    margin: 0 0 8px 0;
    color: #1e3c72;
    font-weight: 400;
  }

  .notiftext {
    font-size: 14px;
    margin: 0;
    color: #64748b;
    line-height: 1.5;
  }

  /* Link styling for special items */
  .box a .notif {
    color: #1e3c72;
    transition: color 0.3s;
  }

  .box a:hover .notif {
    color: #2a5298;
  }

  /* Responsive styles */
  @media screen and (max-width: 992px) {
    .pageTitle h1 {
      font-size: 2rem;
    }

    .profileHeader {
      padding: 1.5rem;
    }

    .profile {
      height: 70px;
      width: 70px;
    }
  }

  @media screen and (max-width: 768px) {
    .mainBody {
      top: 60px;
    }

    .mainBodyContiner {
      padding: 0 1rem;
    }

    .pageTitle {
      height: 80px;
      margin-bottom: 1rem;
    }

    .pageTitle h1 {
      font-size: 1.8rem;
    }

    .profileHeader {
      padding: 1.2rem;
      flex-direction: column;
      text-align: center;
      min-height: auto;
    }

    .profile {
      height: 60px;
      width: 60px;
      margin-right: 0;
      margin-bottom: 1rem;
    }

    .accInfo h5 {
      font-size: 1.1rem;
    }

    .accInfo h6 {
      font-size: 0.9rem;
    }

    .accInfo p {
      font-size: 0.8rem;
    }

    .boxContainer {
      padding: 1.2rem 1.5rem;
    }

    .notif {
      font-size: 15px;
    }

    .notiftext {
      font-size: 13px;
    }
  }

  @media screen and (max-width: 480px) {
    .pageTitle h1 {
      font-size: 1.5rem;
    }

    .profileHeader {
      padding: 1rem;
    }

    .profile {
      height: 50px;
      width: 50px;
    }

    .accInfo h5 {
      font-size: 1rem;
    }

    .accInfo h6 {
      font-size: 0.8rem;
    }

    .accInfo p {
      font-size: 0.7rem;
    }

    .boxContainer {
      padding: 1rem;
    }

    .notif {
      font-size: 14px;
    }

    .notiftext {
      font-size: 12px;
    }
  }
    .profile img {
        cursor: pointer;
    }
</style>

<div class="mainBody">
  <div class="mainBodyContiner">
    <div class="pageTitle">
      <h1>Account</h1>
    </div>
    <div class="profileContent">
      <div class="profileHeader">
        
        <form id="profileImageForm" method="POST" enctype="multipart/form-data" style="margin: 0;">
            <input type="file" name="new_profile_image" id="profileImageInput" style="display: none;" accept="image/*">
            <div class="profile">
                <img src="<?php echo $profile_image_path; ?>" alt="profile" id="profileImage" title="Click to change profile picture" onerror="this.src='../otherIcons/adminIcon.png'">
            </div>
        </form>

        <div class="accInfo">
          <h5 class="tenant_name"><?php echo $tenant_name; ?></h5>
          <p class="contact_no"><?php echo $contact_no; ?></p>
          <h6 class="teanant_ID">Tenant ID: <?php echo $tenant_ID; ?></h6>
        </div>
      </div>
    </div>
    <div class="profileFormContainer">
      <div class="profileForm">
        <div class="profilebox">
          <div class="boxContainer">
            <div class="box">
              <p class="notif"><b>Payment Due</b></p>
              <p class="notiftext" id="lease_payment_due"><?php echo $payment_due; ?></p>
            </div>
          </div>
          <div class="boxContainer">
            <div class="box">
              <p class="notif"><b>Unit Number</b></p>
              <p class="notiftext" id="unit_no"><?php echo $unit_no; ?></p>
            </div>
          </div>
          <div class="boxContainer">
            <div class="box">
              <p class="notif"><b>Total Rent Paid</b></p>
              <p class="notiftext" id="total_rent_paid"><?php echo $total_rent_paid; ?></p>
            </div>
          </div>
          <div class="boxContainer">
            <div class="box">
              <p class="notif"><b>Security Deposit</b></p>
              <p class="notiftext" id="security_deposit"><?php echo $security_deposit; ?></p>
            </div>
          </div>
          <div class="boxContainer">
            <div class="box">
              <p class="notif"><b>Remaining Balance</b></p>
              <p class="notiftext" id="balance"><?php echo $balance; ?></p>
            </div>
          </div>
          <div class="boxContainer">
            <div class="box">
              <p class="notif"><b>Monthly Rent Payment</b></p>
              <p class="notiftext" id="lease_payment_amount"><?php echo $monthly_rent_amount; ?></p>
            </div>
          </div>
          <div class="boxContainer">
            <div class="box">
              <p class="notif"><b>Card Status</b></p>
              <p class="notiftext" id="card_status"><?php echo $card_status; ?></p>
            </div>
          </div>
          <div class="boxContainer">
            <div class="box">
              <a href="TENANTCHANGEUSERNAMEPAGE.php"><p class="notif"><b>Change Username</b></p></a>
            </div>
          </div>
          <div class="boxContainer">
            <div class="box">
              <a href="TENANTCHANGEPASSPAGE.php"><p class="notif"><b>Change Password</b></p></a>
            </div>
          </div>
          <div class="boxContainer">
            <div class="box">
              <!-- Correct Logout should point to a script that destroys the session -->
              <a href="../logout.php"><p class="notif"><b>Log Out</b></p></a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const profileImage = document.getElementById('profileImage');
        const profileImageInput = document.getElementById('profileImageInput');
        const profileImageForm = document.getElementById('profileImageForm');

        profileImage.addEventListener('click', function() {
            profileImageInput.click();
        });

        profileImageInput.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                profileImageForm.submit();
            }
        });
    });
</script>

<?php include 'footer.php'; ?>