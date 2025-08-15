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

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userData = [
        'username' => sanitizeInput($_POST['username'] ?? ''),
        'email' => sanitizeInput($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'first_name' => sanitizeInput($_POST['first_name'] ?? ''),
        'last_name' => sanitizeInput($_POST['last_name'] ?? ''),
        'phone' => sanitizeInput($_POST['phone'] ?? ''),
        'user_type' => sanitizeInput($_POST['user_type'] ?? 'member')
    ];
    
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Basic validation
    if (empty($userData['username']) || empty($userData['email']) || empty($userData['password']) || 
        empty($userData['first_name']) || empty($userData['last_name'])) {
        $error = 'Please fill in all required fields';
    } elseif ($userData['password'] !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        // Attempt registration
        $result = registerUser($pdo, $userData);
        
        if ($result['success']) {
            $success = 'Registration successful! You can now log in.';
            
            // Auto-login the user
            $loginResult = authenticateUser($pdo, $userData['username'], $userData['password']);
            if ($loginResult['success']) {
                startUserSession($loginResult['user']);
                echo "<script>
                    setTimeout(function() {
                        window.location.href = '../index.php';
                    }, 2000);
                </script>";
            }
        } else {
            $error = $result['message'];
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
    <title>Register - FitConnect</title>
    
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
            max-width: 500px;
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
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-md);
        }
        
        .form-label {
            display: block;
            margin-bottom: var(--space-sm);
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .form-input, .form-select {
            width: 100%;
            padding: var(--space-md);
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-input:focus, .form-select:focus {
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
        
        .password-requirements {
            background: var(--bg-secondary);
            padding: var(--space-md);
            border-radius: var(--radius-md);
            margin-top: var(--space-sm);
            font-size: 0.875rem;
        }
        
        .password-requirements ul {
            margin: var(--space-sm) 0 0 var(--space-lg);
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
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
                <h1>Join FitConnect</h1>
                <p>Create your account and start your fitness journey</p>
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
            
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name" class="form-label">First Name *</label>
                        <input type="text" id="first_name" name="first_name" class="form-input" 
                               value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" 
                               placeholder="First name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name" class="form-label">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" class="form-input" 
                               value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" 
                               placeholder="Last name" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="username" class="form-label">Username *</label>
                    <input type="text" id="username" name="username" class="form-input" 
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" 
                           placeholder="Choose a username" required>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email Address *</label>
                    <input type="email" id="email" name="email" class="form-input" 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                           placeholder="your@email.com" required>
                </div>
                
                <div class="form-group">
                    <label for="phone" class="form-label">Phone Number</label>
                    <input type="tel" id="phone" name="phone" class="form-input" 
                           value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" 
                           placeholder="(519) 555-0000">
                </div>
                
                <div class="form-group">
                    <label for="user_type" class="form-label">Account Type</label>
                    <select id="user_type" name="user_type" class="form-select">
                        <option value="member" <?= ($_POST['user_type'] ?? 'member') === 'member' ? 'selected' : '' ?>>
                            Member - Book fitness classes and trainers
                        </option>
                        <option value="trainer" <?= ($_POST['user_type'] ?? '') === 'trainer' ? 'selected' : '' ?>>
                            Trainer - Offer fitness services
                        </option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password *</label>
                    <input type="password" id="password" name="password" class="form-input" 
                           placeholder="Create a strong password" required>
                    <div class="password-requirements">
                        <strong>Password Requirements:</strong>
                        <ul>
                            <li>At least 8 characters long</li>
                            <li>One uppercase letter</li>
                            <li>One lowercase letter</li>
                            <li>One number</li>
                        </ul>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" 
                           placeholder="Confirm your password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-large" style="width: 100%;">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>
            
            <div style="text-align: center; margin-top: var(--space-xl);">
                <p>Already have an account? 
                    <a href="login.php" style="color: var(--primary-color); text-decoration: none; font-weight: 600;">
                        Sign in here
                    </a>
                </p>
                <a href="../index.php" style="color: var(--text-secondary); text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>
            </div>
        </div>
    </div>
    
    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Username validation
        document.getElementById('username').addEventListener('input', function() {
            const username = this.value;
            const regex = /^[a-zA-Z0-9_]{3,20}$/;
            
            if (!regex.test(username)) {
                this.setCustomValidity('Username must be 3-20 characters long and contain only letters, numbers, and underscores');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>