<?php
// google_auth.php
session_start();
require_once 'db_connect.php';

// Turn on error reporting for debugging
ini_set('display_errors', 1); 
error_reporting(E_ALL);

if (isset($_POST['credential']) && isset($_POST['context'])) {
    $id_token = $_POST['credential'];
    $context = $_POST['context']; // 'signin' or 'signup'

    // Verify Token using cURL
    $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $id_token;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $json = curl_exec($ch);
    curl_close($ch);
    $payload = json_decode($json, true);

    if ($payload && isset($payload['email'])) {
        $google_email = $payload['email'];
        $google_name = $payload['name'];
        $google_picture = $payload['picture']; 

        // Check if user exists
        $stmt = $conn->prepare("SELECT * FROM accounts WHERE email_account = ?");
        $stmt->bind_param("s", $google_email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_exists = $result->num_rows === 1;

        // --- LOGIC SPLIT BASED ON CONTEXT ---

        // SCENARIO 1: Login Page
        if ($context === 'signin') {
            if ($user_exists) {
                $user = $result->fetch_assoc();
                $_SESSION['email_account'] = $user['email_account'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['login_success'] = true;
                echo "success";
            } else {
                // User is trying to login but account doesn't exist
                echo "error_not_found"; 
            }
        } 
        // SCENARIO 2: Signup Page
        elseif ($context === 'signup') {
            if ($user_exists) {
                echo "error_exists"; // User already has an account
            } else {
                // User is new -> Create Account
                
                // 1. Download Image
                $image_filename = null;
                if (!empty($google_picture)) {
                    $upload_dir = 'user_images/';
                    if (!is_dir($upload_dir)) @mkdir($upload_dir, 0755, true);
                    $image_content = file_get_contents($google_picture);
                    if ($image_content !== false) {
                        $new_img_name = uniqid('google_', true) . '.jpg';
                        file_put_contents($upload_dir . $new_img_name, $image_content);
                        $image_filename = $new_img_name;
                    }
                }

                // 2. Insert into DB
                $random_pass = bin2hex(random_bytes(10));
                $hashed_password = password_hash($random_pass, PASSWORD_DEFAULT);
                $user_type = 'user';

                $insert = $conn->prepare("INSERT INTO accounts (username, email_account, password, user_type, user_image) VALUES (?, ?, ?, ?, ?)");
                $insert->bind_param("sssss", $google_name, $google_email, $hashed_password, $user_type, $image_filename);
                
                if ($insert->execute()) {
                    // Auto login after signup
                    $_SESSION['email_account'] = $google_email;
                    $_SESSION['user_type'] = $user_type;
                    $_SESSION['login_success'] = true;
                    echo "success";
                } else {
                    echo "error_db";
                }
            }
        }
    } else {
        echo "error_token";
    }
}
?>