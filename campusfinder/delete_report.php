<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['item_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized or missing ID']);
    exit;
}

$user_id = $_SESSION['user_id'];
$item_id = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
if (!$item_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid Item ID']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("DELETE FROM items WHERE id = ? AND user_id = ?");
    $stmt->execute([$item_id, $user_id]);
    if ($stmt->rowCount() > 0) {
        $pdo->commit();
        echo json_encode(['success' => true]);
    } else {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Item not found or not authorized to delete']);
    }
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Error deleting report: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . htmlspecialchars($e->getMessage())]);
}
?>