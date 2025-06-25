<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: landing.php");
    exit;
}

if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: admin.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$successMessage = isset($_GET['success']) ? urldecode($_GET['success']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'report') {
            $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
            $name = filter_input(INPUT_POST, 'reportItemName', FILTER_SANITIZE_STRING);
            $description = filter_input(INPUT_POST, 'reportDescription', FILTER_SANITIZE_STRING);
            $location = filter_input(INPUT_POST, 'reportLocation', FILTER_SANITIZE_STRING);
            $date = $_POST['reportDate'];
            $item_id = isset($_POST['item_id']) ? filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT) : null;

            if (!$type || !$name || !$description || !$location || !$date) {
                header("Location: home.php?success=" . urlencode("All fields are required."));
                exit;
            }

            $image_path = null;
            if (isset($_FILES['itemImage']) && $_FILES['itemImage']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                if (in_array($_FILES['itemImage']['type'], $allowed_types) && $_FILES['itemImage']['size'] <= 10000000) {
                    $upload_dir = 'Uploads/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                    $image_path = $upload_dir . uniqid() . '_' . basename($_FILES['itemImage']['name']);
                    if (!move_uploaded_file($_FILES['itemImage']['tmp_name'], $image_path)) {
                        header("Location: home.php?success=" . urlencode("Failed toDCs upload image."));
                        exit;
                    }
                } else {
                    header("Location: home.php?success=" . urlencode("Invalid image type or size (max 10MB)."));
                    exit;
                }
            }

            if ($item_id) {
                $stmt = $pdo->prepare("UPDATE items SET type = ?, name = ?, description = ?, location = ?, date = ?, image = ? WHERE id = ? AND user_id = ? AND status = 'pending'");
                $stmt->execute([$type, $name, $description, $location, $date, $image_path ?: null, $item_id, $user_id]);
                $successMessage = "Report updated successfully!";
            } else {
                $stmt = $pdo->prepare("INSERT INTO items (user_id, type, name, description, location, date, image, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
                $stmt->execute([$user_id, $type, $name, $description, $location, $date, $image_path ?: null]);
                $successMessage = "Item reported successfully!";
            }
            header("Location: home.php?success=" . urlencode($successMessage));
            exit;
        } elseif ($_POST['action'] === 'update_profile') {
            $name = filter_input(INPUT_POST, 'editName', FILTER_SANITIZE_STRING);
            $email = filter_input(INPUT_POST, 'editEmail', FILTER_SANITIZE_EMAIL);
            if (!$name || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                header("Location: home.php?success=" . urlencode("Valid name and email are required."));
                exit;
            }
            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, email = ? WHERE id = ?");
            $stmt->execute([$name, $email, $user_id]);
            header("Location: home.php?success=" . urlencode("Profile updated successfully!"));
            exit;
        } elseif ($_POST['action'] === 'logout') {
            session_destroy();
            header("Location: landing.php");
            exit;
        } elseif ($_POST['action'] === 'feedback') {
            $feedback = filter_input(INPUT_POST, 'feedback', FILTER_SANITIZE_STRING);
            if ($feedback) {
                $stmt = $pdo->prepare("INSERT INTO feedback (user_id, feedback_text, created_at) VALUES (?, ?, NOW())");
                $stmt->execute([$user_id, $feedback]);
                header("Location: home.php?success=" . urlencode("Feedback submitted successfully!"));
                exit;
            } else {
                header("Location: home.php?success=" . urlencode("Feedback is required."));
                exit;
            }
        } elseif ($_POST['action'] === 'delete_report') {
            $item_id = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
            if ($item_id) {
                $pdo->beginTransaction();
                try {
                    $stmt = $pdo->prepare("SELECT status FROM items WHERE id = ? AND user_id = ?");
                    $stmt->execute([$item_id, $user_id]);
                    $item = $stmt->fetch();

                    if ($item && $item['status'] === 'pending') {
                        $stmt = $pdo->prepare("DELETE FROM items WHERE id = ? AND user_id = ? AND status = 'pending'");
                        $stmt->execute([$item_id, $user_id]);
                        if ($stmt->rowCount() > 0) {
                            $successMessage = "Report deleted successfully!";
                        } else {
                            $successMessage = "Report not found or already processed.";
                        }
                    } else {
                        $successMessage = "Cannot delete an approved or matched report.";
                    }
                    $pdo->commit();
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $errorMessage = "Error deleting report: " . htmlspecialchars($e->getMessage());
                    error_log("Delete report error: " . $e->getMessage());
                    $successMessage = $errorMessage;
                }
            } else {
                $successMessage = "Invalid report ID.";
            }
            header("Location: home.php?success=" . urlencode($successMessage));
            exit;
        }
    } catch (PDOException $e) {
        $errorMessage = "Error: " . htmlspecialchars($e->getMessage());
        error_log("Home action error: " . $e->getMessage());
        header("Location: home.php?success=" . urlencode($errorMessage));
        exit;
    }
}

