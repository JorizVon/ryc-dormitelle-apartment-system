<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Page</title>

</head>
<?php
    $adminDisplayIdentifier = "ADMIN"; // Default
    if (isset($_SESSION['email_account'])) {
        $adminDisplayIdentifier = htmlspecialchars(strtok($_SESSION['email_account'], '@'));
    }
?>
<!-- Header Component -->
<div class="header">
    <div class="hamburger" onclick="toggleSidebar()">☰</div>
    <div class="headerContent">
        <a href="ADMINPROFILE.php" class="adminTitle"><?php echo isset($adminDisplayIdentifier) ? $adminDisplayIdentifier : "ADMIN"; ?></a>
        <p class="adminLogoutspace"> | </p>
        <a href="LOGOUT.php" class="logOutbtn">Log Out</a>
    </div>
</div>
<script>
// Hamburger toggle function for sidebar
function toggleSidebar() {
    const sidebar = document.querySelector('.sideBar');
    if (sidebar) {
        sidebar.classList.toggle('active');
    }
}
// Close sidebar when clicking outside
document.addEventListener('click', function(event) {
    const sidebar = document.querySelector('.sideBar');
    const hamburger = document.querySelector('.hamburger');
    
    if (sidebar && hamburger) {
        // If click is outside sidebar and hamburger, close the sidebar
        if (!sidebar.contains(event.target) && !hamburger.contains(event.target)) {
            sidebar.classList.remove('active');
        }
    }
});
// Prevent sidebar from closing when clicking inside it
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.sideBar');
    if (sidebar) {
        sidebar.addEventListener('click', function(event) {
            event.stopPropagation();
        });
    }
});
</script>