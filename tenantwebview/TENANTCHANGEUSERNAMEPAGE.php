<?php
session_start();
require_once '../db_connect.php';

// Redirect to login if not logged in
if (!isset($_SESSION['email_account'])) {
    header("Location: ../LOGIN.php");
    exit();
}

$email_account = $_SESSION['email_account'];
$current_username = 'none';
$message = '';  // To hold success or error messages

$stmt = $conn->prepare("SELECT username FROM accounts WHERE email_account = ?");
$stmt->bind_param("s", $email_account);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $current_username = $row['username'];
}
$stmt->close();

// Handle Change Username form submission
if (isset($_POST['change_username_submit'])) {
    $new_username = trim($_POST['new_username'] ?? '');
    $confirm_username = trim($_POST['confirm_username'] ?? '');

    if (empty($new_username)) {
        $message = "Username cannot be empty.";
    } elseif ($new_username !== $confirm_username) {
        $message = "Usernames do not match.";
    } elseif (strlen($new_username) < 3) {
        $message = "Username must be at least 3 characters.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $new_username)) {
        $message = "Username can only contain letters, numbers, and underscores.";
    } else {
        // Check if username already exists
        $check_stmt = $conn->prepare("SELECT account_ID FROM accounts WHERE username = ? AND email_account != ?");
        $check_stmt->bind_param("ss", $new_username, $email_account);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $message = "Username already taken. Please choose another one.";
        } else {
            // Update the username
            $update_stmt = $conn->prepare("UPDATE accounts SET username = ? WHERE email_account = ?");
            $update_stmt->bind_param("ss", $new_username, $email_account);

            if ($update_stmt->execute()) {
                $current_username = $new_username;
                $message = "Username changed successfully!";
            } else {
                $message = "Error updating username. Please try again.";
            }
            $update_stmt->close();
        }
        $check_stmt->close();
    }
}

// Set page title for header
$page_title = "Change Username - RYC Dormitelle";

// Include header
include 'tenant_header.php';
?>

<style>
  /* ===========================
     CHANGE USERNAME PAGE-SPECIFIC STYLES
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

  .transactionchoices {
    width: 100%;
    display: flex;
    justify-content: center;
    margin-bottom: 2rem;
  }

  .profileHeader {
    color: #FFFF;
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    display: flex;
    align-items: center;
    justify-content: left;
    width: 100%;
    max-width: 600px;
    height: 110px;
    border-radius: 15px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.08);
  }

  .accInfo {
    display: inline-block;
    margin-left: 30px;
  }

  .accInfo h5 {
    font-size: 18px;
    margin: 0;
    font-weight: 600;
  }

  .accInfo p {
    font-size: 14px;
    margin: 5px 0;
    opacity: 0.9;
  }

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
    padding: 2rem;
    border-radius: 15px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.08);
  }

  .instruct p {
    font-size: 14px;
    color: #1e3c72;
    text-align: center;
    margin-bottom: 2rem;
    line-height: 1.6;
  }

  .changeUsernameForm {
    width: 100%;
  }

  .changeUsernameForm input {
    padding: 15px; 
    font-size: 16px;
    border-radius: 10px;
    width: 100%;
    margin-bottom: 1rem;
    border: 2px solid #e2e8f0;
    box-sizing: border-box;
    transition: border-color 0.3s;
  }

  .changeUsernameForm input:focus {
    outline: none;
    border-color: #79B1FC;
  }

  .changeUsernameForm button {
    padding: 15px 30px;
    background: linear-gradient(135deg, #79B1FC, #4A90E2);
    color: white;
    font-size: 16px;
    border: none;
    border-radius: 25px;
    cursor: pointer;
    width: 100%;
    font-weight: 600;
    transition: all 0.3s;
    margin-top: 1rem;
  }

  .changeUsernameForm button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(121, 177, 252, 0.4);
  }

  /* Message styling */
  .message {
    display: flex;
    justify-content: center;
    margin-bottom: 1rem;
    padding: 1rem;
    border-radius: 10px;
    text-align: center;
    font-weight: 500;
  }

  .message.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
  }

  .message.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
  }

  /* Responsive styles */
  @media screen and (max-width: 992px) {
    .pageTitle h1 {
      font-size: 2rem;
    }

    .profileHeader {
      height: 90px;
    }

    .accInfo h5 {
      font-size: 16px;
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
      height: 80px;
    }

    .accInfo {
      margin-left: 20px;
    }

    .accInfo h5 {
      font-size: 15px;
    }

    .transactionform {
      padding: 1.5rem;
    }

    .instruct p {
      font-size: 13px;
    }

    .changeUsernameForm input {
      padding: 12px;
      font-size: 14px;
    }

    .changeUsernameForm button {
      padding: 12px 25px;
      font-size: 14px;
    }
  }

  @media screen and (max-width: 480px) {
    .pageTitle h1 {
      font-size: 1.5rem;
    }

    .profileHeader {
      height: 70px;
    }

    .accInfo {
      margin-left: 15px;
    }

    .accInfo h5 {
      font-size: 14px;
    }

    .transactionform {
      padding: 1rem;
    }

    .instruct p {
      font-size: 12px;
    }

    .changeUsernameForm input {
      padding: 10px;
      font-size: 13px;
    }

    .changeUsernameForm button {
      padding: 10px 20px;
      font-size: 13px;
    }
  }
</style>

<div class="mainBody">
  <div class="mainBodyContiner">
    <div class="pageTitle">
      <h1>Account</h1>
    </div>
    <div class="transactionchoices">
      <div class="profileHeader">
        <div class="accInfo">
          <h5>Change Username</h5>
          <p>Current Username: <?php echo htmlspecialchars($current_username); ?></p>
          <p>Email: <?php echo htmlspecialchars($email_account); ?></p>
        </div>
      </div>
    </div>
    <div class="transactionformContainer">
      <div class="transactionform">
        <div class="instruct">
          <p><strong>Your username must be at least 3 characters and can only contain letters, numbers, and underscores.</strong></p>
        </div>
        <?php if ($message): ?>
          <div class="message <?php echo (strpos($message, 'successfully') !== false) ? 'success' : 'error'; ?>">
            <?php echo htmlspecialchars($message); ?>
          </div>
        <?php endif; ?>
        <form action="" class="changeUsernameForm" method="post">
          <input type="text" name="new_username" placeholder="New Username" required>
          <input type="text" name="confirm_username" placeholder="Confirm New Username" required>
          <button type="submit" name="change_username_submit">
            Change Username
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php
// Include footer
include 'footer.php';
?>