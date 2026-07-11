<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
requireLogin();

$doctor_id = getDoctorId();
$doctor_name = getDoctorName();

// Get statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM prescriptions WHERE doctor_id = ?");
$stmt->execute([$doctor_id]);
$prescription_count = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(DISTINCT patient_name) as total FROM prescriptions WHERE doctor_id = ?");
$stmt->execute([$doctor_id]);
$patient_count = $stmt->fetch()['total'];

// Get recent activity
$stmt = $pdo->prepare("SELECT * FROM prescriptions WHERE doctor_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$doctor_id]);
$recent_prescriptions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PharmaBot - Doctor Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-capsules"></i>
                </div>
                <span class="logo-text">PharmaBot</span>
            </div>
            <div>
                <span style="margin-right: 20px;">Welcome, Dr. <?php echo htmlspecialchars($doctor_name); ?></span>
                <a href="logout.php" class="btn btn-primary" style="width: auto;">Logout</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <h1>Doctor Dashboard</h1>
        <p>Doctor ID: <strong><?php echo htmlspecialchars($doctor_id); ?></strong></p>
        
        <div class="dashboard-grid">
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-prescription"></i>
                </div>
                <div class="card-title">Total Prescriptions</div>
                <div class="card-value"><?php echo $prescription_count; ?></div>
            </div>
            
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="card-title">Total Patients</div>
                <div class="card-value"><?php echo $patient_count; ?></div>
            </div>
            
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="card-title">This Month</div>
                <div class="card-value"><?php echo $prescription_count; ?></div>
            </div>
        </div>
        
        <div style="margin: 30px 0; display: flex; gap: 15px;">
            <a href="create-prescription.php" class="btn btn-primary" style="width: auto;">
                <i class="fas fa-plus"></i> Create New Prescription
            </a>
            <a href="view-prescriptions.php" class="btn btn-success" style="width: auto;">
                <i class="fas fa-eye"></i> View Prescriptions
            </a>
        </div>
        
        <div class="card">
            <h3>Recent Activity</h3>
            <table style="width: 100%; margin-top: 20px;">
                <thead>
                    <tr style="background: var(--light-gray);">
                        <th style="padding: 10px;">Date</th>
                        <th style="padding: 10px;">Patient</th>
                        <th style="padding: 10px;">Prescription ID</th>
                        <th style="padding: 10px;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_prescriptions as $pres): ?>
                    <tr>
                        <td style="padding: 10px;"><?php echo date('d/m/Y', strtotime($pres['created_at'])); ?></td>
                        <td style="padding: 10px;"><?php echo htmlspecialchars($pres['patient_name']); ?></td>
                        <td style="padding: 10px;"><?php echo htmlspecialchars($pres['prescription_id']); ?></td>
                        <td style="padding: 10px;">₹<?php echo number_format($pres['total_amount'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>