<?php
require_once 'config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $doctor_name = $_POST['doctor_name'];
    $doctor_id = $_POST['doctor_id'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM doctors WHERE doctor_id = ? AND doctor_name = ?");
    $stmt->execute([$doctor_id, $doctor_name]);
    $doctor = $stmt->fetch();
    
    if ($doctor && password_verify($password, $doctor['password'])) {
        $_SESSION['doctor_id'] = $doctor['doctor_id'];
        $_SESSION['doctor_name'] = $doctor['doctor_name'];
        header('Location: dashboard.php');
        exit();
    } else {
        $error = "Invalid doctor credentials!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PharmaBot - Doctor Login</title>
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
            <h2>PharmaBot</h2>
            <p>Doctor Login Portal</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="doctor_name">Doctor Name</label>
                <input type="text" class="form-control" id="doctor_name" name="doctor_name" required>
            </div>
            
            <div class="form-group">
                <label for="doctor_id">Doctor ID</label>
                <input type="text" class="form-control" id="doctor_id" name="doctor_id" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Secure Sign In</button>
        </form>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="register.php" style="color: var(--primary-blue);">New Doctor? Register Here</a>
        </div>
    </div>
</body>
</html>