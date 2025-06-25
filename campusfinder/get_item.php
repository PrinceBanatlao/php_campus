<?php
   require_once 'db_connect.php';
   header('Content-Type: application/json');
   $id = $_GET['id'] ?? null;
   if ($id) {
       $stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
       $stmt->execute([$id]);
       $item = $stmt->fetch();
       if ($item) {
           echo json_encode($item);
       } else {
           echo json_encode(['error' => 'Item not found']);
       }
   } else {
       echo json_encode(['error' => 'No item ID provided']);
   }
   ?>