$visible_items = [];
try {
    $visible_items = $pdo->query("SELECT * FROM items WHERE (status = 'approved' OR status = 'matched') ORDER BY created_at DESC LIMIT 10")->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to load visible items: " . $e->getMessage());
}
$lost_items = array_filter($visible_items, fn($item) => $item['type'] === 'lost');
$found_items = array_filter($visible_items, fn($item) => $item['type'] === 'found');
$matched_items = array_filter($visible_items, fn($item) => $item['status'] === 'matched');

$my_reports = [];
try {
    $my_reports_stmt = $pdo->prepare("SELECT * FROM items WHERE user_id = ? ORDER BY created_at DESC");
    $my_reports_stmt->execute([$user_id]);
    $my_reports = $my_reports_stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to load my reports: " . $e->getMessage());
}

$notifications = [];
try {
    $notifications_stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $notifications_stmt->execute([$user_id]);
    $notifications = $notifications_stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to load notifications: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusFinder - Home</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="home.css">
    <style>
        .dark .bg-white { background-color: #2d3748 !important; }
        .dark .bg-gray-50 { background-color: #4a5568 !important; }
        .dark .text-gray-700, .dark .text-gray-800 { color: #e2e8f0 !important; }
        .dark .modal-content { background-color: #2d3748 !important; }
        .dark .status-badge { color: #e2e8f0 !important; }
        .dark input, .dark textarea, .dark select { background-color: #4a5568 !important; color: #e2e8f0 !important; border-color: #718096 !important; }
        .dark .error-message { color: #f87171 !important; }
        .dark .notification { background-color: #4a5568 !important; color: #e2e8f0 !important; }
        .dark #editProfileModal .bg-gray-300 { background-color: #4a5568 !important; color: #e2e8f0 !important; }
        .dark #editProfileModal button:hover { background-color: #718096 !important; }
        .modal .modal-content { max-height: 90vh; overflow-y: auto; }
        .scrollable { max-height: 300px; overflow-y: auto; }
        .notification {
            transition: opacity 0.5s ease;
        }
    </style>
</head>
<body class="bg-light dark:bg-dark-bg transition-colors duration-300">
    <?php if ($successMessage) { ?>
        <div class="notification <?php echo strpos($successMessage, 'successfully') !== false ? 'success' : 'error'; ?>" style="display: block;">
            <?php echo htmlspecialchars($successMessage); ?>
        </div>
    <?php } ?>
    <nav class="bg-blue-600 dark:bg-blue-800 p-4 flex justify-between items-center sticky top-0 z-10 shadow-md">
        <h1 class="text-white text-2xl font-bold">CampusFinder</h1>
        <button id="hamburger" class="text-white md:hidden focus:outline-none" aria-label="Toggle menu">
            <i class="fas fa-bars w-8 h-8"></i>
        </button>
        <div class="hidden md:flex items-center space-x-6" id="navMenu">
            <ul class="flex space-x-6 text-white">
                <li><a href="#" class="nav-link" data-target="dashboard">Home</a></li>
                <li><a href="#" class="nav-link" data-target="claimStatus">My Reports</a></li>
                <li><a href="#" id="notificationNav" class="nav-link">Notifications<?php if (count($notifications) > 0) { ?><span class="notification-badge"><?php echo count($notifications); ?></span><?php } ?></a></li>
            </ul>
            <button id="darkModeToggle" class="text-white focus:outline-none mr-4">
                <i id="darkModeIcon" class="fas fa-sun w-6 h-6"></i>
            </button>
            <div class="relative">
                <button id="profileBtn" class="text-white focus:outline-none flex items-center space-x-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A4.992 4.992 0 0112 15a4.992 4.992 0 016.879 2.804M15 10a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <span><?php echo htmlspecialchars($user['first_name']); ?></span>
                </button>
                <div id="profileMenu" class="hidden absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg z-10">
                    <div class="p-2 text-sm">
                        <p class="text-gray-700 dark:text-gray-200"><?php echo htmlspecialchars($user['email']); ?></p>
                        <hr class="my-1 border-gray-200 dark:border-gray-700">
                        <button id="editProfileBtn" class="w-full text-left py-1 px-2 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded">Edit Profile</button>
                        <form method="POST" class="mt-1">
                            <input type="hidden" name="action" value="logout">
                            <button type="submit" class="w-full text-left py-1 px-2 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded">Logout</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <header class="hero bg-gradient-to-r from-blue-600 to-blue-400 dark:from-gray-800 dark:to-gray-900 p-6 text-center rounded-lg shadow-lg mt-4">
        <h1 class="text-4xl font-bold text-white dark:text-light-text">Welcome, <span class="font-extrabold"><?php echo htmlspecialchars($user['first_name']); ?>!</span></h1>
        <p class="text-white dark:text-gray-300 mt-2">Check below if your lost item has been found or post a report.</p>
    </header>

    <div id="dashboard" class="container mx-auto p-6">
        <div class="mb-6 relative">
            <input id="searchBar" type="text" placeholder="Search reported items..." class="w-full p-3 border border-gray-300 rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-600" />
            <button id="clearSearch" class="hidden absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300" aria-label="Clear search">×</button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4 text-gray-700 dark:text-light-text flex items-center"><i class="fas fa-search mr-2"></i>Recent Lost Items</h2>
                <p id="recentLostCount" class="text-3xl font-bold text-yellow-500"><?php echo count($lost_items); ?></p>
            </div>
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4 text-gray-700 dark:text-light-text flex items-center"><i class="fas fa-check-circle mr-2"></i>Recent Found Items</h2>
                <p id="recentFoundCount" class="text-3xl font-bold text-green-500"><?php echo count($found_items); ?></p>
            </div>
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4 text-gray-700 dark:text-light-text flex items-center"><i class="fas fa-link mr-2"></i>Matched Items</h2>
                <p id="matchedItemsCount" class="text-3xl font-bold text-blue-500"><?php echo count($matched_items); ?></p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4 text-gray-700 dark:text-light-text">Recent Lost Items</h2>
                <div id="recentLostItems" class="scrollable space-y-4">
                    <?php foreach ($lost_items as $item) { ?>
                        <div id="item-<?php echo $item['id']; ?>" class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition flex justify-between items-center">
                            <?php if ($item['image']) { ?>
                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="Item Image" class="w-20 h-20 rounded object-cover mb-2">
                            <?php } ?>
                            <div>
                                <h3 class="font-semibold text-gray-800 dark:text-light-text"><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p class="text-gray-600 dark:text-gray-300 text-sm">Status: <span class="status-badge <?php echo strtolower($item['status']); ?>"><?php echo htmlspecialchars($item['status']); ?></span></p>
                            </div>
                            <button class="bg-blue-600 text-white px-2 py-1 rounded hover:bg-blue-700 text-sm view-btn" data-item-id="<?php echo $item['id']; ?>" data-view-mode="true">View</button>
                        </div>
                    <?php } ?>
                    <?php if (empty($lost_items)) { ?>
                        <p class="text-gray-600 dark:text-gray-400">No lost items available.</p>
                    <?php } ?>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4 text-gray-700 dark:text-light-text">Recent Found Items</h2>
                <div id="recentFoundItems" class="scrollable space-y-4">
                    <?php foreach ($found_items as $item) { ?>
                        <div id="item-<?php echo $item['id']; ?>" class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition flex justify-between items-center">
                            <?php if ($item['image']) { ?>
                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="Item Image" class="w-20 h-20 rounded object-cover mb-2">
                            <?php } ?>
                            <div>
                                <h3 class="font-semibold text-gray-800 dark:text-light-text"><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p class="text-gray-600 dark:text-gray-300 text-sm">Status: <span class="status-badge <?php echo strtolower($item['status']); ?>"><?php echo htmlspecialchars($item['status']); ?></span></p>
                            </div>
                            <button class="bg-blue-600 text-white px-2 py-1 rounded hover:bg-blue-700 text-sm view-btn" data-item-id="<?php echo $item['id']; ?>" data-view-mode="true">View</button>
                        </div>
                    <?php } ?>
                    <?php if (empty($found_items)) { ?>
                        <p class="text-gray-600 dark:text-gray-400">No found items available.</p>
                    <?php } ?>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4 text-gray-700 dark:text-gray-400">Matched Items</h3>
                <div id="matchedItems" class="scrollable space-y-4">
                    <?php foreach ($matched_items as $item) { ?>
                        <div id="item-<?php echo $item['id']; ?>" class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition flex justify-between items-center">
                            <?php if ($item['image']) { ?>
                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="Item Image" class="w-20 h-20 rounded object-cover mb-2">
                            <?php } ?>
                            <div>
                                <h3 class="font-semibold text-gray-800 dark:text-light-text"><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p class="text-gray-600 dark:text-gray-300 text-sm">Status: <span class="status-badge <?php echo strtolower($item['status']); ?>"><?php echo htmlspecialchars($item['status']); ?></span></p>
                            </div>
                            <button class="bg-blue-600 text-white px-2 py-1 rounded hover:bg-blue-700 text-sm view-btn" data-item-id="<?php echo $item['id']; ?>" data-view-mode="true">View</button>
                        </div>
                    <?php } ?>
                    <?php if (empty($matched_items)) { ?>
                        <p class="text-gray-600 dark:text-gray-400">No matched items available.</p>
                    <?php } ?>
                </div>
            </div>
        </div>

        <div class="flex space-x-4 mb-6">
            <button id="reportLostBtn" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-700 transition">Report a Lost Item</button>
            <button id="reportFoundBtn" class="bg-green-500 text-white px-4 py-2 rounded-lg font-semibold hover:bg-green-600 transition">Report a Found Item</button>
        </div>

        <div id="claimStatus" class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md mb-6">
            <h2 class="text-xl font-semibold mb-4 text-gray-700 dark:text-light-text">My Reports</h2>
            <div class="scrollable space-y-4" id="myReportsContainer">
                <?php foreach ($my_reports as $item) { ?>
                    <div id="report-<?php echo $item['id']; ?>" class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition flex justify-between items-center">
                        <div>
                            <h3 class="font-semibold text-gray-800 dark:text-light-text"><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p class="text-gray-600 dark:text-gray-300 text-sm">Status: <span class="status-badge <?php echo strtolower($item['status']); ?>"><?php echo htmlspecialchars($item['status']); ?></span></p>
                        </div>
                        <button class="bg-blue-600 text-white px-2 py-1 rounded hover:bg-blue-700 text-sm manage-btn" data-item-id="<?php echo $item['id']; ?>">Manage</button>
                    </div>
                <?php } ?>
                <?php if (empty($my_reports)) { ?>
                    <p class="text-gray-600 dark:text-gray-400">No reports available.</p>
                <?php } ?>
            </div>
        </div>

        <div id="feedback" class="feedback bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md text-center">
            <h4 class="text-xl font-semibold mb-4 text-gray-700 dark:text-light-text">Send us a suggestion or feedback</h4>
            <form method="POST">
                <input type="hidden" name="action" value="feedback">
                <div class="form-group">
                    <label for="feedback" class="block text-gray-700 dark:text-light-text mb-1">Feedback</label>
                    <textarea id="feedback" name="feedback" placeholder="Enter your feedback..." class="w-full p-2 border border-gray-300 rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-600"></textarea>
                    <div class="error-message" id="feedbackError"></div>
                </div>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 mt-4 transition">Submit Feedback</button>
            </form>
        </div>
    </div>

    <div id="reportModal" class="modal hidden">
        <div class="modal-content dark:bg-gray-800">
            <span class="close text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">×</span>
            <h2 id="reportModalTitle" class="text-2xl font-semibold text-gray-700 dark:text-light-text mb-4"></h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="report">
                <input type="hidden" name="type" id="reportType">
                <input type="hidden" name="item_id" id="reportItemId">
                <div class="form-group mb-4">
                    <label class="block text-gray-700 dark:text-light-text mb-1">Item Name</label>
                    <input id="reportItemName" name="reportItemName" type="text" class="w-full p-2 border border-gray-300 rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-600">
                    <p id="reportItemNameError" class="error-message text-red-500 text-sm hidden">Item name is required</p>
                </div>
                <div class="form-group mb-4">
                    <label class="block text-gray-700 dark:text-light-text mb-1">Description</label>
                    <textarea id="reportDescription" name="reportDescription" class="w-full p-2 border border-gray-300 rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-600"></textarea>
                    <p id="reportDescriptionError" class="error-message text-red-500 text-sm hidden">Description is required</p>
                </div>
                <div class="form-group mb-4">
                    <label class="block text-gray-700 dark:text-light-text mb-1">Location</label>
                    <input id="reportLocation" name="reportLocation" type="text" class="w-full p-2 border border-gray-300 rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-600">
                    <p id="reportLocationError" class="error-message text-red-500 text-sm hidden">Location is required</p>
                </div>
                <div class="form-group mb-4">
                    <label class="block text-gray-700 dark:text-light-text mb-1">Date Lost/Found</label>
                    <input id="reportDate" name="reportDate" type="date" class="w-full p-2 border border-gray-300 rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-600">
                    <p id="reportDateError" class="error-message text-red-500 text-sm hidden">Date is required</p>
                </div>
                <div class="form-group mb-4">
                    <label class="block text-gray-700 dark:text-light-text mb-1">Upload Image (JPEG/PNG, max 2MB)</label>
                    <input id="itemImage" name="itemImage" type="file" accept="image/jpeg,image/png,image/gif" class="w-full p-2 border border-gray-300 rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600">
                </div>
                <div class="flex space-x-4">
                    <button type="submit" id="submitReport" class="bg-blue-600 text-white px-3 py-1.5 rounded-lg hover:bg-blue-700 transition">Submit</button>
                    <button type="button" id="closeReportModal" class="bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-200 px-3 py-1.5 rounded-lg hover:bg-gray-400 dark:hover:bg-gray-500 transition">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="manageModal" class="modal hidden">
        <div class="modal-content dark:bg-gray-800">
            <span class="close text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">×</span>
            <h2 id="manageModalTitle" class="text-2xl font-semibold text-gray-700 dark:text-light-text mb-4"></h2>
            <div id="manageModalContent">
                <p id="manageModalDescription" class="text-gray-700 dark:text-light-text mb-2">Description: <span id="manageModalDescriptionText"></span></p>
                <p id="manageModalLocation" class="text-gray-700 dark:text-light-text mb-2">Location: <span id="manageModalLocationText"></span></p>
                <p id="manageModalDate" class="text-gray-700 dark:text-light-text mb-2">Date: <span id="manageModalDateText"></span></p>
                <p id="manageModalStatus" class="text-gray-700 dark:text-light-text mb-2">Status: <span id="manageModalStatusText"></span></p>
                <img id="manageModalImage" class="w-48 h-48 rounded mb-2" alt="Item Image">
                <div class="flex space-x-4 mt-4">
                    <?php if (isset($user['role']) && $user['role'] === 'admin') { ?>
                        <button id="approveButton" class="bg-green-600 text-white px-2 py-1 rounded hover:bg-green-700 text-sm">Approve</button>
                    <?php } ?>
                    <button id="editButton" class="bg-blue-600 text-white px-2 py-1 rounded hover:bg-blue-700 text-sm">Edit</button>
                    <button id="deleteButton" class="bg-red-600 text-white px-2 py-1 rounded hover:bg-red-700 text-sm">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <div id="editProfileModal" class="modal hidden">
        <div class="modal-content dark:bg-gray-800">
            <span class="close text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">×</span>
            <h2 class="text-2xl font-semibold text-gray-700 dark:text-light-text mb-4">Edit Profile</h2>
            <form method="POST">
                <input type="hidden" name="action" value="update_profile">
                <div class="form-group mb-4">
                    <label class="block text-gray-700 dark:text-light-text mb-1">Name</label>
                    <input id="editName" name="editName" type="text" value="<?php echo htmlspecialchars($user['first_name']); ?>" class="w-full p-2 border border-gray-300 rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-600">
                    <p id="editNameError" class="error-message text-red-500 text-sm hidden">Name is required</p>
                </div>
                <div class="form-group mb-4">
                    <label class="block text-gray-700 dark:text-light-text mb-1">Email</label>
                    <input id="editEmail" name="editEmail" type="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="w-full p-2 border border-gray-300 rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-600">
                    <p id="editEmailError" class="error-message text-red-500 text-sm hidden">Valid email is required</p>
                </div>
                <div class="flex space-x-4">
                    <button type="submit" id="saveProfile" class="bg-blue-600 text-white px-3 py-1.5 rounded-lg hover:bg-blue-700 transition">Save</button>
                    <button type="button" id="cancelEditProfile" class="bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-200 px-3 py-1.5 rounded-lg hover:bg-gray-400 dark:hover:bg-gray-500 transition">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="notificationModal" class="modal hidden">
        <div class="modal-content dark:bg-gray-800">
            <span class="close text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300" id="closeNotificationModal">×</span>
            <h2 class="text-2xl font-semibold text-gray-700 dark:text-light-text mb-4">Notifications</h2>
            <div id="notificationContent" class="scrollable space-y-4">
                <?php foreach ($notifications as $notification) { ?>
                    <div class="p-3 bg-yellow-500 text-white rounded-lg notification relative" data-id="<?php echo $notification['id']; ?>">
                        <p><?php echo htmlspecialchars($notification['message']); ?> <span class="text-xs opacity-75"><?php echo date('Y-m-d H:i', strtotime($notification['created_at'])); ?></span></p>
                        <span class="close-notif absolute right-2 top-2 cursor-pointer text-white hover:text-red-500">×</span>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <div id="customModal" class="modal hidden flex items-center justify-center">
        <div class="modal-content dark:bg-gray-800">
            <span class="close text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300" id="customModalClose">×</span>
            <p id="customModalMessage" class="text-gray-700 dark:text-light-text mb-4"></p>
            <div id="customModalButtons" class="flex space-x-4">
                <button id="customModalConfirm" class="bg-blue-600 text-white px-3 py-1.5 rounded-lg hover:bg-blue-700 transition hidden">Confirm</button>
                <button id="customModalCancel" class="bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-200 px-3 py-1.5 rounded-lg hover:bg-gray-400 dark:hover:bg-gray-500 transition hidden">Cancel</button>
                <button id="customModalOK" class="bg-blue-600 text-white px-3 py-1.5 rounded-lg hover:bg-blue-700 transition hidden">OK</button>
            </div>
        </div>
    </div>

    <section class="contact-follow flex justify-between max-w-4xl mx-auto flex-wrap gap-4 bg-gray-800 text-white dark:text-light-text p-4">
        <div class="contact">
            <h4 class="text-lg font-semibold mb-2"><i class="fas fa-envelope"></i> Contact Us</h4>
            <p class="text-sm"><i class="fas fa-envelope"></i> Email: finder@campus.edu</p>
            <p class="text-sm"><i class="fas fa-phone"></i> Phone: 63+ 09060721751</p>
            <p class="text-sm"><i class="fas fa-building"></i> Office: PLSP CCST Building</p>
            <p class="text-sm"><i class="fas fa-clock"></i> Office Hours: Monday - Friday, 8 AM - 5 PM</p>
        </div>
        <div class="divider hidden md:block w-px bg-gray-300"></div>
        <div class="follow">
            <h4 class="text-lg font-semibold mb-2"><i class="fas fa-share-alt"></i> Follow Us</h4>
            <div class="flex space-x-4 text-sm">
                <a href="https://web.facebook.com/princemelchor.banatlao.73" target="_blank" rel="noopener noreferrer" class="text-white hover:text-blue-600 transition">Facebook</a>
                <a href="https://twitter.com/login" target="_blank" rel="noopener noreferrer" class="text-white hover:text-blue-600 transition">Twitter</a>
                <a href="https://www.instagram.com/prince_bntl/" target="_blank" rel="noopener noreferrer" class="text-white hover:text-blue-600 transition">Instagram</a>
            </div>
        </div>
    </section>

    <footer class="bg-gray-800 text-white dark:text-light-text text-center p-4 mt-6">
        <p class="text-sm mt-2">© 2025 CampusFinder. All rights reserved.</p>
    </footer>

    <script src="home.js"></script>
</body>
</html>