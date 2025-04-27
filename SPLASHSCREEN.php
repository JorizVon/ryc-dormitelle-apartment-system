<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Splash Screen</title>
  <style>
    body {
      margin: 0;
      padding: 0;
      background-color: #FFFFFF;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      overflow: hidden;
    }

    .splashScreen {
      display: flex;
      justify-content: center;
      align-items: center;
      width: 100%;
      height: 100%;
      opacity: 1;
      transition: opacity 1s ease; /* Transition for smooth fading */
    }

    .splashScreen.fade-out {
      opacity: 0; /* When fade-out class is added, opacity goes to 0 */
    }

    .splashScreen img {
      height: 372px;
      width: 1276px;
    }
  </style>
</head>
<body>

<div class="splashScreen" id="splash">
  <img src="otherIcons/systemLogo.png" alt="systemLogo">
</div>

<script>
  // Wait a bit before fading out
  setTimeout(() => {
    const splash = document.getElementById('splash');
    splash.classList.add('fade-out'); // Add the fade-out class

    // After fade-out animation, redirect to login
    setTimeout(() => {
      window.location.href = 'ADMINLOGIN.php'; // Redirect after fade completes
    }, 1000); // Match this time with CSS transition time (1s = 1000ms)
  }, 1500); // Show splash for 1.5 seconds first
</script>

</body>
</html>
