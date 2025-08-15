<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth_functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ../user/dashboard.php');
    exit;
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = sanitizeInput($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $redirect = $_GET['redirect'] ?? '../index.php';
    
    if (empty($login) || empty($password)) {
        $error = 'Please enter both username/email and password';
    } else {
        // Check rate limiting
        $identifier = $_SERVER['REMOTE_ADDR'] . '_' . $login;
        if (!checkRateLimit($identifier)) {
            $error = 'Too many login attempts. Please try again later.';
        } else {
            // Attempt authentication
            $result = authenticateUser($pdo, $login, $password);
            
            if ($result['success']) {
                startUserSession($result['user']);
                $success = 'Login successful! Redirecting...';
                
                // JavaScript redirect after showing success message
                echo "<script>
                    setTimeout(function() {
                        window.location.href = '" . htmlspecialchars($redirect) . "';
                    }, 1500);
                </script>";
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Get current theme
$stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'default_theme'");
$stmt->execute();
$current_theme = $stmt->fetchColumn() ?: 'energy';

// Theme CSS
$theme_css_files = [
    'energy' => '../assets/css/theme-energy.css',
    'classic' => '../assets/css/theme-classic.css', 
    'dark' => '../assets/css/theme-dark.css'
];
$theme_css = $theme_css_files[$current_theme] ?? $theme_css_files['energy'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FitConnect</title>
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Theme CSS -->
    <link href="<?= htmlspecialchars($theme_css) ?>" rel="stylesheet">
    
    <style>
        <?php include '../assets/css/main.css'; ?>
        
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            padding: var(--space-lg);
        }
        
        .auth-card {
            background: var(--bg-primary);
            border-radius: var(--radius-xl);
            padding: var(--space-2xl);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: var(--space-xl);
        }
        
        .auth-logo {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: var(--space-md);
        }
        
        .form-group {
            margin-bottom: var(--space-lg);
        }
        
        .form-label {
            display: block;
            margin-bottom: var(--space-sm);
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .form-input {
            width: 100%;
            padding: var(--space-md);
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .alert {
            padding: var(--space-md);
            border-radius: var(--radius-md);
            margin-bottom: var(--space-lg);
        }
        
        .alert-error {
            background: #fee;
            color: #c53030;
            border: 1px solid #fed7d7;
        }
        
        .alert-success {
            background: #f0fff4;
            color: #22543d;
            border: 1px solid #c6f6d5;
        }
        
        .demo-credentials {
            background: var(--bg-secondary);
            padding: var(--space-md);
            border-radius: var(--radius-md);
            margin-bottom: var(--space-lg);
            font-size: 0.875rem;
        }
        
        .demo-credentials h4 {
            margin-bottom: var(--space-sm);
            color: var(--primary-color);
        }
        
        .demo-credentials p {
            margin: var(--space-xs) 0;
            font-family: monospace;
        }
    </style>
</head>
<body class="theme-<?= htmlspecialchars($current_theme) ?>">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <i class="fas fa-dumbbell"></i>
                </div>
                <h1>Welcome Back</h1>
                <p>Sign in to your FitConnect account</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <!-- Demo Credentials -->
            <div class="demo-credentials">
                <h4><i class="fas fa-info-circle"></i> Demo Accounts</h4>
                <p><strong>Admin:</strong> admin / admin123</p>
                <p><strong>Trainer:</strong> sarah_trainer / trainer123</p>
                <p><strong>Member:</strong> john_member / member123</p>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="login" class="form-label">Username or Email</label>
                    <input type="text" id="login" name="login" class="form-input" 
                           value="<?= htmlspecialchars($_POST['login'] ?? '') ?>" 
                           placeholder="Enter your username or email" required>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-input" 
                           placeholder="Enter your password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-large" style="width: 100%;">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>
            
            <div style="text-align: center; margin-top: var(--space-xl);">
                <p>Don't have an account? 
                    <a href="register.php" style="color: var(--primary-color); text-decoration: none; font-weight: 600;">
                        Sign up here
                    </a>
                </p>
                <a href="../index.php" style="color: var(--text-secondary); text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>
            </div>
        </div>
    </div>
</body>
</html>