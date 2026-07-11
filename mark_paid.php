<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['doctor_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

$prescription_id = $_POST['prescription_id'] ?? '';
if (empty($prescription_id)) {
    echo json_encode(['success' => false, 'message' => 'No prescription ID']);
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE prescriptions SET payment_status = 'paid' WHERE prescription_id = ?");
    $success = $stmt->execute([$prescription_id]);
    
    if ($success && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Payment confirmed!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Prescription not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>