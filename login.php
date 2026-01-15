<?php
session_start();
require_once 'db_connect.php';

$error = '';

// If already logged in, redirect to dashboard
if (isset($_SESSION['doctor_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        // Check if doctor exists
        $stmt = $conn->prepare("SELECT * FROM doctors WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $doctor = $result->fetch_assoc();
            
            // Check password (both hashed and plain text for compatibility)
            $password_correct = false;
            
            // Try hashed password first
            if (password_verify($password, $doctor['password'])) {
                $password_correct = true;
            }
            // Try plain text (for old accounts)
            elseif ($password === $doctor['password']) {
                $password_correct = true;
            }
            
            if ($password_correct) {
                // Login successful
                $_SESSION['doctor_id'] = $doctor['id'];
                $_SESSION['doctor_name'] = $doctor['full_name'];
                $_SESSION['doctor_email'] = $doctor['email'];
                $_SESSION['doctor_username'] = $doctor['username'];
                
                // Redirect to dashboard
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Incorrect password!";
            }
        } else {
            $error = "No doctor found with username: " . htmlspecialchars($username);
        }
    } else {
        $error = "Please enter both username and password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Login - EHR System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 450px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h2 {
            color: #667eea;
            font-weight: bold;
            margin-top: 10px;
        }
        .login-icon {
            font-size: 4rem;
            color: #667eea;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            font-weight: bold;
            color: white;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #5568d3 0%, #6a4295 100%);
            color: white;
        }
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin-top: 20px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="bi bi-heart-pulse-fill login-icon"></i>
            <h2>EHR System</h2>
            <p class="text-muted mb-0">Doctor Login Portal</p>
        </div>

        <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="mb-3">
                <label for="username" class="form-label">
                    <i class="bi bi-person-fill"></i> Username
                </label>
                <input type="text" 
                       class="form-control form-control-lg" 
                       id="username" 
                       name="username" 
                       placeholder="Enter your username"
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                       required
                       autofocus>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">
                    <i class="bi bi-lock-fill"></i> Password
                </label>
                <input type="password" 
                       class="form-control form-control-lg" 
                       id="password" 
                       name="password" 
                       placeholder="Enter your password"
                       required>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="remember">
                <label class="form-check-label" for="remember">
                    Remember me
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-login w-100">
                <i class="bi bi-box-arrow-in-right"></i> Login to Dashboard
            </button>
        </form>

        <div class="text-center mt-4">
            <p class="text-muted mb-2">Don't have an account? <a href="register.php" class="fw-bold">Register here</a></p>
            <p class="text-muted mb-0"><a href="index.php" class="fw-bold">← Back to Home</a></p>
        </div>

        <hr class="my-4">

        <div class="info-box">
            <strong><i class="bi bi-info-circle-fill"></i> Your Login Details:</strong><br>
            <div class="mt-2">
                <strong>Username:</strong> <code>drjohn</code><br>
                <strong>Password:</strong> <code>Password123</code>
            </div>
            <small class="text-muted mt-2 d-block">Use the credentials from your registration</small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
