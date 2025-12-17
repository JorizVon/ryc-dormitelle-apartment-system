<?php
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if db_connect.php exists and include it
if (!file_exists('../db_connect.php')) {
    die("Database connection file not found. Please check the path.");
}

require_once '../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['email_account'])) {
    header("Location: LOGIN.php");
    exit();
}

$email = $_SESSION['email_account'];
$error_message = null;
$notifications = null;

// Get the email_account from the email
$tenant_query = $conn->prepare("SELECT email FROM tenants WHERE email = ?");
if (!$tenant_query) {
    $error_message = "System error. Please try again later. (Error: " . $conn->error . ")";
} else {
    $tenant_query->bind_param("s", $email);
    
    if (!$tenant_query->execute()) {
        $error_message = "Error executing query: " . $tenant_query->error;
    } else {
        $tenant_result = $tenant_query->get_result();

        if ($tenant_result->num_rows == 0) {
            $error_message = "User account not found.";
        } else {
            $tenant_row = $tenant_result->fetch_assoc();
            $email_account = $tenant_row['email'];
            
           // Get notifications for this tenant using their email
                $notif_query = $conn->prepare("SELECT notif_date_time, email_account, notif_title, notif_description 
                                              FROM notification_inbox 
                                              WHERE email_account = ? 
                                              ORDER BY notif_date_time DESC");
                
                if (!$notif_query) {
                    $error_message = "Could not retrieve notifications. (Error: " . $conn->error . ")";
                } else {
                    $notif_query->bind_param("s", $email);
                    
                    if (!$notif_query->execute()) {
                        $error_message = "Error fetching notifications: " . $notif_query->error;
                    } else {
                        $notifications = $notif_query->get_result();
                    }
                    
                    $notif_query->close();
            }
        }
    }
    
    $tenant_query->close();
}

// Set page title for header
$page_title = "Inbox - RYC Dormitelle";

// Include header
include 'tenant_header.php';
?>

<style>
  /* ===========================
     INBOX PAGE-SPECIFIC STYLES
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

  .transactionchoices h1 {
    text-decoration: none;
    font-size: 22px;
    margin: 0;
    color: #FFFF;
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 15px;
    width: 100%;
    max-width: 600px;
    height: 70px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.08);
    font-weight: 600;
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
    border-radius: 15px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.08);
    overflow: hidden;
  }

  .todaystransactbox {
    max-height: 500px;
    overflow-y: auto;
    scrollbar-width: none; /* Firefox */
  }

  .todaystransactbox::-webkit-scrollbar {
    display: none; /* Chrome, Safari, Opera */
  }

  .transacdate {
    background: #f8fafc;
    padding: 1rem 2rem;
    border-bottom: 1px solid #e2e8f0;
  }

  .transacdate p {
    font-size: 17px;
    margin: 0;
    color: #1e3c72;
    font-weight: 600;
  }

  .boxContainer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #e2e8f0;
    margin: 0;
    padding: 1.5rem 2rem;
    cursor: pointer;
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
    min-height: 60px;
  }

  .box a {
    text-decoration: none;
  }

  .notif {
    font-size: 16px;
    margin: 0;
    position: relative;
    margin-bottom: 8px;
    color: #1e3c72;
    font-weight: 600;
  }

  .notiftext {
    font-size: 14px;
    margin: 0;
    margin-bottom: 8px;
    color: #64748b;
    line-height: 1.5;
  }

  .notifdate {
    font-size: 12px;
    color: #94a3b8;
    margin: 0;
    text-align: right;
  }

  .notifcontent {
    max-height: 80px;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
  }

  .noNotifications {
    text-align: center;
    padding: 3rem 2rem;
    color: #94a3b8;
    font-size: 16px;
  }

  /* Modal styles */
  .notification-modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.4);
  }

  .modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 2rem;
    border: none;
    width: 90%;
    max-width: 700px;
    border-radius: 15px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
  }

  .close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    line-height: 1;
  }

  .close:hover,
  .close:focus {
    color: #333;
    text-decoration: none;
  }

  .modal-title {
    color: #1e3c72;
    margin: 1rem 0;
    font-size: 1.5rem;
    font-weight: 600;
  }

  .modal-date {
    color: #94a3b8;
    font-size: 14px;
    margin-bottom: 1.5rem;
  }

  .modal-body {
    color: #333;
    line-height: 1.6;
    font-size: 15px;
  }

  .error-message {
    background-color: #fee;
    border: 1px solid #fcc;
    color: #c33;
    padding: 1rem;
    border-radius: 8px;
    margin: 1rem 0;
  }

  /* Responsive styles */
  @media screen and (max-width: 992px) {
    .pageTitle h1 {
      font-size: 2rem;
    }

    .transactionchoices h1 {
      font-size: 20px;
      height: 60px;
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

    .transactionchoices h1 {
      font-size: 18px;
      height: 55px;
    }

    .transacdate {
      padding: 0.8rem 1.5rem;
    }

    .transacdate p {
      font-size: 16px;
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

    .modal-content {
      margin: 10% auto;
      padding: 1.5rem;
    }
  }

  @media screen and (max-width: 480px) {
    .pageTitle h1 {
      font-size: 1.5rem;
    }

    .transactionchoices h1 {
      font-size: 16px;
      height: 50px;
    }

    .transacdate {
      padding: 0.8rem 1rem;
    }

    .transacdate p {
      font-size: 15px;
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

    .modal-content {
      width: 95%;
      margin: 5% auto;
      padding: 1rem;
    }
  }
</style>

<div class="mainBody">
  <div class="mainBodyContiner">
    <div class="pageTitle">
      <h1>Inbox</h1>
    </div>
    <div class="transactionchoices">
      <h1>Notifications</h1>
    </div>
    <div class="transactionformContainer">
      <div class="transactionform">
        <div class="todaystransactbox">
          <div class="transacdate">
            <p><b>Your Notifications</b></p>
          </div>
          
          <?php
          if ($error_message) {
              echo '<div class="noNotifications error-message">' . htmlspecialchars($error_message) . '</div>';
          } elseif ($notifications && $notifications->num_rows > 0) {
              while ($notif = $notifications->fetch_assoc()) {
                  // Safely create DateTime object
                  try {
                      $date = new DateTime($notif['notif_date_time']);
                      $formatted_date = $date->format('F j, Y - g:i A');
                  } catch (Exception $e) {
                      $formatted_date = $notif['notif_date_time'];
                  }
                  
                  // Safely truncate description
                  $description = $notif['notif_description'] ?? '';
                  $truncated = substr(strip_tags($description), 0, 150);
                  if (strlen($description) > 150) {
                      $truncated .= '...';
                  }
                  
                  echo '<div class="boxContainer" onclick="showNotification(this)">
                          <div class="box">
                              <p class="notif">' . htmlspecialchars($notif['notif_title'] ?? 'No Title') . '</p>
                              <div class="notifcontent">
                                  <p class="notiftext">' . htmlspecialchars($truncated) . '</p>
                              </div>
                              <p class="notifdate">' . htmlspecialchars($formatted_date) . '</p>
                              
                              <div class="hidden-content" style="display:none;">
                                  <div class="full-title">' . htmlspecialchars($notif['notif_title'] ?? '') . '</div>
                                  <div class="full-date">' . htmlspecialchars($formatted_date) . '</div>
                                  <div class="full-description">' . htmlspecialchars($description) . '</div>
                              </div>
                          </div>
                      </div>';
              }
          } else {
              echo '<div class="noNotifications">No notifications found</div>';
          }
          ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Notification Modal -->
<div id="notificationModal" class="notification-modal">
  <div class="modal-content">
    <span class="close">&times;</span>
    <h2 id="modalTitle" class="modal-title"></h2>
    <p id="modalDate" class="modal-date"></p>
    <div id="modalBody" class="modal-body"></div>
  </div>
</div>

<script>
  // Modal functions
  var modal = document.getElementById("notificationModal");
  var span = document.getElementsByClassName("close")[0];
  
  function showNotification(element) {
    var hiddenContent = element.querySelector(".hidden-content");
    if (!hiddenContent) return;
    
    var title = hiddenContent.querySelector(".full-title").textContent;
    var date = hiddenContent.querySelector(".full-date").textContent;
    var description = hiddenContent.querySelector(".full-description").textContent;
    
    document.getElementById("modalTitle").textContent = title;
    document.getElementById("modalDate").textContent = date;
    document.getElementById("modalBody").textContent = description;
    
    modal.style.display = "block";
  }
  
  // When the user clicks on <span> (x), close the modal
  if (span) {
    span.onclick = function() {
      modal.style.display = "none";
    }
  }
  
  // When the user clicks anywhere outside of the modal, close it
  window.onclick = function(event) {
    if (event.target == modal) {
      modal.style.display = "none";
    }
  }
</script>

<?php
// Include footer (which includes the chat component)
include 'footer.php';
?>