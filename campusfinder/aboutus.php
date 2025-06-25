<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>About Us - CampusFinder</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="landing.css">
</head>
<body>
  <header>
    <div class="nav">
      <img src="logo ipt.png" alt="CampusFinder Logo" class="logo">
      <ul>
        <li><a href="landing.php" class="nav-link">HOME</a></li>
        <li><a href="aboutus.php" class="nav-link active">ABOUT US</a></li>
        <li><a href="contactus.php" class="nav-link">CONTACT US</a></li> 
        <li><a href="#" class="nav-link" id="loginBtn">LOG IN</a></li>
      </ul>
    </div>
  </header>

  <section class="about" id="about-section">
    <p><strong>About Campus Finder</strong>
      Our mission is to help students, faculty, and staff reunite with their lost belongings across campus. Whether you've misplaced your backpack, keys, or any other item, we're here to assist you.
    </p>
  </section>

  <section class="info">
    <div class="column">
      <h3><i class="fas fa-question-circle"></i> How it Works:</h3>
      <ol>
        <li>Report a Lost Item. Fill out our online form to report any lost items.</li>
        <li>Browse Found Items. Check our regularly updated list of found items to see if your belongings have been turned in.</li>
        <li>Claim Your Item. If you find your lost item, follow the instructions to claim it.</li>
      </ol>
    </div>
    <div class="column">
      <h3><i class="fas fa-link"></i> System Features:</h3>
      <ul>
        <li><i class="fas fa-check-circle"></i> Report Lost or Found Items</li>
        <li><i class="fas fa-check-circle"></i> View Recent Posts</li>
        <li><i class="fas fa-check-circle"></i> Match Alerts for Found Items</li>
        <li><i class="fas fa-check-circle"></i> Claim Tracking</li>
        <li><i class="fas fa-check-circle"></i> Admin Support for Verification</li>
        <li><i class="fas fa-check-circle"></i> Feedback Submission</li>
      </ul>
    </div>
    <div class="column">
      <h3><i class="fas fa-book"></i> Helpful Resources:</h3>
      <ul>
        <li><i class="fas fa-lightbulb"></i> Tips for Preventing Lost Items</li>
        <li><i class="fas fa-hands-helping"></i> What to Do if You Find an Item</li>
        <li><i class="fas fa-map-marked-alt"></i> Campus Map for Lost & Found Locations</li>
      </ul>
    </div>
  </section>

  <footer>
    <p>© 2025 CampusFinder. All rights reserved.<br>Your belongings matter to us!</p>
  </footer>

  <div id="loginModal" class="modal">
    <div class="modal-content">
      <span class="close">×</span>
      <h2><i class="fas fa-sign-in-alt"></i> Log In</h2>
      <div id="loginNotification" class="custom-notification"></div>
      <form id="loginForm" method="POST" action="landing.php">
        <input type="hidden" name="action" value="login">
        <div class="form-group">
          <label for="loginEmail">Email</label>
          <input type="email" id="loginEmail" name="loginEmail" placeholder="Enter your email" required>
          <div class="error-message" id="loginEmailError"></div>
        </div>
        <div class="form-group">
          <label for="loginPassword">Password</label>
          <input type="password" id="loginPassword" name="loginPassword" placeholder="Enter your password" required>
          <div class="error-message" id="loginPasswordError"></div>
        </div>
        <button type="submit">Log In</button>
      </form>
      <p style="text-align: center; margin-top: 1rem;">
        Don't have an account? <a href="#" id="showSignup" style="color: gold;">Sign Up</a>
      </p>
    </div>
  </div>

  <div id="signupModal" class="modal">
    <div class="modal-content">
      <span class="close">×</span>
      <h2><i class="fas fa-user-plus"></i> Sign Up</h2>
      <div id="signupNotification" class="custom-notification"></div>
      <form id="signupForm" method="POST" action="landing.php">
        <input type="hidden" name="action" value="signup">
        <div class="form-group">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" placeholder="Enter your username" required>
          <div class="error-message" id="usernameError"></div>
        </div>
        <div class="form-group">
          <label for="firstName">First Name</label>
          <input type="text" id="firstName" name="firstName" placeholder="Enter your first name" required>
          <div class="error-message" id="firstNameError"></div>
        </div>
        <div class="form-group">
          <label for="middleName">Middle Name</label>
          <input type="text" id="middleName" name="middleName" placeholder="Enter your middle name">
          <div class="error-message" id="middleNameError"></div>
        </div>
        <div class="form-group">
          <label for="lastName">Last Name</label>
          <input type="text" id="lastName" name="lastName" placeholder="Enter your last name" required>
          <div class="error-message" id="lastNameError"></div>
        </div>
        <div class="form-group">
          <label for="signupEmail">Email</label>
          <input type="email" id="signupEmail" name="signupEmail" placeholder="Enter your email" required>
          <div class="error-message" id="signupEmailError"></div>
        </div>
        <div class="form-group">
          <label for="signupPassword">Password</label>
          <input type="password" id="signupPassword" name="signupPassword" placeholder="Create a password" required>
          <div class="error-message" id="signupPasswordError"></div>
        </div>
        <div class="form-group">
          <label for="confirmPassword">Confirm Password</label>
          <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm your password" required>
          <div class="error-message" id="confirmPasswordError"></div>
        </div>
        <button type="submit">Sign Up</button>
      </form>
      <p style="text-align: center; margin-top: 1rem;">Already have an account? <a href="#" id="showLogin" style="color: gold;">Log In</a></p>
    </div>
  </div>

  <script src="landing.js"></script>
</body>
</html>