<?php
require_once 'config/database.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    die("Invalid QR code");
}

$stmt = $pdo->prepare("SELECT * FROM prescriptions WHERE access_token = ?");
$stmt->execute([$token]);
$prescription = $stmt->fetch();

if (!$prescription) {
    die("Prescription not found");
}

if ($prescription['payment_status'] !== 'paid') {
    if (isset($_GET['esp'])) {
        echo "PAYMENT_PENDING";
        exit();
    } else {
        echo "<h2>⏳ Payment Pending</h2><p>Please complete payment at doctor's desk.</p>";
        exit();
    }
}

if (isset($_GET['esp'])) {
    echo $prescription['medicine_data'];
} else {
    header('Location: view-prescription.php?id=' . $prescription['id']);
}
?>