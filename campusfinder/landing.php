<?php
ob_start();
ini_set('display_errors', 0); 
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
session_start();
require_once 'db_connect.php';
require_once 'vendor/PHPMailer/src/PHPMailer.php';
require_once 'vendor/PHPMailer/src/SMTP.php';
require_once 'vendor/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$loginError = '';
$signupError = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        
        $pdo->query("SELECT 1");
    } catch (PDOException $e) {
        error_log("Database connection check failed: " . $e->getMessage(), 3, __DIR__ . '/error.log');
        http_response_code(500);
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Database unavailable. Please try again later.']);
            exit;
        } else {
            $_SESSION['signupError'] = 'Database unavailable. Please try again later.';
            header("Location: landing.php");
            exit;
        }
    }

    try {
        if ($_POST['action'] === 'login') {
            $email = filter_input(INPUT_POST, 'loginEmail', FILTER_SANITIZE_EMAIL);
            $password = trim($_POST['loginPassword']);

            if (!$email || !$password) {
                $loginError = "Email and password are required.";
            } else {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                if (!$stmt->execute([$email])) {
                    error_log("Login query failed for email: $email");
                    throw new PDOException("Database query failed.");
                }
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    error_log("User found for email: $email, stored hash: " . $user['password']);
                    if (password_verify($password, $user['password'])) {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['role'] = $user['role'] ?? 'user';
                        error_log("Login successful for user ID: {$user['id']}, role: {$_SESSION['role']}");

                        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                            header('Content-Type: application/json');
                            echo json_encode([
                                'status' => 'success',
                                'redirect' => $user['role'] === 'admin' ? 'admin.php' : 'home.php'
                            ]);
                            exit;
                        } else {
                            header("Location: " . ($user['role'] === 'admin' ? 'admin.php' : 'home.php'));
                            header("Cache-Control: no-cache, must-revalidate");
                            exit;
                        }
                    } else {
                        $loginError = "Incorrect password.";
                        error_log("Password verification failed for email: $email, input password: [hidden]");
                    }
                } else {
                    $loginError = "No account found with that email.";
                    error_log("No user found for email: $email");
                }
            }

            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' && $loginError) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => $loginError]);
                exit;
            } elseif (!isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                $_SESSION['loginError'] = $loginError;
                header("Location: landing.php");
                exit;
            }
        } elseif ($_POST['action'] === 'signup') {
            $username = filter_input(INPUT_POST, 'username', FILTER_UNSAFE_RAW);
            $username = $username !== false ? htmlspecialchars(trim($username), ENT_QUOTES, 'UTF-8') : '';
            $firstName = filter_input(INPUT_POST, 'firstName', FILTER_UNSAFE_RAW);
            $firstName = $firstName !== false ? htmlspecialchars(trim($firstName), ENT_QUOTES, 'UTF-8') : '';
            $middleName = filter_input(INPUT_POST, 'middleName', FILTER_UNSAFE_RAW);
            $middleName = $middleName !== false ? htmlspecialchars(trim($middleName), ENT_QUOTES, 'UTF-8') : '';
            $lastName = filter_input(INPUT_POST, 'lastName', FILTER_UNSAFE_RAW);
            $lastName = $lastName !== false ? htmlspecialchars(trim($lastName), ENT_QUOTES, 'UTF-8') : '';
            $email = filter_input(INPUT_POST, 'signupEmail', FILTER_SANITIZE_EMAIL);
            $password = trim($_POST['signupPassword']);
            $confirmPassword = trim($_POST['confirmPassword']);

            if (!$username || !$firstName || !$lastName || !$email || !$password || !$confirmPassword) {
                $signupError = "All required fields must be filled.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $signupError = "Invalid email format.";
            } elseif (strlen($password) < 6) {
                $signupError = "Password must be at least 6 characters.";
            } elseif ($password !== $confirmPassword) {
                $signupError = "Passwords do not match.";
            } else {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
                if (!$stmt->execute([$email])) {
                    error_log("Email check query failed for email: $email");
                    throw new PDOException("Email check query failed.");
                }
                if ($stmt->fetchColumn() > 0) {
                    $signupError = "Email already exists.";
                    error_log("Signup failed: Email $email already exists.");
                } else {
                    $pdo->beginTransaction();
                    try {
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        if (!$hashedPassword) {
                            throw new Exception("Password hashing failed.");
                        }
                        error_log("Generated hash for email: $email, hash: $hashedPassword");
                        $stmt = $pdo->prepare("INSERT INTO users (username, first_name, middle_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?, ?, 'user')");
                        if (!$stmt->execute([$username, $firstName, $middleName, $lastName, $email, $hashedPassword])) {
                            throw new PDOException("User insertion failed.");
                        }
                        $userId = $pdo->lastInsertId();
                        $_SESSION['user_id'] = $userId;
                        $_SESSION['username'] = $username;
                        $_SESSION['role'] = 'user';
                        $successMessage = "Sign up successful! Redirecting to home page...";
                        $pdo->commit();
                        error_log("Signup successful for user ID: $userId, email: $ $email");

                        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                            header('Content-Type: application/json');
                            echo json_encode(['status' => 'success', 'message' => $successMessage, 'redirect' => 'home.php']);
                            exit;
                        } else {
                            $_SESSION['successMessage'] = $successMessage;
                            header("Location: landing.php?signup_success=1");
                            exit;
                        }
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $signupError = "Registration failed: " . htmlspecialchars($e->getMessage());
                        error_log("Signup error: " . $e->getMessage());
                    }
                }
            }

            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' && $signupError) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => $signupError]);
                exit;
            } elseif (!isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                $_SESSION['signupError'] = $signupError;
                header("Location: landing.php");
                exit;
            }
        }
    } catch (Exception $e) {
        error_log("General error in POST handling: " . $e->getMessage(), 3, __DIR__ . '/error.log');
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred: ' . htmlspecialchars($e->getMessage())]);
            exit;
        } else {
            $_SESSION['signupError'] = "An unexpected error occurred.";
            header("Location: landing.php");
            exit;
        }
    }
}

