<?php
/**
 * Authentication Helper Functions
 * FitConnect - University of Windsor CS Project
 * 
 * This file contains all authentication-related functions
 * Updated to work with simulated JSON-based database
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Hash a password securely
 * @param string $password
 * @return string
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify a password against its hash
 * @param string $password
 * @param string $hash
 * @return bool
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate a secure random token
 * @param int $length
 * @return string
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Validate email format
 * @param string $email
 * @return bool
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate password strength
 * @param string $password
 * @return array ['valid' => bool, 'errors' => array]
 */
function validatePassword($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Register a new user (Simulated Database Version)
 * @param mixed $pdo (SimulatedDatabase instance)
 * @param array $userData
 * @return array ['success' => bool, 'message' => string, 'user_id' => int|null]
 */
function registerUser($pdo, $userData) {
    try {
        // Validate required fields
        $required = ['username', 'email', 'password', 'first_name', 'last_name'];
        foreach ($required as $field) {
            if (empty($userData[$field])) {
                return ['success' => false, 'message' => "Field '{$field}' is required"];
            }
        }
        
        // Validate email
        if (!isValidEmail($userData['email'])) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }
        
        // Validate password
        $passwordValidation = validatePassword($userData['password']);
        if (!$passwordValidation['valid']) {
            return ['success' => false, 'message' => implode(', ', $passwordValidation['errors'])];
        }
        
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$userData['username']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Username already exists'];
        }
        
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$userData['email']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email already registered'];
        }
        
        // Hash password
        $hashedPassword = hashPassword($userData['password']);
        
        // Insert new user
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password_hash, user_type, first_name, last_name, phone, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW())
        ");
        
        $result = $stmt->execute([
            $userData['username'],
            $userData['email'],
            $hashedPassword,
            $userData['user_type'] ?? 'member',
            $userData['first_name'],
            $userData['last_name'],
            $userData['phone'] ?? null
        ]);
        
        if ($result) {
            $userId = $pdo->lastInsertId();
            return ['success' => true, 'message' => 'Registration successful', 'user_id' => $userId];
        } else {
            return ['success' => false, 'message' => 'Registration failed'];
        }
        
    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred'];
    }
}

/**
 * Authenticate user login (Simulated Database Version)
 * @param mixed $pdo (SimulatedDatabase instance)
 * @param string $login (username or email)
 * @param string $password
 * @return array ['success' => bool, 'message' => string, 'user' => array|null]
 */
function authenticateUser($pdo, $login, $password) {
    try {
        // Check if login is email or username
        $field = isValidEmail($login) ? 'email' : 'username';
        
        $stmt = $pdo->prepare("
            SELECT id, username, email, password_hash, user_type, first_name, last_name, 
                   phone, profile_image, status, created_at
            FROM users 
            WHERE {$field} = ? AND status = 'active'
        ");
        $stmt->execute([$login]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid credentials'];
        }
        
        if (!verifyPassword($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Invalid credentials'];
        }
        
        // Update last login (simulated)
        $stmt = $pdo->prepare("UPDATE users SET updated_at = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        // Remove password hash from user data
        unset($user['password_hash']);
        
        return ['success' => true, 'message' => 'Login successful', 'user' => $user];
        
    } catch (Exception $e) {
        error_log("Authentication error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Authentication error occurred'];
    }
}

/**
 * Start user session
 * @param array $user
 */
function startUserSession($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['user_type'] = $user['user_type'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['profile_image'] = $user['profile_image'];
    $_SESSION['login_time'] = time();
    
    // Regenerate session ID for security
    session_regenerate_id(true);
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user has specific role
 * @param string $role
 * @return bool
 */
function hasRole($role) {
    return isLoggedIn() && $_SESSION['user_type'] === $role;
}

/**
 * Check if user is admin
 * @return bool
 */
function isAdmin() {
    return hasRole('admin');
}

/**
 * Check if user is trainer
 * @return bool
 */
function isTrainer() {
    return hasRole('trainer');
}

/**
 * Get current user ID
 * @return int|null
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user data
 * @return array|null
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email'],
        'user_type' => $_SESSION['user_type'],
        'first_name' => $_SESSION['first_name'],
        'last_name' => $_SESSION['last_name'],
        'profile_image' => $_SESSION['profile_image']
    ];
}

/**
 * Logout user
 */
function logoutUser() {
    // Unset all session variables
    $_SESSION = array();
    
    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
}

/**
 * Redirect to login page if not authenticated
 * @param string $redirect_url
 */
function requireLogin($redirect_url = null) {
    if (!isLoggedIn()) {
        $redirect = $redirect_url ?: $_SERVER['REQUEST_URI'];
        $login_url = "/auth/login.php?redirect=" . urlencode($redirect);
        header("Location: $login_url");
        exit;
    }
}

/**
 * Redirect to appropriate page if not authorized
 * @param string $required_role
 * @param string $redirect_url
 */
function requireRole($required_role, $redirect_url = '/') {
    requireLogin();
    
    if (!hasRole($required_role)) {
        header("Location: $redirect_url");
        exit;
    }
}

/**
 * Generate CSRF token
 * @return string
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateToken();
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * @param string $token
 * @return bool
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize input data
 * @param mixed $data
 * @return mixed
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Rate limiting for login attempts
 * @param string $identifier (IP or username)
 * @return bool true if within limits, false if rate limited
 */
function checkRateLimit($identifier) {
    $max_attempts = 5;
    $time_window = 900; // 15 minutes
    
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }
    
    $now = time();
    $attempts = $_SESSION['login_attempts'][$identifier] ?? [];
    
    // Remove old attempts outside time window
    $attempts = array_filter($attempts, function($timestamp) use ($now, $time_window) {
        return ($now - $timestamp) < $time_window;
    });
    
    // Check if within rate limit
    if (count($attempts) >= $max_attempts) {
        return false;
    }
    
    // Record this attempt
    $attempts[] = $now;
    $_SESSION['login_attempts'][$identifier] = $attempts;
    
    return true;
}

/**
 * Get user's full name
 * @param array|null $user
 * @return string
 */
function getUserFullName($user = null) {
    if (!$user) {
        $user = getCurrentUser();
    }
    
    if (!$user) {
        return 'Guest';
    }
    
    return trim($user['first_name'] . ' ' . $user['last_name']);
}

/**
 * Get user's display name (first name or username)
 * @param array|null $user
 * @return string
 */
function getUserDisplayName($user = null) {
    if (!$user) {
        $user = getCurrentUser();
    }
    
    if (!$user) {
        return 'Guest';
    }
    
    return $user['first_name'] ?: $user['username'];
}
?>