<?php
require_once 'config/database.php';

function generateDoctorID($pdo) {
    $prefix = 'DR';
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM doctors");
    $row = $stmt->fetch();
    $count = $row['count'] + 1;
    return $prefix . str_pad($count, 3, '0', STR_PAD_LEFT);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $doctor_name = $_POST['doctor_name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $doctor_id = generateDoctorID($pdo);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO doctors (doctor_id, doctor_name, email, password) VALUES (?, ?, ?, ?)");
        $stmt->execute([$doctor_id, $doctor_name, $email, $password]);
        $success = true;
    } catch(PDOException $e) {
        $error = "Registration failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PharmaBot - Doctor Registration</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-logo">
                <i class="fas fa-capsules"></i>
                <i class="fas fa-plus-circle" style="font-size: 1.5rem; position: relative; left: -15px;"></i>
            </div>
            <h2>Doctor Registration</h2>
            <p>Join PharmaBot Network</p>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <h3>Registration Successful!</h3>
                <p>Doctor Name: <?php echo htmlspecialchars($doctor_name); ?></p>
                <p>Doctor ID: <strong><?php echo $doctor_id; ?></strong></p>
                <p style="margin-top: 15px;"><a href="login.php" style="color: var(--primary-blue);">Click here to login</a></p>
            </div>
        <?php else: ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="doctor_name">Full Name</label>
                    <input type="text" class="form-control" id="doctor_name" name="doctor_name" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Register</button>
            </form>
            
            <div style="text-align: center; margin-top: 20px;">
                <a href="login.php" style="color: var(--primary-blue);">Already registered? Login</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>