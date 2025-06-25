<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Unknown error'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'User not logged in';
    echo json_encode($response);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notification_id'])) {
    $notification_id = filter_input(INPUT_POST, 'notification_id', FILTER_VALIDATE_INT);

    if (!$notification_id) {
        $response['message'] = 'Invalid notification ID';
        echo json_encode($response);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
        $stmt->execute([$notification_id, $user_id]);

        if ($stmt->rowCount() > 0) {
            $response['success'] = true;
            $response['message'] = 'Notification deleted successfully';
        } else {
            $response['message'] = 'Notification not found or not owned by user';
        }

        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $response['message'] = 'Database error: ' . htmlspecialchars($e->getMessage());
        error_log('Delete notification error: ' . $e->getMessage());
    }
} else {
    $response['message'] = 'Invalid request';
}

echo json_encode($response);
?>