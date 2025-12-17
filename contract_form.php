<?php
$message = "";
$contract_saved = false;

// Only process if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Database connection
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "ryc_dormitelle_dbs";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Get form data and validate
        $contract_id = uniqid('CONTRACT_'); // Generate unique contract ID
        $email_account = 'kyle@gmail.com'; // Example email as requested
        $contract_date = $_POST['contract_date'] ?? '';
        $full_name = $_POST['tenant_name'] ?? '';
        $citizenship = $_POST['citizenship'] ?? '';
        $postal_address = $_POST['tenant_address'] ?? '';
        $contract_term = $_POST['lease_term'] ?? '';
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        $monthly_rate = $_POST['rent_amount'] ?? 0;
        $security_deposit = $_POST['security_deposit'] ?? 0;

        // Validate required fields
        if (empty($contract_date) || empty($full_name) || empty($citizenship) || 
            empty($postal_address) || empty($contract_term) || empty($start_date) || 
            empty($end_date) || empty($monthly_rate) || empty($security_deposit)) {
            throw new Exception("All fields are required.");
        }

        // Prepare INSERT statement
        $sql = "INSERT INTO `contract_information`
                (`contract_id`, `email_account`, `contract_date`, `full_name`, `citizenship`, 
                 `postal_address`, `contract_term`, `start_date`, `end_date`, `monthly_rate`, `security_deposit`) 
                VALUES 
                (:contract_id, :email_account, :contract_date, :full_name, :citizenship, 
                 :postal_address, :contract_term, :start_date, :end_date, :monthly_rate, :security_deposit)";

        $stmt = $conn->prepare($sql);
        
        // Bind parameters
        $stmt->bindParam(':contract_id', $contract_id);
        $stmt->bindParam(':email_account', $email_account);
        $stmt->bindParam(':contract_date', $contract_date);
        $stmt->bindParam(':full_name', $full_name);
        $stmt->bindParam(':citizenship', $citizenship);
        $stmt->bindParam(':postal_address', $postal_address);
        $stmt->bindParam(':contract_term', $contract_term);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->bindParam(':monthly_rate', $monthly_rate);
        $stmt->bindParam(':security_deposit', $security_deposit);

        // Execute the statement
        if ($stmt->execute()) {
            $contract_saved = true;
            $message = "<div class='alert alert-success'>Contract information saved successfully! Contract ID: " . $contract_id . "</div>";
            
            // Display saved information
            $message .= "<div class='card mt-3'>";
            $message .= "<div class='card-header'><h5>Contract Information Saved:</h5></div>";
            $message .= "<div class='card-body'>";
            $message .= "<p><strong>Contract ID:</strong> " . htmlspecialchars($contract_id) . "</p>";
            $message .= "<p><strong>Email:</strong> " . htmlspecialchars($email_account) . "</p>";
            $message .= "<p><strong>Contract Date:</strong> " . htmlspecialchars($contract_date) . "</p>";
            $message .= "<p><strong>Tenant Name:</strong> " . htmlspecialchars($full_name) . "</p>";
            $message .= "<p><strong>Citizenship:</strong> " . htmlspecialchars($citizenship) . "</p>";
            $message .= "<p><strong>Address:</strong> " . htmlspecialchars($postal_address) . "</p>";
            $message .= "<p><strong>Lease Term:</strong> " . htmlspecialchars($contract_term) . "</p>";
            $message .= "<p><strong>Start Date:</strong> " . htmlspecialchars($start_date) . "</p>";
            $message .= "<p><strong>End Date:</strong> " . htmlspecialchars($end_date) . "</p>";
            $message .= "<p><strong>Monthly Rate:</strong> ₱" . number_format($monthly_rate, 2) . "</p>";
            $message .= "<p><strong>Security Deposit:</strong> ₱" . number_format($security_deposit, 2) . "</p>";
            $message .= "<div class='mt-3'>";
            $message .= "<form action='generate_contract.php' method='POST' target='_blank'>";
            $message .= "<input type='hidden' name='contract_id' value='" . htmlspecialchars($contract_id) . "'>";
            $message .= "<input type='hidden' name='contract_date' value='" . htmlspecialchars($contract_date) . "'>";
            $message .= "<input type='hidden' name='tenant_name' value='" . htmlspecialchars($full_name) . "'>";
            $message .= "<input type='hidden' name='citizenship' value='" . htmlspecialchars($citizenship) . "'>";
            $message .= "<input type='hidden' name='tenant_address' value='" . htmlspecialchars($postal_address) . "'>";
            $message .= "<input type='hidden' name='lease_term' value='" . htmlspecialchars($contract_term) . "'>";
            $message .= "<input type='hidden' name='start_date' value='" . htmlspecialchars($start_date) . "'>";
            $message .= "<input type='hidden' name='end_date' value='" . htmlspecialchars($end_date) . "'>";
            $message .= "<input type='hidden' name='rent_amount' value='" . htmlspecialchars($monthly_rate) . "'>";
            $message .= "<input type='hidden' name='security_deposit' value='" . htmlspecialchars($security_deposit) . "'>";
            
            // Add the day, month, year fields for PDF generation
            $date_obj = new DateTime($contract_date);
            $day_suffix = $date_obj->format('j');
            if ($day_suffix > 3 && $day_suffix < 21) $day_suffix .= "th";
            else {
                switch ($day_suffix % 10) {
                    case 1: $day_suffix .= "st"; break;
                    case 2: $day_suffix .= "nd"; break;
                    case 3: $day_suffix .= "rd"; break;
                    default: $day_suffix .= "th";
                }
            }
            $message .= "<input type='hidden' name='day' value='" . $day_suffix . "'>";
            $message .= "<input type='hidden' name='month' value='" . $date_obj->format('F') . "'>";
            $message .= "<input type='hidden' name='year' value='" . $date_obj->format('Y') . "'>";
            
            $message .= "<button type='submit' class='btn btn-success'>Generate PDF Contract</button>";
            $message .= "</form>";
            $message .= "</div>";
            $message .= "</div></div>";
        } else {
            $message = "<div class='alert alert-danger'>Error saving contract information.</div>";
        }

    } catch(PDOException $e) {
        $message = "<div class='alert alert-danger'>Database Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    } catch(Exception $e) {
        $message = "<div class='alert alert-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }

    if (isset($conn)) {
        $conn = null;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Contract of Lease Form</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .section-title {
      font-weight: bold;
      color: #495057;
      border-bottom: 2px solid #007bff;
      padding-bottom: 5px;
      margin-bottom: 15px;
    }
    .container {
      max-width: 800px;
      margin: 20px auto;
      padding: 20px;
    }
  </style>
</head>
<body>

  <div class="container">
    <h2 class="text-center">CONTRACT OF LEASE</h2>
    <h5 class="text-center">(Residential)</h5>
    <h6 class="text-center text-muted mb-4">RYC Dormitelle</h6>

    <?php if (!empty($message)): ?>
        <?php echo $message; ?>
        <?php if ($contract_saved): ?>
            <div class="text-center mt-3">
                <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-secondary">Create New Contract</a>
            </div>
        <?php endif; ?>
        <hr class="my-4">
    <?php endif; ?>

    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">

      <!-- Date of Contract -->
      <div class="mb-3">
        <label class="form-label">Date of Contract</label>
        <input type="date" id="contract_date" name="contract_date" class="form-control" 
               value="<?php echo $_POST['contract_date'] ?? ''; ?>" required>
      </div>

      <div class="row mb-3">
        <div class="col">
          <label>Day</label>
          <input type="text" id="day" name="day" class="form-control" readonly required>
        </div>
        <div class="col">
          <label>Month</label>
          <input type="text" id="month" name="month" class="form-control" readonly required>
        </div>
        <div class="col">
          <label>Year</label>
          <input type="text" id="year" name="year" class="form-control" readonly required>
        </div>
      </div>

      <hr class="my-4">

      <div class="section-title">Tenant Information</div>

      <div class="mb-3">
        <label class="form-label">Full Name</label>
        <input type="text" name="tenant_name" class="form-control" 
               value="<?php echo htmlspecialchars($_POST['tenant_name'] ?? ''); ?>" 
               placeholder="Enter tenant full name" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Citizenship</label>
        <input type="text" name="citizenship" class="form-control" 
               value="<?php echo htmlspecialchars($_POST['citizenship'] ?? ''); ?>" 
               placeholder="Enter citizenship" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Postal Address</label>
        <input type="text" name="tenant_address" class="form-control" 
               value="<?php echo htmlspecialchars($_POST['tenant_address'] ?? ''); ?>" 
               placeholder="Enter postal address" required>
      </div>

      <hr class="my-4">

      <div class="section-title">Lease Term</div>

      <div class="mb-3">
        <label class="form-label">Lease Duration (e.g., 1 year, 6 months)</label>
        <input type="text" name="lease_term" class="form-control" 
               value="<?php echo htmlspecialchars($_POST['lease_term'] ?? ''); ?>" required>
      </div>

      <div class="row mb-3">
        <div class="col">
          <label>Start Date</label>
          <input type="date" name="start_date" class="form-control" 
                 value="<?php echo $_POST['start_date'] ?? ''; ?>" required>
        </div>
        <div class="col">
          <label>End Date</label>
          <input type="date" name="end_date" class="form-control" 
                 value="<?php echo $_POST['end_date'] ?? ''; ?>" required>
        </div>
      </div>

      <hr class="my-4">

      <div class="section-title">Financial Information</div>

      <div class="mb-3">
        <label class="form-label">Monthly Rental Rate</label>
        <input type="number" name="rent_amount" class="form-control" 
               value="<?php echo $_POST['rent_amount'] ?? ''; ?>" 
               placeholder="e.g., 10000" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Security Deposit</label>
        <input type="number" name="security_deposit" class="form-control" 
               value="<?php echo $_POST['security_deposit'] ?? ''; ?>" 
               placeholder="e.g., 5000" required>
      </div>

      <button type="submit" class="btn btn-primary w-100 mt-3">Save Contract Information</button>
    </form>
  </div>

  <script>
    function getDaySuffix(day) {
      if (day > 3 && day < 21) return day + "th";
      switch (day % 10) {
        case 1: return day + "st";
        case 2: return day + "nd";
        case 3: return day + "rd";
        default: return day + "th";
      }
    }

    function updateDateFields() {
      let dateInput = document.getElementById("contract_date");
      if (dateInput.value) {
        let date = new Date(dateInput.value);
        if (!isNaN(date)) {
          document.getElementById("day").value = getDaySuffix(date.getDate());
          document.getElementById("month").value = date.toLocaleString('default', { month: 'long' });
          document.getElementById("year").value = date.getFullYear();
        }
      }
    }

    document.getElementById("contract_date").addEventListener("change", updateDateFields);
    
    // Initialize date fields on page load if date is already set
    document.addEventListener("DOMContentLoaded", function() {
      updateDateFields();
    });
  </script>
</body>
</html>