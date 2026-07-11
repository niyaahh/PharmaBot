<?php
// view-prescription.php - Patient view (no login required)
require_once 'config/database.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    die("Invalid prescription link");
}

// Get prescription from database
$stmt = $pdo->prepare("SELECT * FROM prescriptions WHERE access_token = ?");
$stmt->execute([$token]);
$prescription = $stmt->fetch();

if (!$prescription) {
    die("Prescription not found");
}

$medicines = json_decode($prescription['medicines'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Prescription - PharmaBot</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .prescription-card {
            max-width: 800px;
            width: 100%;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #2ecc71;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #2a86da;
            font-size: 36px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th {
            background: #2a86da;
            color: white;
            padding: 12px;
            text-align: left;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        .total {
            text-align: right;
            font-size: 20px;
            font-weight: bold;
            color: #2a86da;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #2ecc71;
        }
    </style>
</head>
<body>
    <div class="prescription-card">
        <div class="header">
            <h1>PharmaBot</h1>
            <p>Digital Prescription System</p>
        </div>
        
        <p><strong>Prescription ID:</strong> <?php echo $prescription['prescription_id']; ?></p>
        <p><strong>Date:</strong> <?php echo date('d/m/Y', strtotime($prescription['created_at'])); ?></p>
        <p><strong>Doctor:</strong> Dr. <?php echo $prescription['doctor_name']; ?></p>
        
        <h3 style="color: #2a86da; margin: 20px 0;">Patient Details</h3>
        <p><strong>Name:</strong> <?php echo $prescription['patient_name']; ?></p>
        <p><strong>Age:</strong> <?php echo $prescription['patient_age']; ?> | <strong>Gender:</strong> <?php echo $prescription['patient_gender']; ?></p>
        
        <h3 style="color: #2a86da; margin: 20px 0;">Medicines</h3>
        <table>
            <tr>
                <th>Medicine</th>
                <th>Dosage</th>
                <th>Frequency</th>
                <th>Duration</th>
                <th>Quantity</th>
            </tr>
            <?php foreach ($medicines as $med): ?>
            <tr>
                <td><?php echo htmlspecialchars($med['name']); ?></td>
                <td><?php echo $med['dosage']; ?></td>
                <td><?php echo $med['frequency']; ?>/day</td>
                <td><?php echo $med['duration']; ?> days</td>
                <td><?php echo $med['quantity']; ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <div class="total">
            Total Amount: ₹<?php echo number_format($prescription['total_amount'], 2); ?>
        </div>
    </div>
</body>
</html>