if (isset($_SESSION['loginError'])) {
    $loginError = $_SESSION['loginError'];
    unset($_SESSION['loginError']);
}
if (isset($_SESSION['signupError'])) {
    $signupError = $_SESSION['signupError'];
    unset($_SESSION['signupError']);
}
if (isset($_SESSION['successMessage'])) {
    $successMessage = $_SESSION['successMessage'];
    unset($_SESSION['successMessage']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>CampusFinder</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="landing.css">
  <style>
    .custom-notification {
      background: #ff4d4d;
      color: white;
      padding: 10px;
      border-radius: 5px;
      margin-bottom: 10px;
      text-align: center;
      font-size: 0.9em;
      opacity: 1;
      transition: opacity 0.5s ease;
      display: none;
    }
    .custom-notification.success {
      background: #28a745;
    }
    .custom-notification.fade-out {
      opacity: 0;
    }
    .custom-notification[style*="display: none"] {
      opacity: 0;
    }
    .modal-content .error-message {
      color: #ff4d4d;
      font-size: 0.8em;
      margin-top: 5px;
    }
    .modal-content button {
      width: 100%;
      padding: 10px;
      background: #007bff;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }
    .modal-content button:hover {
      background: #0056b3;
    }
    .success-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.7);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 1000;
      opacity: 0;
      transition: opacity 0.5s ease;
    }
    .success-overlay.show {
      opacity: 1;
    }
    .success-message {
      background: #28a745;
      color: white;
      padding: 20px 40px;
      border-radius: 10px;
      font-size: 1.2em;
      text-align: center;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      transform: translateY(-20px);
      transition: transform 0.5s ease;
    }
    .success-message.show {
      transform: translateY(0);
    }
  </style>
</head>
<body>
  <?php if ($successMessage && !isset($_SERVER['HTTP_X_REQUESTED_WITH']) && isset($_GET['signup_success'])) { ?>
    <div class="success-overlay show">
      <div class="success-message show">
        <?php echo htmlspecialchars($successMessage); ?>
      </div>
    </div>
  <?php } ?>
  <header>
    <div class="nav">
      <img src="logo ipt.png" alt="CampusFinder Logo" class="logo">
      <ul>
        <li><a href="landing.php" class="nav-link active">HOME</a></li>
        <li><a href="aboutus.php" class="nav-link">ABOUT US</a></li>
        <li><a href="contactus.php" class="nav-link">CONTACT US</a></li> 
        <li><a href="#" class="nav-link" id="loginBtn">LOG IN</a></li>
      </ul>
    </div>
  </header>

  <section class="hero" id="home">
    <div class="hero-text">
      <h1>CAMPUS FINDER</h1>
      <p>Lost Something?<br />Use our platform to report or find lost items on campus of PLSP. We verify claims and help return belongings to their rightful owners.</p>
      <a href="#" id="report-btn"><button><i class="fas fa-plus-circle"></i> Report a lost item</button></a>
    </div>
    <div class="hero-img">
      <img src="pic1.png" alt="CampusFinder Illustration">
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
      <form id="loginForm" method="POST">
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
      <form id="signupForm" method="POST">
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

  <script>
    
    <?php if ($loginError) { ?>
      document.getElementById('loginModal').style.display = 'flex';
      document.getElementById('loginNotification').textContent = '<?php echo htmlspecialchars($loginError); ?>';
      document.getElementById('loginNotification').style.display = 'block';
    <?php } ?>
    <?php if ($signupError) { ?>
      document.getElementById('signupModal').style.display = 'flex';
      document.getElementById('signupNotification').textContent = '<?php echo htmlspecialchars($signupError); ?>';
      document.getElementById('signupNotification').style.display = 'block';
    <?php } ?>
    <?php if (isset($_GET['signup_success'])) { ?>
      setTimeout(() => {
        const overlay = document.querySelector('.success-overlay');
        overlay.classList.remove('show');
        setTimeout(() => {
          window.location.href = 'home.php';
        }, 500);
      }, 3000);
    <?php } ?>
  </script>
  <script src="landing.js"></script>
</body>
</html>