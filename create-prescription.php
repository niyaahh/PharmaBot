<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['doctor_id'])) {
    header('Location: login.php');
    exit();
}

$doctor_id = $_SESSION['doctor_id'];
$doctor_name = $_SESSION['doctor_name'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Prescription - PharmaBot</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background: #f0f2f5; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; background: white; border-radius: 20px; padding: 30px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
        h1 { color: #2a86da; text-align: center; margin-bottom: 30px; }
        h2 { color: #2a86da; margin: 20px 0; border-bottom: 2px solid #2ecc71; padding-bottom: 5px; }
        
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
        input, select, textarea { 
            width: 100%; 
            padding: 12px; 
            border: 2px solid #e0e0e0; 
            border-radius: 8px; 
            font-size: 14px; 
        }
        input:focus, select:focus, textarea:focus { 
            border-color: #2a86da; 
            outline: none; 
            box-shadow: 0 0 0 3px rgba(42,134,218,0.1);
        }
        
        .row { display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 15px; }
        .col { flex: 1; min-width: 120px; }
        
        /* Medicine Card Styling - IMPROVED */
        .medicine-card {
            background: linear-gradient(135deg, #ffffff, #f8f9fa);
            border: 2px solid #2a86da;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 25px;
            position: relative;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .medicine-card:hover {
            box-shadow: 0 8px 25px rgba(42,134,218,0.15);
            transform: translateY(-2px);
        }
        .medicine-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 3px solid #2ecc71;
        }
        .medicine-header h3 { 
            color: #2a86da; 
            font-size: 20px;
            font-weight: bold;
        }
        
        /* Medicine Selection Grid - IMPROVED */
        .medicine-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
            margin: 20px 0;
        }
        
        .med-card-btn {
            background: white;
            border: 2px solid #2a86da;
            border-radius: 15px;
            padding: 15px 10px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }
        
        .med-card-btn:hover {
            background: #2a86da;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(42,134,218,0.3);
        }
        
        .med-card-btn:hover .med-name,
        .med-card-btn:hover .med-price,
        .med-card-btn:hover .med-code {
            color: white;
        }
        
        .med-card-btn.selected {
            background: #2a86da;
            border-color: #2a86da;
        }
        
        .med-card-btn.selected .med-name,
        .med-card-btn.selected .med-price,
        .med-card-btn.selected .med-code {
            color: white;
        }
        
        .med-name {
            font-weight: bold;
            color: #2a86da;
            font-size: 16px;
        }
        
        .med-price {
            color: #2ecc71;
            font-weight: bold;
            font-size: 18px;
        }
        
        .med-code {
            background: rgba(46,204,113,0.2);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
            color: #27ae60;
            font-weight: bold;
        }
        
        .med-card-btn:hover .med-code {
            background: rgba(255,255,255,0.2);
        }
        
        /* Selected Medicine Display */
        .selected-medicine-box {
            background: #e8f4fd;
            border: 2px solid #2a86da;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .selected-med-info {
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .selected-med-name {
            font-size: 18px;
            font-weight: bold;
            color: #2a86da;
        }
        
        .selected-med-price {
            background: #2ecc71;
            color: white;
            padding: 5px 15px;
            border-radius: 25px;
            font-weight: bold;
        }
        
        .selected-med-code {
            background: #2a86da;
            color: white;
            padding: 5px 15px;
            border-radius: 25px;
            font-weight: bold;
        }
        
        .change-med-btn {
            background: #2ecc71;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .change-med-btn:hover {
            background: #27ae60;
            transform: scale(1.05);
        }
        
        /* Medicine Price Display */
        .medicine-price {
            background: linear-gradient(135deg, #2a86da, #2ecc71);
            color: white;
            padding: 12px 20px;
            border-radius: 50px;
            font-weight: bold;
            display: inline-block;
            margin-top: 15px;
            font-size: 16px;
        }
        
        /* Remove Button */
        .remove-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }
        .remove-btn:hover { background: #c0392b; transform: scale(1.05); }
        .remove-btn:disabled { background: #ccc; cursor: not-allowed; transform: none; }
        
        /* Add Medicine Button */
        .btn-add {
            background: #2ecc71;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            margin: 20px 0;
            transition: all 0.3s;
        }
        .btn-add:hover { background: #27ae60; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(46,204,113,0.3); }
        
        /* Submit Button */
        .btn-submit {
            background: #2a86da;
            color: white;
            border: none;
            padding: 18px 40px;
            border-radius: 50px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
            transition: all 0.3s;
        }
        .btn-submit:hover { background: #1e6ab3; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(42,134,218,0.3); }
        
        /* Medicine Count Badge */
        .medicine-count {
            background: #2ecc71;
            color: white;
            padding: 5px 15px;
            border-radius: 50px;
            display: inline-block;
            margin-left: 10px;
            font-size: 14px;
        }
        
        /* Code Preview */
        .code-preview {
            background: #e8f4fd;
            padding: 10px;
            border-radius: 8px;
            margin-top: 10px;
            font-size: 12px;
            color: #2a86da;
            border-left: 4px solid #2a86da;
        }
        
        /* Billing Summary Box */
        .billing-summary {
            background: linear-gradient(135deg, #f6f9fc, #e3f2fd);
            border: 3px solid #2a86da;
            border-radius: 20px;
            padding: 25px;
            margin: 30px 0;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .billing-title {
            color: #2a86da;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            border-bottom: 2px solid #2ecc71;
            padding-bottom: 10px;
        }
        
        .billing-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            font-size: 18px;
            border-bottom: 1px dashed #2a86da;
        }
        
        .billing-row.total {
            font-weight: bold;
            font-size: 24px;
            color: #2a86da;
            border-bottom: none;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 3px solid #2ecc71;
        }
        
        .billing-row.gst-row {
            color: #27ae60;
            font-weight: 600;
        }
        
        .billing-amount {
            font-family: monospace;
            font-size: 20px;
        }
        
        .billing-note {
            font-size: 12px;
            color: #666;
            text-align: right;
            margin-top: 10px;
        }
        
        /* Payment Toggle Styling - IMPROVED */
        .payment-toggle {
            display: flex;
            gap: 20px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        
        .payment-option {
            flex: 1;
            min-width: 200px;
        }
        
        .payment-option input[type="radio"] {
            display: none;
        }
        
        .payment-option label {
            display: block;
            padding: 25px;
            border: 3px solid #e0e0e0;
            border-radius: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 18px;
            font-weight: bold;
        }
        
        .payment-option input[type="radio"]:checked + label {
            transform: scale(1.02);
        }
        
        #pending:checked + label {
            background: #fff3cd;
            border-color: #f39c12;
            color: #f39c12;
            box-shadow: 0 0 20px rgba(243,156,18,0.3);
        }
        
        #paid:checked + label {
            background: #d4edda;
            border-color: #2ecc71;
            color: #2ecc71;
            box-shadow: 0 0 20px rgba(46,204,113,0.3);
        }
        
        .payment-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }
        
        .payment-status-text {
            font-size: 20px;
            margin-bottom: 5px;
        }
        
        .qr-warning {
            background: #f39c12;
            color: white;
            padding: 5px 15px;
            border-radius: 25px;
            font-size: 12px;
            margin-top: 10px;
        }
        
        .payment-option input[type="radio"]:checked + label .qr-warning {
            background: rgba(255,255,255,0.3);
        }
        
        .helper-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .required::after {
            content: " *";
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>💊 Create New Prescription</h1>
        
        <form action="prescription-preview.php" method="POST" id="prescriptionForm">
            <!-- Patient Details -->
            <h2>👤 Patient Information</h2>
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        <label class="required">Full Name</label>
                        <input type="text" name="patient_name" required placeholder="Enter patient full name">
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label class="required">Age</label>
                        <input type="number" name="patient_age" required placeholder="Enter age">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        <label class="required">Gender</label>
                        <select name="patient_gender" required>
                            <option value="" disabled selected>-- Select Gender --</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label class="required">Contact Number</label>
                        <input type="text" name="patient_contact" required placeholder="10-digit mobile number">
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label>Address (Optional)</label>
                <textarea name="patient_address" rows="2" placeholder="Enter patient address (optional)"></textarea>
            </div>
            
            <!-- Payment Status with QR Warning -->
            <h2>💰 Payment Status</h2>
            <div class="payment-toggle">
                <div class="payment-option">
                    <input type="radio" name="payment_status" id="pending" value="pending" checked>
                    <label for="pending">
                        <div class="payment-icon">⏳</div>
                        <div class="payment-status-text">Payment Pending</div>
                        <div class="helper-text">QR code will be LOCKED - Cannot scan</div>
                        <div class="qr-warning">🚫 QR DISABLED</div>
                    </label>
                </div>
                
                <div class="payment-option">
                    <input type="radio" name="payment_status" id="paid" value="paid">
                    <label for="paid">
                        <div class="payment-icon">✅</div>
                        <div class="payment-status-text">Payment Completed</div>
                        <div class="helper-text">QR code will be ACTIVE - Ready to scan</div>
                        <div class="qr-warning" style="background:#2ecc71;">✅ QR ENABLED</div>
                    </label>
                </div>
            </div>
            
            <!-- Medicines Section -->
            <h2>💊 Medicines <span class="medicine-count" id="medCount">1/5</span></h2>
            
            <div id="medicinesContainer">
                <!-- Medicine 1 (Default) -->
                <div class="medicine-card" id="medicine_1">
                    <div class="medicine-header">
                        <h3>Medicine #1</h3>
                        <button type="button" class="remove-btn" onclick="removeMedicine(1)" disabled>Remove</button>
                    </div>
                    
                    <!-- Hidden input to store selected medicine name -->
                    <input type="hidden" name="medicines[1][name]" id="med_name_1" value="">
                    <input type="hidden" name="medicines[1][code]" id="med_code_1" value="">
                    
                    <!-- Selected Medicine Display -->
                    <div class="selected-medicine-box" id="display_1">
                        <div class="selected-med-info">
                            <span class="selected-med-name" id="selected_name_1">No medicine selected</span>
                            <span class="selected-med-price" id="selected_price_1">₹0</span>
                            <span class="selected-med-code" id="selected_code_1">-</span>
                        </div>
                        <button type="button" class="change-med-btn" onclick="showMedicineGrid(1)">Change</button>
                    </div>
                    
                    <!-- Medicine Grid Selector (Initially hidden) -->
                    <div class="medicine-grid" id="grid_1" style="display: none;">
                        <div class="med-card-btn" onclick="selectMedicine(1, 'Paracetamol', 10, 'P')">
                            <span class="med-name">Paracetamol</span>
                            <span class="med-price">₹10</span>
                            <span class="med-code">Code: P</span>
                        </div>
                        <div class="med-card-btn" onclick="selectMedicine(1, 'Dolo', 10, 'D')">
                            <span class="med-name">Dolo</span>
                            <span class="med-price">₹10</span>
                            <span class="med-code">Code: D</span>
                        </div>
                        <div class="med-card-btn" onclick="selectMedicine(1, 'Aspirin', 8, 'A')">
                            <span class="med-name">Aspirin</span>
                            <span class="med-price">₹8</span>
                            <span class="med-code">Code: A</span>
                        </div>
                        <div class="med-card-btn" onclick="selectMedicine(1, 'Naproxen', 12, 'N')">
                            <span class="med-name">Naproxen</span>
                            <span class="med-price">₹12</span>
                            <span class="med-code">Code: N</span>
                        </div>
                        <div class="med-card-btn" onclick="selectMedicine(1, 'Metoprolol', 9, 'M')">
                            <span class="med-name">Metoprolol</span>
                            <span class="med-price">₹9</span>
                            <span class="med-code">Code: M</span>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label class="required">Dosage (mg)</label>
                                <input type="text" name="medicines[1][dosage]" required placeholder="e.g., 500" onchange="calculateAll()" onkeyup="calculateAll()">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label class="required">Frequency/day</label>
                                <input type="number" name="medicines[1][frequency]" required 
                                       placeholder="Times per day" min="1" 
                                       onchange="calculateAll()" onkeyup="calculateAll()">
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label class="required">Duration (days)</label>
                                <input type="number" name="medicines[1][duration]" required 
                                       placeholder="Number of days" min="1"
                                       onchange="calculateAll()" onkeyup="calculateAll()">
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label class="required">Rate (₹)</label>
                                <input type="number" name="medicines[1][rate]" id="rate_1" required 
                                       placeholder="Price per tablet" step="0.50" min="1"
                                       onchange="calculateAll()" onkeyup="calculateAll()" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Medicine Price Display -->
                    <div class="medicine-price" id="price_1">
                        <i class="fas fa-calculator"></i> Subtotal: ₹0.00
                    </div>
                    
                    <div class="code-preview" id="preview_1">
                        <i class="fas fa-info-circle"></i> Select medicine to see code
                    </div>
                </div>
            </div>
            
            <!-- ADD MEDICINE BUTTON -->
            <button type="button" class="btn-add" onclick="addNewMedicine()">
                <i class="fas fa-plus"></i> Add Another Medicine (Max 5)
            </button>
            
            <!-- BILLING SUMMARY WITH GST -->
            <div class="billing-summary">
                <div class="billing-title">💰 Billing Summary</div>
                
                <div class="billing-row">
                    <span>Subtotal:</span>
                    <span class="billing-amount" id="subtotal">₹0.00</span>
                </div>
                
                <div class="billing-row gst-row">
                    <span>GST (5%):</span>
                    <span class="billing-amount" id="gst">₹0.00</span>
                </div>
                
                <div class="billing-row total">
                    <span>Total Amount:</span>
                    <span class="billing-amount" id="total">₹0.00</span>
                </div>
                
                <div class="billing-note">
                    <i class="fas fa-info-circle"></i> GST 5% included in total
                </div>
            </div>
            
            <!-- Hidden input to track medicine count -->
            <input type="hidden" id="medicineCount" name="medicine_count" value="1">
            
            <!-- Submit Button -->
            <button type="submit" class="btn-submit">
                <i class="fas fa-file-prescription"></i> Generate Prescription & QR
            </button>
        </form>
    </div>

    <script>
        let medicineCount = 1;
        const maxMedicines = 5;
        
        // Function to show medicine grid
        function showMedicineGrid(index) {
            document.getElementById(`grid_${index}`).style.display = 'grid';
        }
        
        // Function to select medicine
        function selectMedicine(index, name, price, code) {
            // Update hidden inputs
            document.getElementById(`med_name_${index}`).value = name;
            document.getElementById(`med_code_${index}`).value = code;
            
            // Update display
            document.getElementById(`selected_name_${index}`).textContent = name;
            document.getElementById(`selected_price_${index}`).textContent = `₹${price}`;
            document.getElementById(`selected_code_${index}`).textContent = code;
            
            // Update rate input
            const rateInput = document.getElementById(`rate_${index}`);
            if (rateInput) {
                rateInput.value = price;
            }
            
            // Hide grid
            document.getElementById(`grid_${index}`).style.display = 'none';
            
            // Update preview
            const preview = document.getElementById(`preview_${index}`);
            preview.innerHTML = `<i class="fas fa-check-circle" style="color:#2ecc71;"></i> Medicine Code: ${code} (${name})`;
            
            // Recalculate
            calculateAll();
        }
        
        // Function to calculate subtotal for a medicine
        function calculateMedicineSubtotal(index) {
            const freq = parseFloat(document.querySelector(`input[name="medicines[${index}][frequency]"]`).value) || 0;
            const dur = parseFloat(document.querySelector(`input[name="medicines[${index}][duration]"]`).value) || 0;
            const rate = parseFloat(document.getElementById(`rate_${index}`).value) || 0;
            
            const quantity = freq * dur;
            const subtotal = quantity * rate;
            
            // Update price display
            const priceDisplay = document.getElementById(`price_${index}`);
            if (priceDisplay) {
                if (subtotal > 0) {
                    priceDisplay.innerHTML = `<i class="fas fa-calculator"></i> Subtotal: ₹${subtotal.toFixed(2)} (${quantity} tabs × ₹${rate.toFixed(2)})`;
                } else {
                    priceDisplay.innerHTML = `<i class="fas fa-calculator"></i> Subtotal: ₹0.00`;
                }
            }
            
            return subtotal;
        }
        
        // Function to calculate all and update billing summary
        function calculateAll() {
            let totalSubtotal = 0;
            
            for (let i = 1; i <= medicineCount; i++) {
                totalSubtotal += calculateMedicineSubtotal(i);
            }
            
            // Calculate GST (5%)
            const gst = totalSubtotal * 0.05;
            const total = totalSubtotal + gst;
            
            // Update billing summary
            document.getElementById('subtotal').textContent = `₹${totalSubtotal.toFixed(2)}`;
            document.getElementById('gst').textContent = `₹${gst.toFixed(2)}`;
            document.getElementById('total').textContent = `₹${total.toFixed(2)}`;
        }
        
        // Function to add new medicine
        function addNewMedicine() {
            if (medicineCount < maxMedicines) {
                medicineCount++;
                
                // Update hidden input
                document.getElementById('medicineCount').value = medicineCount;
                
                // Get container
                const container = document.getElementById('medicinesContainer');
                
                // Create new medicine card
                const newCard = document.createElement('div');
                newCard.className = 'medicine-card';
                newCard.id = `medicine_${medicineCount}`;
                
                newCard.innerHTML = `
                    <div class="medicine-header">
                        <h3>Medicine #${medicineCount}</h3>
                        <button type="button" class="remove-btn" onclick="removeMedicine(${medicineCount})">Remove</button>
                    </div>
                    
                    <input type="hidden" name="medicines[${medicineCount}][name]" id="med_name_${medicineCount}" value="">
                    <input type="hidden" name="medicines[${medicineCount}][code]" id="med_code_${medicineCount}" value="">
                    
                    <div class="selected-medicine-box" id="display_${medicineCount}">
                        <div class="selected-med-info">
                            <span class="selected-med-name" id="selected_name_${medicineCount}">No medicine selected</span>
                            <span class="selected-med-price" id="selected_price_${medicineCount}">₹0</span>
                            <span class="selected-med-code" id="selected_code_${medicineCount}">-</span>
                        </div>
                        <button type="button" class="change-med-btn" onclick="showMedicineGrid(${medicineCount})">Select</button>
                    </div>
                    
                    <div class="medicine-grid" id="grid_${medicineCount}" style="display: none;">
                        <div class="med-card-btn" onclick="selectMedicine(${medicineCount}, 'Paracetamol', 10, 'P')">
                            <span class="med-name">Paracetamol</span>
                            <span class="med-price">₹10</span>
                            <span class="med-code">Code: P</span>
                        </div>
                        <div class="med-card-btn" onclick="selectMedicine(${medicineCount}, 'Dolo', 10, 'D')">
                            <span class="med-name">Dolo</span>
                            <span class="med-price">₹10</span>
                            <span class="med-code">Code: D</span>
                        </div>
                        <div class="med-card-btn" onclick="selectMedicine(${medicineCount}, 'Aspirin', 8, 'A')">
                            <span class="med-name">Aspirin</span>
                            <span class="med-price">₹8</span>
                            <span class="med-code">Code: A</span>
                        </div>
                        <div class="med-card-btn" onclick="selectMedicine(${medicineCount}, 'Naproxen', 12, 'N')">
                            <span class="med-name">Naproxen</span>
                            <span class="med-price">₹12</span>
                            <span class="med-code">Code: N</span>
                        </div>
                        <div class="med-card-btn" onclick="selectMedicine(${medicineCount}, 'Metoprolol', 9, 'M')">
                            <span class="med-name">Metoprolol</span>
                            <span class="med-price">₹9</span>
                            <span class="med-code">Code: M</span>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label class="required">Dosage (mg)</label>
                                <input type="text" name="medicines[${medicineCount}][dosage]" required placeholder="e.g., 500" onchange="calculateAll()" onkeyup="calculateAll()">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label class="required">Frequency/day</label>
                                <input type="number" name="medicines[${medicineCount}][frequency]" required placeholder="Times per day" min="1" onchange="calculateAll()" onkeyup="calculateAll()">
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label class="required">Duration (days)</label>
                                <input type="number" name="medicines[${medicineCount}][duration]" required placeholder="Number of days" min="1" onchange="calculateAll()" onkeyup="calculateAll()">
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label class="required">Rate (₹)</label>
                                <input type="number" name="medicines[${medicineCount}][rate]" id="rate_${medicineCount}" required placeholder="Price per tablet" step="0.50" min="1" onchange="calculateAll()" onkeyup="calculateAll()" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="medicine-price" id="price_${medicineCount}">
                        <i class="fas fa-calculator"></i> Subtotal: ₹0.00
                    </div>
                    
                    <div class="code-preview" id="preview_${medicineCount}">
                        <i class="fas fa-info-circle"></i> Select medicine to see code
                    </div>
                `;
                
                container.appendChild(newCard);
                
                // Update medicine count display
                document.getElementById('medCount').textContent = `${medicineCount}/${maxMedicines}`;
                
                // Update remove buttons
                updateRemoveButtons();
            } else {
                alert('Maximum 5 medicines allowed!');
            }
        }
        
        // Function to remove medicine
        function removeMedicine(id) {
            if (medicineCount > 1) {
                const element = document.getElementById(`medicine_${id}`);
                if (element) {
                    element.remove();
                    medicineCount--;
                    document.getElementById('medicineCount').value = medicineCount;
                    
                    // Renumber remaining medicines
                    const cards = document.querySelectorAll('.medicine-card');
                    cards.forEach((card, index) => {
                        const newNumber = index + 1;
                        card.id = `medicine_${newNumber}`;
                        
                        // Update header
                        const header = card.querySelector('h3');
                        if (header) header.textContent = `Medicine #${newNumber}`;
                        
                        // Update remove button
                        const btn = card.querySelector('.remove-btn');
                        if (btn) btn.setAttribute('onclick', `removeMedicine(${newNumber})`);
                        
                        // Update all IDs and attributes
                        const updates = [
                            'med_name', 'med_code', 'selected_name', 'selected_price', 
                            'selected_code', 'rate', 'price', 'preview', 'display', 'grid'
                        ];
                        
                        updates.forEach(item => {
                            const element = card.querySelector(`[id*="${item}_${id}"]`);
                            if (element) {
                                element.id = `${item}_${newNumber}`;
                            }
                        });
                        
                        // Update onclick for grid buttons
                        const grid = card.querySelector('.medicine-grid');
                        if (grid) {
                            grid.id = `grid_${newNumber}`;
                            const medBtns = grid.querySelectorAll('.med-card-btn');
                            medBtns.forEach(btn => {
                                const onclick = btn.getAttribute('onclick');
                                if (onclick) {
                                    const newOnclick = onclick.replace(/\d+/, newNumber);
                                    btn.setAttribute('onclick', newOnclick);
                                }
                            });
                        }
                        
                        // Update change button
                        const changeBtn = card.querySelector('.change-med-btn');
                        if (changeBtn) {
                            changeBtn.setAttribute('onclick', `showMedicineGrid(${newNumber})`);
                        }
                        
                        // Update input names
                        const inputs = card.querySelectorAll('input:not([type="hidden"])');
                        inputs.forEach(input => {
                            const name = input.getAttribute('name');
                            if (name) {
                                const newName = name.replace(/\[\d+\]/, `[${newNumber}]`);
                                input.setAttribute('name', newName);
                            }
                        });
                    });
                    
                    // Update displays
                    document.getElementById('medCount').textContent = `${medicineCount}/${maxMedicines}`;
                    updateRemoveButtons();
                    calculateAll();
                }
            }
        }
        
        // Update remove buttons state
        function updateRemoveButtons() {
            const removeButtons = document.querySelectorAll('.remove-btn');
            removeButtons.forEach(btn => {
                btn.disabled = (medicineCount === 1);
            });
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateRemoveButtons();
            calculateAll();
        });
    </script>
</body>
</html>