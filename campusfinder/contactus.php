<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Contact Us - CampusFinder</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="landing.css">
</head>
<body>
  <header>
    <div class="nav">
      <img src="logo ipt.png" alt="CampusFinder Logo" class="logo">
      <ul>
        <li><a href="landing.php" class="nav-link">HOME</a></li>
        <li><a href="aboutus.php" class="nav-link">ABOUT US</a></li>
        <li><a href="contactus.php" class="nav-link active">CONTACT US</a></li> 
        <li><a href="#" class="nav-link" id="loginBtn">LOG IN</a></li>
      </ul>
    </div>
  </header>

  <section class="contact-follow" id="contact-section">
    <div class="contact">
      <h4><i class="fas fa-envelope"></i> Contact Us</h4>
      <p><i class="fas fa-envelope"></i> Email: campusfinder2025@gmail.com</p>
      <p><i class="fas fa-phone"></i> Phone: +63 906-072-1751</p>
      <p><i class="fas fa-building"></i> Office: CCST Department, 2nd Floor, Academic Building 2, PLSP</p>
      <p><i class="fas fa-clock"></i> Office Hours: Monday - Friday, 8 AM - 5 PM</p>
    </div>
    <div class="divider"></div>
    <div class="follow">
      <h4><i class="fas fa-share-alt"></i> Follow Us</h4>
      <div class="social-icon">
        <a href="https://web.facebook.com/login" target="_blank" rel="noopener noreferrer">
          <i class="fab fa-facebook-f"></i>
        </a>
        <p>Facebook</p>
      </div>
      <div class="social-icon">
        <a href="https://twitter.com/login" target="_blank" rel="noopener noreferrer">
          <i class="fab fa-twitter"></i>
        </a>
        <p>Twitter</p>
      </div>
      <div class="social-icon">
        <a href="https://www.instagram.com" target="_blank" rel="noopener noreferrer">
          <i class="fab fa-instagram"></i>
        </a>
        <p>Instagram</p>
      </div>
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