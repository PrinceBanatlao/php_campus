<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);


if (session_status() === PHP_SESSION_NONE) {
    try {
        session_start();
    } catch (Exception $e) {
        error_log("Session start failed: " . $e->getMessage());
        header("Location: landing.php?error=Session initialization failed");
        exit;
    }
}

require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: landing.php");
    exit;
}

$user_id = $_SESSION['user_id'];
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        throw new Exception("User not found for ID: $user_id");
    }
} catch (Exception $e) {
    error_log("User query failed: " . $e->getMessage());
    header("Location: landing.php?error=" . urlencode("User not found or database error"));
    exit;
}

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'update_item_status') {
            $item_id = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
            $status = filter_input(INPUT_POST, 'status', FILTER_UNSAFE_RAW);
            $status = $status !== false ? htmlspecialchars(trim($status), ENT_QUOTES, 'UTF-8') : '';
            if ($item_id && in_array($status, ['approved', 'matched', 'rejected'])) {
                $stmt = $pdo->prepare("UPDATE items SET status = ? WHERE id = ?");
                $stmt->execute([$status, $item_id]);
                
                $stmt = $pdo->prepare("SELECT name, user_id FROM items WHERE id = ?");
                $stmt->execute([$item_id]);
                $item = $stmt->fetch(PDO::FETCH_ASSOC);
                $notification = '';
                if ($status === 'approved') {
                    $notification = "Your report ({$item['name']}) has been approved! Please claim it to the CCST building student council 2nd floor.";
                } elseif ($status === 'matched') {
                    $notification = "An item matching your report ({$item['name']}) has been found! Please go to the CCST building student council 2nd floor to claim it.";
                } elseif ($status === 'rejected') {
                    $notification = "Your post on {$item['name']} was rejected.";
                }
                if ($notification) {
                    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())");
                    $stmt->execute([$item['user_id'], $notification]);
                }

                $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, created_at) VALUES (?, ?, NOW())");
                $stmt->execute([$user_id, "Updated item $item_id status to $status"]);
                $successMessage = "Item status updated to $status successfully!";
            } elseif ($item_id && $status === 'deleted') {
                $stmt = $pdo->prepare("SELECT name, user_id FROM items WHERE id = ?");
                $stmt->execute([$item_id]);
                $item = $stmt->fetch(PDO::FETCH_ASSOC);
                $stmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
                $stmt->execute([$item_id]);
                $notification = "Your post on {$item['name']} has been deleted by an admin.";
                if ($notification) {
                    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())");
                    $stmt->execute([$item['user_id'], $notification]);
                }
                $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, created_at) VALUES (?, ?, NOW())");
                $stmt->execute([$user_id, "Deleted item $item_id"]);
                $successMessage = "Item deleted successfully!";
            }
            header("Location: admin.php?success=" . urlencode($successMessage));
            exit;
        } elseif ($_POST['action'] === 'delete_user') {
            $delete_user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
            if ($delete_user_id && $delete_user_id !== $user_id) {
                
                $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ?");
                $stmt->execute([$delete_user_id]);

               
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
                $stmt->execute([$delete_user_id]);
                if ($stmt->rowCount() > 0) {
                    $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, created_at) VALUES (?, ?, NOW())");
                    $stmt->execute([$user_id, "Deleted user $delete_user_id"]);
                    $successMessage = "User deleted successfully!";
                } else {
                    $errorMessage = "No matching non-admin user found or invalid user.";
                }
            } else {
                $errorMessage = "Cannot delete admin or invalid user.";
            }
            header("Location: admin.php?success=" . urlencode($successMessage) . "&error=" . urlencode($errorMessage));
            exit;
        } elseif ($_POST['action'] === 'logout') {
            
            session_destroy();
            header("Location: landing.php");
            header("Cache-Control: no-cache, must-revalidate");
            exit;
        }
    } catch (PDOException $e) {
        $errorMessage = "Database error: " . htmlspecialchars($e->getMessage());
        error_log("Admin action error: " . $e->getMessage());
        header("Location: admin.php?error=" . urlencode($errorMessage));
        exit;
    }
}

