<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['doctor_id']) || $_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: login.php');
    exit();
}

$doctor_id = $_SESSION['doctor_id'];
$doctor_name = $_SESSION['doctor_name'];

// Generate prescription ID
$prescription_id = 'RX' . date('Ymd') . rand(1000, 9999);

// Get medicine count from form
$medicine_count = $_POST['medicine_count'] ?? 1;

// Process medicines
$processed_medicines = [];
$subtotal = 0;

// Medicine codes and rates
$medicineData = [
    'Paracetamol' => ['code' => 'P', 'rate' => 10],
    'Dolo' => ['code' => 'D', 'rate' => 10],
    'Aspirin' => ['code' => 'A', 'rate' => 8],
    'Naproxen' => ['code' => 'N', 'rate' => 12],
    'Metoprolol' => ['code' => 'M', 'rate' => 9]
];

// Loop through each medicine
for ($i = 1; $i <= $medicine_count; $i++) {
    if (isset($_POST['medicines'][$i])) {
        $med = $_POST['medicines'][$i];
        
        $medicine_name = $med['name'];
        $medicine_code = $med['code'] ?? 'P';
        $quantity = $med['frequency'] * $med['duration'];
        $subtotal_item = $quantity * $med['rate'];
        $subtotal += $subtotal_item;
        
        $processed_medicines[] = [
            'name' => $medicine_name,
            'code' => $medicine_code,
            'dosage' => $med['dosage'],
            'frequency' => $med['frequency'],
            'duration' => $med['duration'],
            'rate' => $med['rate'],
            'quantity' => $quantity,
            'subtotal' => $subtotal_item
        ];
    }
}

$gst_percent = 5;
$gst_amount = $subtotal * ($gst_percent / 100);
$total_amount = $subtotal + $gst_amount;

// Get payment status from form
$payment_status = $_POST['payment_status'] ?? 'pending';

// ===== CREATE QR CODE WITH MEDICINE COUNTS =====
$qr_data = "";
$medicine_counts = [];

// Initialize counts for all medicines
foreach ($medicineData as $med => $data) {
    $medicine_counts[$data['code']] = 0;
}

// Calculate quantities
foreach ($processed_medicines as $med) {
    $code = $med['code'];
    if (isset($medicine_counts[$code])) {
        $medicine_counts[$code] += $med['quantity'];
    }
}

// Build QR string
foreach ($medicine_counts as $code => $count) {
    $qr_data .= $code . ":" . $count . ",";
}
$qr_data = rtrim($qr_data, ',');

// If payment is pending, create a LOCKED QR code
if ($payment_status == 'pending') {
    $qr_data = "LOCKED:PAYMENT_PENDING";
}

