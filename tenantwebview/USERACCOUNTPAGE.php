<?php
session_start(); // Start session ONCE, at the very beginning

// Redirect to login if not logged in
if (!isset($_SESSION['email_account'])) {
    header("Location: ../LOGIN.php");
    exit();
}

// Connect to the database
require_once '../db_connect.php';

$email_account = $_SESSION['email_account'];
$username = 'none';
$user_image_filename = null;

// --- HANDLE IMAGE UPLOAD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['new_profile_image'])) {
    if ($_FILES['new_profile_image']['error'] === UPLOAD_ERR_OK) {
        $image = $_FILES['new_profile_image'];
        // Basic validation
        if ($image['size'] > 2097152) { // 2MB
            echo "<script>alert('Error: Image is too large. Max size is 2MB.');</script>";
        } else {
            $img_ext = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
            if (in_array($img_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                // Before saving new image, delete the old one if it exists
                $old_img_stmt = $conn->prepare("SELECT user_image FROM accounts WHERE email_account = ?");
                $old_img_stmt->bind_param("s", $email_account);
                $old_img_stmt->execute();
                $old_img_result = $old_img_stmt->get_result()->fetch_assoc();
                if (!empty($old_img_result['user_image']) && file_exists('../user_images/' . $old_img_result['user_image'])) {
                    unlink('../user_images/' . $old_img_result['user_image']);
                }
                $old_img_stmt->close();

                // Save the new image
                $new_img_name = uniqid('user_', true) . '.' . $img_ext;
                $destination = '../user_images/' . $new_img_name;
                if (move_uploaded_file($image['tmp_name'], $destination)) {
                    // Update the database with the new filename
                    $update_stmt = $conn->prepare("UPDATE accounts SET user_image = ? WHERE email_account = ?");
                    $update_stmt->bind_param("ss", $new_img_name, $email_account);
                    $update_stmt->execute();
                    $update_stmt->close();
                    
                    // Redirect to refresh the page and show the new image
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit();
                }
            } else {
                 echo "<script>alert('Error: Invalid image format.');</script>";
            }
        }
    }
}


// Get username and image from database
$stmt = $conn->prepare("SELECT username, user_image FROM accounts WHERE email_account = ?");
$stmt->bind_param("s", $email_account);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $username = $row['username'];
    $user_image_filename = $row['user_image'];
}
$stmt->close();

// Set default image if none is found
$profile_image_path = !empty($user_image_filename) ? '../user_images/' . $user_image_filename : '../otherIcons/adminIcon.png';

$page_title = "Account - RYC Dormitelle";
include 'user_header.php';
?>

<style>
  /* ===========================
     ACCOUNT PAGE-SPECIFIC STYLES
     =========================== */

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
  .transactionchoices {
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
    position: relative;
    top: 10px;
  }

  .accInfo h6 {
    font-size: 1rem;
    margin: 0 0 0.3rem 0;
    opacity: 0.9;
    position: relative;
    bottom: 10px;
    font-weight: 500;
  }

  .accInfo p {
    font-size: 0.9rem;
    margin: 0.2rem 0;
    opacity: 0.8;
  }

  /* Account Information Section */
  .transactionformContainer {
    width: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 4rem;
  }

  .transactionform {
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

  /* Responsive styles for mainBody */
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
    <div class="transactionchoices">
      <div class="profileHeader">
        
        <!-- UPDATED: Form added for image upload -->
        <form id="profileImageForm" method="POST" enctype="multipart/form-data" style="margin: 0;">
            <input type="file" name="new_profile_image" id="profileImageInput" style="display: none;" accept="image/*">
            <div class="profile">
                <!-- UPDATED: Image source is now dynamic -->
                <img src="<?php echo $profile_image_path; ?>" alt="profile" id="profileImage" title="Click to change profile picture" onerror="this.src='../otherIcons/adminIcon.png'">
            </div>
        </form>

        <div class="accInfo">
          <h5 class="email_account">Email Account: <?php echo htmlspecialchars($email_account); ?></h5>
          <br>
          <h6 class="username">Username: <?php echo htmlspecialchars($username); ?></h6>
        </div>
      </div>
    </div>
    <div class="transactionformContainer">
      <div class="transactionform">
        <div class="todaystransactbox">
            <div class="boxContainer">
            <div class="box">
              <a href="USERCHANGEUSERNAMEPAGE.php"><p class="notif"><b>Change Username</b></p></a>
            </div>
          </div>
          <div class="boxContainer">
            <div class="box">
              <a href="USERCHANGEPASSPAGE.php"><p class="notif"><b>Change Password</b></p></a>
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

<!-- ADDED: JavaScript for image editing -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const profileImage = document.getElementById('profileImage');
        const profileImageInput = document.getElementById('profileImageInput');
        const profileImageForm = document.getElementById('profileImageForm');

        // When the profile image is clicked, trigger the hidden file input
        profileImage.addEventListener('click', function() {
            profileImageInput.click();
        });

        // When a new file is chosen, automatically submit the form
        profileImageInput.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                profileImageForm.submit();
            }
        });
    });
</script>

<?php include 'footer.php'; ?>