try {
    $pending_items = $pdo->query("SELECT * FROM items WHERE status = 'pending' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    $processed_items = $pdo->query("SELECT * FROM items WHERE status IN ('approved', 'matched', 'rejected') ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    $users = $pdo->query("SELECT * FROM users WHERE role != 'admin' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    $logs = $pdo->query("SELECT al.*, u.username FROM admin_logs al JOIN users u ON al.admin_id = u.id ORDER BY al.created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    $user_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role != 'admin'")->fetchColumn();
    $feedbacks = $pdo->query("SELECT f.*, u.username FROM feedback f JOIN users u ON f.user_id = u.id ORDER BY f.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = "Failed to load data: " . htmlspecialchars($e->getMessage());
    error_log("Data fetch error: " . $e->getMessage());
}

$pending_lost = array_filter($pending_items, fn($item) => $item['type'] === 'lost');
$pending_found = array_filter($pending_items, fn($item) => $item['type'] === 'found');

$successMessage = isset($_GET['success']) ? urldecode($_GET['success']) : '';
$errorMessage = isset($_GET['error']) ? urldecode($_GET['error']) : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusFinder - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .scrollable { max-height: 300px; overflow-y: auto; }
        .modal { display: none; position: fixed; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); }
        #statusModal { z-index: 1100; }
        #detailsModal, #deleteModal { z-index: 1000; }
        #imageModal { z-index: 1200; background-color: rgba(0, 0, 0, 0.9); }
        .modal-content { background-color: var(--card-bg); margin: 5% auto; padding: 2rem; border-radius: 10px; width: 90%; max-width: 400px; text-align: center; }
        #imageModal .modal-content { background: none; box-shadow: none; position: relative; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; }
        #enlargedImage { max-width: 95%; max-height: 95%; object-fit: contain; border-radius: 10px; }
        #imageModal .close { position: absolute; top: 20px; right: 20px; color: white; font-size: 2rem; }
        .status-btn { margin: 0 0.5rem; padding: 0.5rem 1rem; border-radius: 5px; cursor: pointer; }
        .approved-btn { background-color: #8e44ad; color: white; }
        .matched-btn { background-color: #27ae60; color: white; }
        .rejected-btn { background-color: #e74c3c; color: white; }
        .notification { padding: 1rem; text-align: center; margin-bottom: 1rem; border-radius: 5px; font-weight: 600; opacity: 1; transition: opacity 0.5s ease-out; }
        .notification.fade-out { opacity: 0; }
        .details-btn { background-color: #4a6bff; color: white; padding: 0.5rem 1rem; border-radius: 5px; text-decoration: none; transition: background-color 0.3s; }
        .details-btn:hover { background-color: #3a5bef; }
        .item-card {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background-color: var(--card-bg);
            border-radius: 10px;
            margin-bottom: 1rem;
            justify-content: space-between;
        }
        .item-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 5px;
            display: none;
        }
        .item-image[src]:not([src=""]) {
            display: block;
        }
        .delete-btn {
            margin-left: auto;
            padding: 0.5rem 1rem;
            background-color: #e74c3c;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .delete-btn:hover {
            background-color: #c0392b;
        }
        .user-record, .feedback-record {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background-color: var(--card-bg);
            border-radius: 10px;
            margin-bottom: 1rem;
            justify-content: space-between;
        }
    </style>
</head>
<body>
    <?php if ($successMessage || $errorMessage) { ?>
        <div class="notification <?php echo $successMessage ? 'success' : 'error'; ?>" id="notification">
            <?php echo htmlspecialchars($successMessage ?: $errorMessage); ?>
        </div>
    <?php } ?>
    <header>
        <div class="nav">
            <img src="logo ipt.png" alt="CampusFinder Logo" class="logo">
            <ul class="nav-links">
                <li><span class="nav-title">Admin Panel</span></li>
            </ul>
            <div class="profile">
                <button id="profileBtn" class="nav-link">
                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($user['username']); ?>
                </button>
                <div id="profileMenu" class="profile-menu hidden">
                    <button id="darkModeToggle" class="profile-option">Dark Mode: <span id="darkModeStatus">Off</span></button>
                    <form method="POST" class="profile-option">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <section class="admin-panel">
        <div class="container">
            <h1><i class="fas fa-shield-alt"></i> Admin Dashboard</h1>

            <div class="section">
                <h2>Dashboard</h2>
                <div class="scrollable space-y-4">
                    <?php foreach ($processed_items as $item) {
                        $user_stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
                        $user_stmt->execute([$item['user_id']]);
                        $item_user = $user_stmt->fetch(PDO::FETCH_ASSOC);
                        $image_path = htmlspecialchars($item['image'] ?? '');
                    ?>
                        <div class="item-card">
                            <img src="<?php echo $image_path; ?>" alt="Item Image" class="item-image">
                            <div>
                                <h3 class="font-semibold"><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p class="text-sm">User: <?php echo htmlspecialchars($item_user['username'] ?? 'Unknown'); ?>, Status: <?php echo htmlspecialchars($item['status']); ?></p>
                            </div>
                            <a href="#" class="details-btn" onclick="viewDetails(<?php echo $item['id']; ?>, true)">View Details</a>
                            <button class="delete-btn" onclick="confirmDelete(<?php echo $item['id']; ?>)"><i class="fas fa-trash"></i></button>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <div class="section">
                <h2>Pending Lost Items</h2>
                <div class="scrollable space-y-4">
                    <?php foreach ($pending_lost as $item) {
                        $user_stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
                        $user_stmt->execute([$item['user_id']]);
                        $item_user = $user_stmt->fetch(PDO::FETCH_ASSOC);
                        $image_path = htmlspecialchars($item['image'] ?? '');
                    ?>
                        <div class="item-card">
                            <img src="<?php echo $image_path; ?>" alt="Item Image" class="item-image">
                            <div>
                                <h3 class="font-semibold"><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p class="text-sm">User: <?php echo htmlspecialchars($item_user['username'] ?? 'Unknown'); ?></p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <a href="#" class="details-btn" onclick="viewDetails(<?php echo $item['id']; ?>, false)">View Details</a>
                                <button class="status-btn approved-btn" onclick="confirmStatus(<?php echo $item['id']; ?>, 'approved')">Approve</button>
                                <button class="status-btn matched-btn" onclick="confirmStatus(<?php echo $item['id']; ?>, 'matched')">Match</button>
                                <button class="status-btn rejected-btn" onclick="confirmStatus(<?php echo $item['id']; ?>, 'rejected')">Reject</button>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <div class="section">
                <h2>Pending Found Items</h2>
                <div class="scrollable space-y-4">
                    <?php foreach ($pending_found as $item) {
                        $user_stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
                        $user_stmt->execute([$item['user_id']]);
                        $item_user = $user_stmt->fetch(PDO::FETCH_ASSOC);
                        $image_path = htmlspecialchars($item['image'] ?? '');
                    ?>
                        <div class="item-card">
                            <img src="<?php echo $image_path; ?>" alt="Item Image" class="item-image">
                            <div>
                                <h3 class="font-semibold"><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p class="text-sm">User: <?php echo htmlspecialchars($item_user['username'] ?? 'Unknown'); ?></p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <a href="#" class="details-btn" onclick="viewDetails(<?php echo $item['id']; ?>, false)">View Details</a>
                                <button class="status-btn approved-btn" onclick="confirmStatus(<?php echo $item['id']; ?>, 'approved')">Approve</button>
                                <button class="status-btn matched-btn" onclick="confirmStatus(<?php echo $item['id']; ?>, 'matched')">Match</button>
                                <button class="status-btn rejected-btn" onclick="confirmStatus(<?php echo $item['id']; ?>, 'rejected')">Reject</button>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <div class="section">
                <h2>Users Records (Total: <?php echo htmlspecialchars($user_count); ?>)</h2>
                <div class="scrollable space-y-4">
                    <?php foreach ($users as $user) { ?>
                        <div class="user-record">
                            <div>
                                <h3 class="font-semibold"><?php echo htmlspecialchars($user['username']); ?></h3>
                                <p class="text-sm">Email: <?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                            <form method="POST" class="inline">
                                <input type="hidden" name="action" value="delete_user">
                                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id']); ?>">
                                <button type="submit" class="delete-btn"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <div class="section">
                <h2>Feedback</h2>
                <div class="scrollable space-y-4">
                    <?php foreach ($feedbacks as $feedback) { ?>
                        <div class="feedback-record">
                            <div>
                                <h3 class="font-semibold"><?php echo htmlspecialchars($feedback['username']); ?></h3>
                                <p class="text-sm">Feedback: <?php echo htmlspecialchars($feedback['feedback_text']); ?></p>
                                <p class="text-sm">Date: <?php echo htmlspecialchars($feedback['created_at']); ?></p>
                            </div>
                        </div>
                    <?php } ?>
                    <?php if (empty($feedbacks)) { ?>
                        <p class="text-sm text-gray-600 dark:text-gray-400">No feedback available.</p>
                    <?php } ?>
                </div>
            </div>

            <div class="section">
                <h2>Recent Admin Actions</h2>
                <div class="scrollable space-y-2" style="max-height: 200px;">
                    <?php foreach ($logs as $log) { ?>
                        <p class="p-2 bg-gray-50 dark:bg-gray-700 rounded-lg"><?php echo htmlspecialchars($log['username'] . ': ' . $log['action'] . ' at ' . $log['created_at']); ?></p>
                    <?php } ?>
                </div>
            </div>
        </div>
    </section>

    <div id="statusModal" class="modal">
        <div class="modal-content">
            <span class="close">×</span>
            <h2 id="modalMessage"></h2>
            <div class="mt-4">
                <button id="confirmStatus" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">OK</button>
                <button id="cancelStatus" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400 ml-2">Cancel</button>
            </div>
        </div>
    </div>

    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <span class="close">×</span>
            <h2>Item Details</h2>
            <div id="detailsContent"></div>
            <div class="mt-6 flex justify-center space-x-2">
                <button id="closeDetails" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">Close</button>
            </div>
        </div>
    </div>

    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <span class="close">×</span>
            <h2>Confirm Deletion</h2>
            <p>Are you sure you want to delete this item?</p>
            <div class="mt-4">
                <button id="confirmDeleteBtn" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">Yes</button>
                <button id="cancelDeleteBtn" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400 ml-2">No</button>
            </div>
        </div>
    </div>

    <div id="imageModal" class="modal">
        <div class="modal-content">
            <span class="close">×</span>
            <img id="enlargedImage" src="" alt="Enlarged Item Image">
        </div>
    </div>

    <footer>
        <p>© 2025 CampusFinder. All rights reserved.<br>Your belongings matter!</p>
    </footer>

    <script src="admin.js"></script>
    <script>
        let currentItemId = null;
        let currentStatus = null;

        function confirmStatus(itemId, status) {
            currentItemId = itemId;
            currentStatus = status;
            document.getElementById('modalMessage').textContent = `Are you sure you want to ${status === 'matched' ? 'match' : status} this item?`;
            document.getElementById('statusModal').style.display = 'block';
        }

        document.getElementById('confirmStatus').addEventListener('click', () => {
            if (currentItemId && currentStatus) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                const actionInput = document.createElement('input');
                actionInput.name = 'action';
                actionInput.value = 'update_item_status';
                const itemIdInput = document.createElement('input');
                itemIdInput.name = 'item_id';
                itemIdInput.value = currentItemId;
                const statusInput = document.createElement('input');
                statusInput.name = 'status';
                statusInput.value = currentStatus;
                form.appendChild(actionInput);
                form.appendChild(itemIdInput);
                form.appendChild(statusInput);
                document.body.appendChild(form);
                form.submit();
            }
            document.getElementById('statusModal').style.display = 'none';
            // Keep detailsModal open until page reloads
        });

        document.getElementById('cancelStatus').addEventListener('click', () => {
            document.getElementById('statusModal').style.display = 'none';
            // Keep detailsModal open
        });

        document.querySelectorAll('.close').forEach(closeBtn => {
            closeBtn.addEventListener('click', () => {
                document.getElementById(closeBtn.closest('.modal').id).style.display = 'none';
            });
        });

        window.addEventListener('click', (e) => {
            document.querySelectorAll('.modal').forEach(modal => {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
        });

        function confirmDelete(itemId) {
            currentItemId = itemId;
            document.getElementById('deleteModal').style.display = 'block';
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', () => {
            if (currentItemId) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                const actionInput = document.createElement('input');
                actionInput.name = 'action';
                actionInput.value = 'update_item_status';
                const itemIdInput = document.createElement('input');
                itemIdInput.name = 'item_id';
                itemIdInput.value = currentItemId;
                const statusInput = document.createElement('input');
                statusInput.name = 'status';
                statusInput.value = 'deleted';
                form.appendChild(actionInput);
                form.appendChild(itemIdInput);
                form.appendChild(statusInput);
                document.body.appendChild(form);
                form.submit();
            }
            document.getElementById('deleteModal').style.display = 'none';
        });

        document.getElementById('cancelDeleteBtn').addEventListener('click', () => {
            document.getElementById('deleteModal').style.display = 'none';
        });

        function viewDetails(itemId, isDashboard) {
            fetch(`get_item.php?id=${itemId}`)
                .then(response => response.json())
                .then(data => {
                    const content = `
                        <p><strong>Name:</strong> ${data.name || 'N/A'}</p>
                        <p><strong>Description:</strong> ${data.description || 'N/A'}</p>
                        <p><strong>Location:</strong> ${data.location || 'N/A'}</p>
                        <p><strong>Date:</strong> ${data.date || 'N/A'}</p>
                        <p><strong>Status:</strong> ${data.status || 'N/A'}</p>
                        ${data.image ? `<img src="${data.image}" alt="Item Image" style="max-width: 200px; max-height: 200px; object-fit: cover; border-radius: 5px; cursor: pointer;" onclick="enlargeImage('${data.image}')">` : ''}
                        ${isDashboard && data.status !== 'matched' ? `<button class="matched-btn mt-2 mb-2" onclick="confirmStatus(${itemId}, 'matched')">Mark as Matched</button>` : ''}
                    `;
                    document.getElementById('detailsContent').innerHTML = content;
                    document.getElementById('detailsModal').style.display = 'block';
                })
                .catch(error => console.error('Error fetching details:', error));
        }

        document.getElementById('closeDetails').addEventListener('click', () => {
            document.getElementById('detailsModal').style.display = 'none';
        });

        function enlargeImage(src) {
            document.getElementById('enlargedImage').src = src;
            document.getElementById('imageModal').style.display = 'block';
        }

       
        document.addEventListener('DOMContentLoaded', function() {
            const notification = document.getElementById('notification');
            if (notification) {
                setTimeout(() => {
                    notification.classList.add('fade-out');
                    setTimeout(() => {
                        notification.style.display = 'none';
                    }, 500);
                }, 2000);
            }
        });
    </script>
</html>