// Save to database
try {
    // Ensure columns exist
    $pdo->exec("ALTER TABLE prescriptions ADD COLUMN IF NOT EXISTS access_token VARCHAR(64) NULL");
    $pdo->exec("ALTER TABLE prescriptions ADD COLUMN IF NOT EXISTS medicine_data TEXT NULL");
    $pdo->exec("ALTER TABLE prescriptions ADD COLUMN IF NOT EXISTS payment_status ENUM('pending','paid') DEFAULT 'pending'");
    
    $stmt = $pdo->prepare("INSERT INTO prescriptions 
        (prescription_id, doctor_id, doctor_name, patient_name, patient_age, 
         patient_gender, patient_contact, patient_address, medicines, subtotal, 
         gst_percent, gst_amount, total_amount, access_token, medicine_data, payment_status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $prescription_id,
        $doctor_id,
        $doctor_name,
        $_POST['patient_name'],
        $_POST['patient_age'],
        $_POST['patient_gender'],
        $_POST['patient_contact'],
        $_POST['patient_address'] ?? '',
        json_encode($processed_medicines),
        $subtotal,
        $gst_percent,
        $gst_amount,
        $total_amount,
        md5(time() . $prescription_id),
        $qr_data,
        $payment_status
    ]);
    
    $save_success = true;
    
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Prescription Preview - PharmaBot</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background: #f0f2f5; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; background: white; border-radius: 20px; padding: 40px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
        
        h1 { color: #2a86da; text-align: center; margin-bottom: 30px; font-size: 32px; }
        h2 { color: #2a86da; margin: 30px 0 20px; border-bottom: 2px solid #2ecc71; padding-bottom: 5px; }
        
        /* Payment Banner */
        .payment-banner {
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 20px;
            border: 3px solid;
        }
        .payment-pending { 
            background: #fff3cd; 
            border-color: #f39c12; 
        }
        .payment-paid { 
            background: #d4edda; 
            border-color: #2ecc71; 
        }
        .payment-icon { font-size: 40px; }
        .payment-text h3 { font-size: 24px; margin-bottom: 5px; }
        
        /* QR Code Section */
        .qr-section {
            text-align: center;
            margin: 40px 0;
            padding: 30px;
            background: linear-gradient(135deg, #f5f7fa, #e3f2fd);
            border-radius: 20px;
            border: 3px solid #2a86da;
            position: relative;
        }
        
        .qr-image {
            display: inline-block;
            padding: 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin: 20px 0;
            position: relative;
        }
        
        .qr-image img { 
            width: 250px; 
            height: 250px; 
            display: block;
            filter: <?php echo $payment_status == 'pending' ? 'grayscale(100%) blur(2px)' : 'none'; ?>;
            opacity: <?php echo $payment_status == 'pending' ? '0.5' : '1'; ?>;
        }
        
        /* QR Lock Overlay for Pending Payment */
        .qr-lock-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            border-radius: 15px;
            display: <?php echo $payment_status == 'pending' ? 'flex' : 'none'; ?>;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            z-index: 10;
        }
        
        .lock-icon {
            font-size: 60px;
            color: #f39c12;
            margin-bottom: 15px;
        }
        
        .lock-text {
            font-size: 20px;
            font-weight: bold;
            background: #f39c12;
            padding: 8px 25px;
            border-radius: 50px;
            color: white;
        }
        
        .lock-subtext {
            font-size: 14px;
            margin-top: 10px;
            color: #ddd;
        }
        
        .qr-data-preview {
            background: #2c3e50;
            color: <?php echo $payment_status == 'pending' ? '#f39c12' : '#2ecc71'; ?>;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 18px;
            margin: 20px 0;
            word-break: break-all;
        }
        
        /* Payment Warning */
        .payment-warning {
            background: #f39c12;
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            display: <?php echo $payment_status == 'pending' ? 'block' : 'none'; ?>;
        }
        
        .payment-success {
            background: #2ecc71;
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            display: <?php echo $payment_status == 'paid' ? 'block' : 'none'; ?>;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
            font-size: 15px;
        }
        th {
            background: linear-gradient(135deg, #2a86da, #2ecc71);
            color: white;
            padding: 15px;
            text-align: left;
        }
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .billing-box {
            background: linear-gradient(135deg, #d5f5e3, #a3e4d7);
            padding: 30px;
            border-radius: 15px;
            margin: 30px 0;
            border: 2px solid #2ecc71;
        }
        .billing-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            font-size: 18px;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        .grand-total {
            font-weight: bold;
            font-size: 24px;
            color: #2a86da;
            border-top: 2px solid #2ecc71;
            margin-top: 15px;
            padding-top: 20px;
        }
        
        .code-mapping {
            background: #2c3e50;
            color: #2ecc71;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .code-mapping table {
            width: 100%;
            color: white;
            margin: 10px 0;
        }
        .code-mapping th {
            background: #34495e;
            color: #2ecc71;
        }
        .code-mapping td {
            color: white;
            border-bottom: 1px solid #34495e;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin: 30px 0;
            flex-wrap: wrap;
        }
        .btn {
            padding: 15px 35px;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .btn-whatsapp { background: #25D366; color: white; }
        .btn-pdf { background: #e74c3c; color: white; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(0,0,0,0.2); }
        
        .phone-box {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin: 25px 0;
            border: 2px solid #25D366;
            text-align: center;
        }
        .phone-number { font-size: 28px; color: #075e54; font-weight: bold; }
        
        .loader {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(255,255,255,0.9);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }
        .spinner {
            width: 60px; height: 60px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #2ecc71;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        
        .medicine-code-box {
            background: #2a86da;
            color: white;
            padding: 3px 8px;
            border-radius: 5px;
            font-weight: bold;
            display: inline-block;
        }
        
        .footer-note {
            text-align: center;
            margin-top: 30px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            color: #666;
        }
        
        .prescription-id {
            background: #def5f0;
            padding: 10px 22px;
            border-radius: 60px;
            font-family: monospace;
            font-weight: 700;
            color: #0b524b;
        }
    </style>
</head>
<body>
    <div class="loader" id="loader">
        <div class="spinner"></div>
        <p style="margin-top: 20px;">Generating PDF...</p>
    </div>

    <div class="container" id="prescription-content">
        <h1>💊 PharmaBot Digital Prescription</h1>
        
        <?php if (isset($error)): ?>
            <div style="color: red; padding: 15px; background: #ffeeee; border-radius: 10px; margin-bottom: 20px;">
                ❌ <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <!-- Payment Status Banner -->
        <div class="payment-banner <?php echo $payment_status == 'paid' ? 'payment-paid' : 'payment-pending'; ?>">
            <div class="payment-icon">
                <?php if ($payment_status == 'paid'): ?>
                    <i class="fas fa-check-circle" style="color: #2ecc71;"></i>
                <?php else: ?>
                    <i class="fas fa-lock" style="color: #f39c12;"></i>
                <?php endif; ?>
            </div>
            <div class="payment-text">
                <h3 style="color: <?php echo $payment_status == 'paid' ? '#2ecc71' : '#f39c12'; ?>;">
                    <?php echo $payment_status == 'paid' ? '✅ PAYMENT COMPLETED' : '🔒 PAYMENT PENDING - QR LOCKED'; ?>
                </h3>
                <p>
                    <?php echo $payment_status == 'paid' 
                        ? 'QR code is ACTIVE and ready to scan' 
                        : 'QR code is LOCKED - Cannot be scanned until payment is completed'; ?>
                </p>
            </div>
        </div>
        
        <!-- Payment Status Messages -->
        <?php if ($payment_status == 'pending'): ?>
        <div class="payment-warning">
            <i class="fas fa-exclamation-triangle"></i> 
            <strong>⚠️ QR CODE IS LOCKED</strong> - The patient must complete payment before the QR code can be scanned by the ESP32-CAM machine.
        </div>
        <?php else: ?>
        <div class="payment-success">
            <i class="fas fa-check-circle"></i> 
            <strong>✅ QR CODE IS ACTIVE</strong> - Ready to be scanned by the ESP32-CAM dispensing machine.
        </div>
        <?php endif; ?>
        
        <!-- Prescription Info -->
        <div style="display: flex; justify-content: space-between; margin-bottom: 25px; padding: 15px; background: #f8f9fa; border-radius: 10px;">
            <div><strong>Prescription ID:</strong> <?php echo $prescription_id; ?></div>
            <div><strong>Date:</strong> <?php echo date('d/m/Y'); ?></div>
            <div><strong>Time:</strong> <?php echo date('h:i A'); ?></div>
        </div>
        
        <div style="display: flex; justify-content: space-between; margin-bottom: 25px; padding: 15px; background: #e8f4fd; border-radius: 10px;">
            <div><strong>Doctor:</strong> Dr. <?php echo htmlspecialchars($doctor_name); ?> (<?php echo $doctor_id; ?>)</div>
            <div><strong>Patient:</strong> <?php echo htmlspecialchars($_POST['patient_name']); ?></div>
        </div>
        
        <!-- Patient Details -->
        <h2>👤 Patient Information</h2>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 25px; padding: 15px; background: #f5f5f5; border-radius: 10px;">
            <div><strong>Name:</strong> <?php echo htmlspecialchars($_POST['patient_name']); ?></div>
            <div><strong>Age:</strong> <?php echo $_POST['patient_age']; ?> years</div>
            <div><strong>Gender:</strong> <?php echo $_POST['patient_gender']; ?></div>
            <div><strong>Contact:</strong> <?php echo $_POST['patient_contact']; ?></div>
        </div>
        
        <!-- Address if provided -->
        <?php if (!empty($_POST['patient_address'])): ?>
        <div style="margin-bottom: 25px; padding: 15px; background: #f5f5f5; border-radius: 10px;">
            <strong>Address:</strong> <?php echo htmlspecialchars($_POST['patient_address']); ?>
        </div>
        <?php endif; ?>
        
        <!-- Medicines Table -->
        <h2>💊 Prescribed Medicines</h2>
        <table>
            <thead>
                <tr>
                    <th>Medicine</th>
                    <th>Code</th>
                    <th>Dosage</th>
                    <th>Frequency</th>
                    <th>Duration</th>
                    <th>Quantity</th>
                    <th>Rate (₹)</th>
                    <th>Subtotal (₹)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($processed_medicines as $med): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($med['name']); ?></strong></td>
                    <td><span class="medicine-code-box"><?php echo $med['code']; ?></span></td>
                    <td><?php echo $med['dosage']; ?> mg</td>
                    <td><?php echo $med['frequency']; ?>/day</td>
                    <td><?php echo $med['duration']; ?> days</td>
                    <td><?php echo $med['quantity']; ?></td>
                    <td>₹<?php echo number_format($med['rate'], 2); ?></td>
                    <td>₹<?php echo number_format($med['subtotal'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Medicine Code Mapping -->
        <div class="code-mapping">
            <h3 style="color: #2ecc71; margin-bottom: 15px;">🔤 Medicine Code Mapping (For ESP32-CAM)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Medicine</th>
                        <th>Code</th>
                        <th>Quantity in QR</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($medicine_counts as $code => $count): 
                        // Find medicine name for this code
                        $med_name = 'Unknown';
                        foreach ($medicineData as $name => $data) {
                            if ($data['code'] == $code) {
                                $med_name = $name;
                                break;
                            }
                        }
                    ?>
                    <tr>
                        <td><?php echo $med_name; ?></td>
                        <td><strong style="color:#2ecc71; font-size:18px;"><?php echo $code; ?></strong></td>
                        <td><?php echo $count; ?> tablets</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p style="color:#2ecc71; margin-top:10px; font-size:12px;">↑ This mapping shows what each letter code means when ESP32-CAM scans the QR</p>
        </div>
        
        <!-- Billing Summary -->
        <h2>💰 Billing Summary</h2>
        <div class="billing-box">
            <div class="billing-row">
                <span>Subtotal:</span>
                <span>₹<?php echo number_format($subtotal, 2); ?></span>
            </div>
            <div class="billing-row">
                <span>GST (<?php echo $gst_percent; ?>%):</span>
                <span>₹<?php echo number_format($gst_amount, 2); ?></span>
            </div>
            <div class="billing-row grand-total">
                <span>Total Amount:</span>
                <span>₹<?php echo number_format($total_amount, 2); ?></span>
            </div>
        </div>
        
        <!-- QR CODE SECTION with LOCK for Pending Payment -->
        <div class="qr-section">
            <h3>📱 QR Code for ESP32-CAM Dispenser</h3>
            <p style="color: #666; margin-bottom: 10px;">
                <?php echo $payment_status == 'paid' 
                    ? 'Format: P:count, D:count, A:count, N:count, M:count' 
                    : '⛔ QR CODE IS LOCKED - Complete payment to unlock'; ?>
            </p>
            
            <?php
            if ($payment_status == 'paid') {
                $qr_image = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($qr_data);
            } else {
                // Create a "LOCKED" QR code that won't work with ESP32
                $locked_message = "PAYMENT_PENDING_" . $prescription_id;
                $qr_image = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($locked_message);
            }
            ?>
            
            <div class="qr-image">
                <img src="<?php echo $qr_image; ?>" alt="Medicine QR Code">
                
                <!-- Lock Overlay for Pending Payment -->
                <div class="qr-lock-overlay">
                    <div class="lock-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <div class="lock-text">PAYMENT PENDING</div>
                    <div class="lock-subtext">QR is LOCKED</div>
                </div>
            </div>
            
            <div class="qr-data-preview">
                <strong>🔍 Raw QR Data:</strong><br>
                <?php 
                if ($payment_status == 'paid') {
                    echo htmlspecialchars($qr_data);
                } else {
                    echo "LOCKED: PAYMENT PENDING - Prescription ID: " . $prescription_id;
                }
                ?>
            </div>
            
            <?php if ($payment_status == 'pending'): ?>
            <p style="margin-top: 15px; font-weight: bold; color: #f39c12;">
                <i class="fas fa-exclamation-triangle"></i> 
                QR code is LOCKED. Patient must complete payment to unlock.
            </p>
            <?php else: ?>
            <p style="margin-top: 15px; font-weight: bold; color: #2a86da;">
                <i class="fas fa-check-circle"></i> 
                When scanned, ESP32-CAM will receive: <span style="color: #2ecc71;"><?php echo htmlspecialchars($qr_data); ?></span>
            </p>
            <?php endif; ?>
        </div>
        
        <!-- WhatsApp Section -->
        <div class="qr-section" style="border-color: #25D366;">
            <h3 style="color: #075e54;">📱 Send to Patient WhatsApp</h3>
            
            <div class="phone-box">
                <div style="margin-bottom: 10px;">Patient's WhatsApp Number:</div>
                <div class="phone-number" id="patientPhone"><?php echo $_POST['patient_contact']; ?></div>
            </div>
            
            <!-- WhatsApp Button -->
            <button class="btn btn-whatsapp" onclick="sendWhatsApp()" style="width: 100%;">
                <i class="fab fa-whatsapp"></i> Send Prescription via WhatsApp
            </button>
        </div>
        
        <!-- Action Buttons -->
        <div class="action-buttons">
            <button class="btn btn-pdf" onclick="downloadPDF()">
                <i class="fas fa-file-pdf"></i> Download PDF
            </button>
        </div>
        
        <div class="footer-note">
            <p><strong>QR Code Format:</strong> P:Paracetamol (₹10), D:Dolo (₹10), A:Aspirin (₹8), N:Naproxen (₹12), M:Metoprolol (₹9)</p>
            <p style="font-size:12px; margin-top:5px; color:<?php echo $payment_status == 'pending' ? '#f39c12' : '#2ecc71'; ?>;">
                <?php echo $payment_status == 'pending' 
                    ? '⛔ QR is LOCKED - Payment required for ESP32-CAM to scan' 
                    : '✅ QR is ACTIVE - ESP32-CAM can scan and dispense medicines'; ?>
            </p>
        </div>
    </div>

    <script>
    function downloadPDF() {
        document.getElementById('loader').style.display = 'flex';
        
        setTimeout(() => {
            const element = document.getElementById('prescription-content');
            const opt = {
                margin: [0.5, 0.5, 0.5, 0.5],
                filename: 'Prescription_<?php echo preg_replace('/[^a-zA-Z0-9]/', '_', $_POST['patient_name']); ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, backgroundColor: '#ffffff' },
                jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
            };
            
            html2pdf().set(opt).from(element).save()
                .then(() => {
                    document.getElementById('loader').style.display = 'none';
                })
                .catch((error) => {
                    console.error('PDF Error:', error);
                    document.getElementById('loader').style.display = 'none';
                    alert('Error generating PDF. Please try again.');
                });
        }, 500);
    }
    
    function sendWhatsApp() {
        let phone = "<?php echo $_POST['patient_contact']; ?>";
        
        // Clean phone number
        phone = phone.replace(/\D/g, '');
        
        // Add India country code if not present
        if (phone.length === 10) {
            phone = '91' + phone;
        } else if (phone.length === 11 && phone.startsWith('0')) {
            phone = '91' + phone.substring(1);
        }
        
        // Create QR image URL based on payment status
        <?php if ($payment_status == 'paid'): ?>
        let qrImageLink = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=<?php echo urlencode($qr_data); ?>";
        <?php else: ?>
        let qrImageLink = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=LOCKED:PAYMENT_PENDING_<?php echo $prescription_id; ?>";
        <?php endif; ?>
        
        // Build message
        let message = "🏥 *PHARMABOT PRESCRIPTION*\n\n";
        
        // Payment Status
        message += "💰 *Payment Status:* <?php echo $payment_status == 'paid' ? '✅ PAID' : '⏳ PENDING'; ?>\n";
        <?php if ($payment_status == 'pending'): ?>
        message += "⚠️ *IMPORTANT:* QR code is LOCKED until payment is completed!\n";
        <?php endif; ?>
        message += "\n";
        
        // Patient & Doctor Info
        message += "👤 *Patient:* <?php echo addslashes($_POST['patient_name']); ?>\n";
        message += "📋 *ID:* <?php echo $prescription_id; ?>\n";
        message += "👨‍⚕️ *Doctor:* Dr. <?php echo addslashes($doctor_name); ?>\n\n";
        
        // Medicines
        message += "💊 *PRESCRIBED MEDICINES*\n";
        message += "────────────────────\n";
        
        <?php foreach ($processed_medicines as $index => $med): ?>
        message += "*<?php echo $index+1; ?>. <?php echo addslashes($med['name']); ?>*\n";
        message += "   • Dosage: <?php echo $med['dosage']; ?> mg\n";
        message += "   • Schedule: <?php echo $med['frequency']; ?>×/day for <?php echo $med['duration']; ?> days\n";
        message += "   • Quantity: <?php echo $med['quantity']; ?> tablets\n";
        message += "   • Price: ₹<?php echo number_format($med['rate'], 2); ?> × <?php echo $med['quantity']; ?> = ₹<?php echo number_format($med['subtotal'], 2); ?>\n\n";
        <?php endforeach; ?>
        
        // Totals
        message += "────────────────────\n";
        message += "💰 *Subtotal:* ₹<?php echo number_format($subtotal, 2); ?>\n";
        message += "📊 *GST (5%):* ₹<?php echo number_format($gst_amount, 2); ?>\n";
        message += "💵 *TOTAL AMOUNT:* ₹<?php echo number_format($total_amount, 2); ?>\n\n";
        
        // QR Code
        message += "📸 *QR CODE FOR DISPENSER*\n";
        <?php if ($payment_status == 'paid'): ?>
        message += "✅ QR is ACTIVE - Scan this in the PharmaBot machine:\n";
        <?php else: ?>
        message += "⛔ QR is LOCKED - Complete payment to unlock:\n";
        <?php endif; ?>
        message += qrImageLink + "\n\n";
        
        // Medicine codes explanation
        message += "🔤 *Medicine Codes:*\n";
        message += "P = Paracetamol (₹10), D = Dolo (₹10)\n";
        message += "A = Aspirin (₹8), N = Naproxen (₹12)\n";
        message += "M = Metoprolol (₹9)\n\n";
        
        // Footer
        message += "✨ *Thank you for using PharmaBot!* ✨\n";
        
        // Encode and send
        let whatsappUrl = "https://wa.me/" + phone + "?text=" + encodeURIComponent(message);
        window.open(whatsappUrl, '_blank');
    }
    </script>
</body>
</html>