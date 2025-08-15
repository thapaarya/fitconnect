<?php
session_start();
require_once '../config/database.php';

// Handle form submission
$message = '';
$message_type = '';

if ($_POST && isset($_POST['submit_contact'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message_text = trim($_POST['message'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    // Basic validation
    if (empty($name) || empty($email) || empty($subject) || empty($message_text)) {
        $message = 'Please fill in all required fields.';
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $message_type = 'error';
    } else {
        try {
            // Insert contact message into database
            $stmt = $pdo->prepare("
                INSERT INTO contact_messages (name, email, phone, subject, message, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$name, $email, $phone, $subject, $message_text]);
            
            $message = 'Thank you for your message! We\'ll get back to you soon.';
            $message_type = 'success';
            
            // Clear form data on success
            $name = $email = $subject = $message_text = $phone = '';
            
        } catch (PDOException $e) {
            $message = 'Sorry, there was an error sending your message. Please try again.';
            $message_type = 'error';
        }
    }
}

// Get current theme from database
$stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'default_theme'");
$stmt->execute();
$current_theme = $stmt->fetchColumn() ?: 'energy';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Contact FitConnect - Get in touch with our fitness experts. Send us a message, book a consultation, or find our location and hours.">
    <meta name="keywords" content="contact fitness trainer, FitConnect support, fitness consultation, gym contact, personal trainer contact">
    <meta name="author" content="FitConnect Team">
    <meta name="robots" content="index, follow">
    
    <!-- Open Graph Tags -->
    <meta property="og:title" content="Contact FitConnect - Get in Touch">
    <meta property="og:description" content="Contact our fitness experts for consultations, support, or questions about our services">
    <meta property="og:image" content="../assets/images/fitconnect-contact-og.jpg">
    <meta property="og:url" content="https://myweb.cs.uwindsor.ca/~yourusername/fitconnect/pages/contact.php">
    <meta property="og:type" content="website">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    
    <title>Contact Us - FitConnect | Get in Touch with Fitness Experts</title>
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* CSS Custom Properties - Energy Theme */
        :root {
            --primary-color: #f97316;
            --primary-hover: #ea580c;
            --primary-light: #fed7aa;
            --secondary-color: #ef4444;
            --secondary-hover: #dc2626;
            --accent-color: #eab308;
            --success-color: #22c55e;
            --error-color: #ef4444;
            
            --text-primary: #1c1917;
            --text-secondary: #44403c;
            --text-tertiary: #78716c;
            --text-light: #a8a29e;
            --text-inverse: #ffffff;
            
            --bg-primary: #fefefe;
            --bg-secondary: #fef2f2;
            --bg-tertiary: #fed7d7;
            --bg-overlay: rgba(239, 68, 68, 0.5);
            
            --border-color: #fed7d7;
            --border-hover: #fca5a5;
            --border-focus: #f97316;
            
            --shadow-sm: 0 1px 3px 0 rgba(249, 115, 22, 0.2);
            --shadow-md: 0 4px 6px -1px rgba(249, 115, 22, 0.2);
            --shadow-lg: 0 10px 15px -3px rgba(249, 115, 22, 0.2);
            --shadow-xl: 0 20px 25px -5px rgba(249, 115, 22, 0.2);
            
            --gradient-primary: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            --gradient-hero: linear-gradient(135deg, #fff7ed 0%, #fed7d7 50%, #fecaca 100%);
            --gradient-fire: linear-gradient(45deg, #f97316, #ef4444, #eab308, #ef4444);
            
            --font-primary: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            --font-heading: 'Poppins', sans-serif;
            
            --space-xs: 0.25rem;
            --space-sm: 0.5rem;
            --space-md: 1rem;
            --space-lg: 1.5rem;
            --space-xl: 2rem;
            --space-2xl: 3rem;
            --space-3xl: 4rem;
            
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
            --radius-full: 9999px;
            
            --transition-fast: 0.15s ease;
            --transition-normal: 0.3s ease;
            --transition-slow: 0.5s ease;
        }

        /* Reset and Base Styles */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
            height: 100%;
        }

        body {
            font-family: var(--font-primary);
            font-size: 16px;
            line-height: 1.6;
            color: var(--text-primary);
            background-color: var(--bg-primary);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Typography */
        h1, h2, h3, h4, h5, h6 {
            font-family: var(--font-heading);
            font-weight: 600;
            line-height: 1.3;
            margin-bottom: var(--space-md);
        }

        h1 { font-size: 2.5rem; }
        h2 { font-size: 2rem; }
        h3 { font-size: 1.5rem; }
        h4 { font-size: 1.25rem; }

        p { margin-bottom: var(--space-md); }

        a {
            color: var(--primary-color);
            text-decoration: none;
            transition: color var(--transition-fast);
        }

        a:hover { color: var(--secondary-color); }

        img {
            max-width: 100%;
            height: auto;
            display: block;
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 var(--space-md);
        }

        /* Alert Messages */
        .alert {
            padding: var(--space-md) var(--space-lg);
            border-radius: var(--radius-md);
            margin-bottom: var(--space-lg);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: var(--space-sm);
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            border: 2px solid var(--success-color);
            color: var(--success-color);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 2px solid var(--error-color);
            color: var(--error-color);
        }
    </style>
</head>
<body class="theme-<?= htmlspecialchars($current_theme) ?>">
<style>
        /* Header Styles */
        .main-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(254, 254, 254, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 3px solid var(--primary-color);
            box-shadow: var(--shadow-lg);
            transition: all var(--transition-normal);
        }

        .navbar {
            padding: var(--space-md) 0;
        }

        .nav-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: var(--space-sm);
        }

        .brand-logo {
            height: 40px;
            width: 40px;
            border-radius: var(--radius-md);
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            box-shadow: 0 0 20px rgba(249, 115, 22, 0.3);
        }

        .brand-text {
            font-family: var(--font-heading);
            font-size: 1.5rem;
            font-weight: 700;
            background: var(--gradient-fire);
            background-size: 300% 300%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: fire-gradient 3s ease-in-out infinite;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        @keyframes fire-gradient {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .mobile-toggle {
            display: none;
            flex-direction: column;
            background: none;
            border: none;
            cursor: pointer;
            padding: var(--space-sm);
            gap: 4px;
        }

        .hamburger-line {
            width: 25px;
            height: 3px;
            background-color: var(--text-primary);
            transition: all var(--transition-fast);
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: var(--space-xl);
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: var(--space-lg);
            margin: 0;
            padding: 0;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: var(--space-xs);
            padding: var(--space-sm) var(--space-md);
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 600;
            border-radius: var(--radius-md);
            transition: all var(--transition-fast);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 3px;
            background: var(--gradient-primary);
            transition: width 0.3s ease;
        }

        .nav-link:hover::after,
        .nav-link.active::after {
            width: 100%;
        }

        .nav-link:hover,
        .nav-link.active {
            background: linear-gradient(135deg, rgba(249, 115, 22, 0.1), rgba(239, 68, 68, 0.1));
            color: var(--primary-color);
            transform: translateY(-2px);
            text-shadow: 0 0 10px rgba(249, 115, 22, 0.5);
        }

        .auth-buttons {
            display: flex;
            gap: var(--space-sm);
        }

        /* Button Styles */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: var(--space-sm) var(--space-lg);
            border: none;
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all var(--transition-fast);
            position: relative;
            overflow: hidden;
            min-width: 120px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: var(--text-inverse);
            box-shadow: var(--shadow-md);
        }

        .btn-primary:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: var(--shadow-lg), 0 0 30px rgba(249, 115, 22, 0.4);
            color: var(--text-inverse);
        }

        .btn-outline {
            background: transparent;
            border: 3px solid var(--primary-color);
            color: var(--primary-color);
            position: relative;
        }

        .btn-outline::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 100%;
            background: var(--gradient-primary);
            transition: width 0.3s ease;
            z-index: -1;
        }

        .btn-outline:hover::before {
            width: 100%;
        }

        .btn-outline:hover {
            color: var(--text-inverse);
            transform: translateY(-2px);
        }

        .btn-large {
            padding: var(--space-md) var(--space-2xl);
            font-size: 1.125rem;
            min-width: 160px;
        }

        .btn-small {
            padding: var(--space-xs) var(--space-md);
            font-size: 0.75rem;
            min-width: 80px;
        }

        /* Page Header */
        .page-header {
            background: var(--gradient-hero);
            padding: calc(80px + var(--space-3xl)) 0 var(--space-3xl);
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: conic-gradient(from 0deg, transparent, rgba(249, 115, 22, 0.1), transparent, rgba(239, 68, 68, 0.1), transparent);
            animation: rotate-energy 20s linear infinite;
            pointer-events: none;
        }

        @keyframes rotate-energy {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .page-header-content {
            text-align: center;
            position: relative;
            z-index: 2;
        }

        .page-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: var(--space-md);
            background: var(--gradient-fire);
            background-size: 300% 300%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: fire-gradient 3s ease-in-out infinite;
        }

        .page-subtitle {
            font-size: 1.25rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
        }

        .breadcrumb {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: var(--space-sm);
            margin-top: var(--space-lg);
            font-size: 0.875rem;
            color: var(--text-tertiary);
        }

        .breadcrumb a {
            color: var(--primary-color);
            font-weight: 500;
        }

        .breadcrumb i {
            color: var(--text-light);
        }
    </style>

    <!-- Header Navigation -->
    <header class="main-header">
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-brand">
                    <div class="brand-logo">
                        <i class="fas fa-dumbbell"></i>
                    </div>
                    <span class="brand-text">FitConnect</span>
                </div>

                <button class="mobile-toggle" aria-label="Toggle navigation menu">
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                </button>

                <div class="nav-menu">
                    <ul class="nav-links">
                        <li class="nav-item">
                            <a href="../index.php" class="nav-link">
                                <i class="fas fa-home"></i> Home
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="services.html" class="nav-link">
                                <i class="fas fa-dumbbell"></i> Services
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="trainers.html" class="nav-link">
                                <i class="fas fa-users"></i> Trainers
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="about.html" class="nav-link">
                                <i class="fas fa-info-circle"></i> About
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="contact.php" class="nav-link active">
                                <i class="fas fa-envelope"></i> Contact
                            </a>
                        </li>
                    </ul>

                    <div class="auth-buttons">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="../user/dashboard.php" class="btn btn-outline btn-small">
                                <i class="fas fa-user"></i> Dashboard
                            </a>
                            <a href="../auth/logout.php" class="btn btn-primary btn-small">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        <?php else: ?>
                            <a href="../auth/login.php" class="btn btn-outline btn-small">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                            <a href="../auth/register.php" class="btn btn-primary btn-small">
                                <i class="fas fa-user-plus"></i> Sign Up
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <div class="page-header-content">
                <h1 class="page-title">Contact Us</h1>
                <p class="page-subtitle">Get in touch with our fitness experts. We're here to help you achieve your wellness goals!</p>
                <nav class="breadcrumb">
                    <a href="../index.php">Home</a>
                    <i class="fas fa-chevron-right"></i>
                    <span>Contact</span>
                </nav>
            </div>
        </div>
    </section>

    <style>
        /* Contact Section */
        .contact-section {
            padding: var(--space-3xl) 0;
            background: linear-gradient(180deg, var(--bg-primary) 0%, var(--bg-secondary) 100%);
        }

        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-3xl);
            align-items: start;
        }

        /* Contact Info Cards */
        .contact-info {
            display: grid;
            gap: var(--space-lg);
        }

        .info-card {
            background: var(--bg-primary);
            padding: var(--space-xl);
            border-radius: var(--radius-lg);
            border: 2px solid var(--border-color);
            transition: all var(--transition-normal);
            position: relative;
            overflow: hidden;
        }

        .info-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--gradient-primary);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .info-card:hover::before {
            transform: scaleX(1);
        }

        .info-card:hover {
            border-color: var(--primary-color);
            box-shadow: var(--shadow-xl), 0 0 30px rgba(249, 115, 22, 0.4);
            transform: translateY(-8px);
        }

        .info-card-icon {
            width: 60px;
            height: 60px;
            background: var(--gradient-primary);
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: var(--space-lg);
            box-shadow: var(--shadow-md), 0 0 20px rgba(249, 115, 22, 0.3);
            animation: icon-float 3s ease-in-out infinite;
        }

        @keyframes icon-float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-5px); }
        }

        .info-card h3 {
            color: var(--text-primary);
            margin-bottom: var(--space-sm);
        }

        .info-card p {
            color: var(--text-secondary);
            margin-bottom: 0;
        }

        /* Contact Form */
        .contact-form {
            background: var(--bg-primary);
            padding: var(--space-2xl);
            border-radius: var(--radius-lg);
            border: 2px solid var(--border-color);
            box-shadow: var(--shadow-lg);
        }

        .form-group {
            margin-bottom: var(--space-lg);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-lg);
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: var(--space-sm);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: var(--space-md);
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 1rem;
            font-family: var(--font-primary);
            color: var(--text-primary);
            background: var(--bg-primary);
            transition: all var(--transition-fast);
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
            transform: translateY(-2px);
        }

        .form-textarea {
            resize: vertical;
            min-height: 120px;
        }

        .form-submit {
            width: 100%;
            padding: var(--space-md) var(--space-xl);
            background: var(--gradient-primary);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-fast);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-submit:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: var(--shadow-lg), 0 0 30px rgba(249, 115, 22, 0.4);
        }

        .required {
            color: var(--error-color);
        }
    </style>

    <main>
        <!-- Contact Section -->
        <section class="contact-section">
            <div class="container">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?= $message_type ?>">
                        <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <div class="contact-grid">
                    <!-- Contact Information -->
                    <div class="contact-info">
                        <div class="info-card">
                            <div class="info-card-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <h3>Visit Our Studio</h3>
                            <p>401 Sunset Avenue<br>
                            Windsor, ON N9B 3P4<br>
                            Canada</p>
                        </div>

                        <div class="info-card">
                            <div class="info-card-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <h3>Call Us</h3>
                            <p>Phone: (519) 555-FITC<br>
                            Emergency: (519) 555-9999<br>
                            Mon-Fri: 6AM - 10PM</p>
                        </div>

                        <div class="info-card">
                            <div class="info-card-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <h3>Email Us</h3>
                            <p>General: info@fitconnect.ca<br>
                            Support: support@fitconnect.ca<br>
                            Partnerships: partners@fitconnect.ca</p>
                        </div>

                        <div class="info-card">
                            <div class="info-card-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h3>Operating Hours</h3>
                            <p>Monday - Friday: 6:00 AM - 10:00 PM<br>
                            Saturday: 7:00 AM - 9:00 PM<br>
                            Sunday: 8:00 AM - 8:00 PM</p>
                        </div>
                    </div>

                    <!-- Contact Form -->
                    <div class="contact-form">
                        <h2 style="text-align: center; margin-bottom: var(--space-xl); background: var(--gradient-fire); background-size: 300% 300%; -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; animation: fire-gradient 3s ease-in-out infinite;">
                            Send Us a Message
                        </h2>

                        <form method="POST" action="">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="name" class="form-label">Full Name <span class="required">*</span></label>
                                    <input type="text" id="name" name="name" class="form-input" 
                                           value="<?= htmlspecialchars($name ?? '') ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="email" class="form-label">Email Address <span class="required">*</span></label>
                                    <input type="email" id="email" name="email" class="form-input" 
                                           value="<?= htmlspecialchars($email ?? '') ?>" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" id="phone" name="phone" class="form-input" 
                                           value="<?= htmlspecialchars($phone ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label for="subject" class="form-label">Subject <span class="required">*</span></label>
                                    <select id="subject" name="subject" class="form-select" required>
                                        <option value="">Choose a subject</option>
                                        <option value="General Inquiry" <?= (isset($subject) && $subject === 'General Inquiry') ? 'selected' : '' ?>>General Inquiry</option>
                                        <option value="Book a Session" <?= (isset($subject) && $subject === 'Book a Session') ? 'selected' : '' ?>>Book a Session</option>
                                        <option value="Personal Training" <?= (isset($subject) && $subject === 'Personal Training') ? 'selected' : '' ?>>Personal Training</option>
                                        <option value="Group Classes" <?= (isset($subject) && $subject === 'Group Classes') ? 'selected' : '' ?>>Group Classes</option>
                                        <option value="Membership" <?= (isset($subject) && $subject === 'Membership') ? 'selected' : '' ?>>Membership</option>
                                        <option value="Technical Support" <?= (isset($subject) && $subject === 'Technical Support') ? 'selected' : '' ?>>Technical Support</option>
                                        <option value="Partnership" <?= (isset($subject) && $subject === 'Partnership') ? 'selected' : '' ?>>Partnership</option>
                                        <option value="Other" <?= (isset($subject) && $subject === 'Other') ? 'selected' : '' ?>>Other</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="message" class="form-label">Message <span class="required">*</span></label>
                                <textarea id="message" name="message" class="form-textarea" 
                                          placeholder="Tell us about your fitness goals, questions, or how we can help you..." required><?= htmlspecialchars($message_text ?? '') ?></textarea>
                            </div>

                            <button type="submit" name="submit_contact" class="form-submit">
                                <i class="fas fa-paper-plane"></i>
                                Send Message
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <style>
        /* Footer */
        .main-footer {
            background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%);
            border-top: 3px solid var(--primary-color);
            padding: var(--space-2xl) 0 var(--space-lg);
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--space-xl);
            margin-bottom: var(--space-xl);
        }

        .footer-section h3 {
            color: var(--primary-color);
            margin-bottom: var(--space-md);
        }

        .footer-section ul {
            list-style: none;
            padding: 0;
        }

        .footer-section ul li {
            margin-bottom: var(--space-sm);
        }

        .footer-section ul li a {
            color: var(--text-secondary);
            transition: color var(--transition-fast);
        }

        .footer-section ul li a:hover {
            color: var(--primary-color);
        }

        .footer-logo {
            display: flex;
            align-items: center;
            gap: var(--space-sm);
            margin-bottom: var(--space-md);
        }

        .footer-brand {
            font-family: var(--font-heading);
            font-size: 1.25rem;
            font-weight: 700;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .footer-description {
            color: var(--text-secondary);
            margin-bottom: var(--space-lg);
        }

        .social-links {
            display: flex;
            gap: var(--space-md);
        }

        .social-links a {
            width: 40px;
            height: 40px;
            background: var(--gradient-primary);
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.125rem;
            transition: all var(--transition-fast);
        }

        .social-links a:hover {
            transform: translateY(-3px) scale(1.1);
            box-shadow: 0 0 20px rgba(249, 115, 22, 0.4);
        }

        .footer-bottom {
            text-align: center;
            padding-top: var(--space-lg);
            border-top: 1px solid var(--border-color);
            color: var(--text-secondary);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .mobile-toggle {
                display: flex;
            }
            
            .nav-menu {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background-color: var(--bg-primary);
                border-top: 1px solid var(--border-color);
                flex-direction: column;
                gap: 0;
                opacity: 0;
                visibility: hidden;
                transform: translateY(-10px);
                transition: all var(--transition-fast);
            }
            
            .nav-menu.active {
                opacity: 1;
                visibility: visible;
                transform: translateY(0);
            }
            
            .nav-links {
                flex-direction: column;
                width: 100%;
                gap: 0;
                padding: var(--space-md) 0;
            }
            
            .nav-link {
                padding: var(--space-md);
                border-radius: 0;
                width: 100%;
                justify-content: center;
            }

            .auth-buttons {
                padding: var(--space-md);
                border-top: 1px solid var(--border-color);
                justify-content: center;
                width: 100%;
            }

            .page-title {
                font-size: 2.5rem;
            }

            .contact-grid {
                grid-template-columns: 1fr;
                gap: var(--space-2xl);
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }

        @media (max-width: 480px) {
            .page-title {
                font-size: 2rem;
            }

            h1 { font-size: 2rem; }
            h2 { font-size: 1.5rem; }

            .contact-form {
                padding: var(--space-lg);
            }

            .info-card {
                padding: var(--space-lg);
            }
        }

        /* Animation for page load */
        .animate-in {
            animation: energy-slide-in 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
        }

        @keyframes energy-slide-in {
            from {
                opacity: 0;
                transform: translateY(50px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
    </style>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="footer-logo">
                        <div class="brand-logo">
                            <i class="fas fa-dumbbell"></i>
                        </div>
                        <span class="footer-brand">FitConnect</span>
                    </div>
                    <p class="footer-description">
                        Your premier fitness platform connecting you with expert trainers and comprehensive wellness services.
                    </p>
                    <div class="social-links">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="../index.php">Home</a></li>
                        <li><a href="services.html">Services</a></li>
                        <li><a href="trainers.html">Trainers</a></li>
                        <li><a href="about.html">About</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Support</h3>
                    <ul>
                        <li><a href="help/getting-started.html">Getting Started</a></li>
                        <li><a href="help/booking-guide.html">Booking Guide</a></li>
                        <li><a href="help/faq.html">FAQ</a></li>
                        <li><a href="privacy.html">Privacy Policy</a></li>
                        <li><a href="terms.html">Terms of Service</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Contact Info</h3>
                    <div style="color: var(--text-secondary);">
                        <p><i class="fas fa-phone"></i> 519-555-FITC</p>
                        <p><i class="fas fa-envelope"></i> info@fitconnect.ca</p>
                        <p><i class="fas fa-map-marker-alt"></i> Windsor, ON, Canada</p>
                        <p><i class="fas fa-clock"></i> Mon-Fri: 6AM-10PM</p>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2025 FitConnect. All rights reserved. | University of Windsor CS Project</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle
            const mobileToggle = document.querySelector('.mobile-toggle');
            const navMenu = document.querySelector('.nav-menu');
            
            if (mobileToggle && navMenu) {
                mobileToggle.addEventListener('click', function() {
                    navMenu.classList.toggle('active');
                    
                    // Animate hamburger lines
                    const lines = mobileToggle.querySelectorAll('.hamburger-line');
                    if (navMenu.classList.contains('active')) {
                        lines[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
                        lines[1].style.opacity = '0';
                        lines[2].style.transform = 'rotate(-45deg) translate(7px, -6px)';
                    } else {
                        lines[0].style.transform = 'none';
                        lines[1].style.opacity = '1';
                        lines[2].style.transform = 'none';
                    }
                });
            }

            // Form validation and enhancement
            const form = document.querySelector('form');
            const inputs = form.querySelectorAll('.form-input, .form-select, .form-textarea');
            
            // Add focus/blur animations to form inputs
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                
                input.addEventListener('blur', function() {
                    if (this.value === '') {
                        this.parentElement.classList.remove('focused');
                    }
                });
                
                // Add floating label effect
                if (input.value !== '') {
                    input.parentElement.classList.add('focused');
                }
            });

            // Form submission with loading state
            form.addEventListener('submit', function(e) {
                const submitBtn = form.querySelector('.form-submit');
                const originalText = submitBtn.innerHTML;
                
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
                submitBtn.disabled = true;
                
                // Re-enable button after a delay (in case of server issues)
                setTimeout(() => {
                    if (submitBtn.disabled) {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }
                }, 10000);
            });

            // Intersection Observer for animations
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-in');
                    }
                });
            }, observerOptions);

            // Observe elements for animation
            document.querySelectorAll('.info-card, .contact-form, .alert').forEach(el => {
                observer.observe(el);
            });

            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth' });
                    }
                });
            });

            // Header scroll effect
            let lastScrollTop = 0;
            const header = document.querySelector('.main-header');
            
            window.addEventListener('scroll', function() {
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                
                if (scrollTop > lastScrollTop && scrollTop > 100) {
                    // Scrolling down
                    header.style.transform = 'translateY(-100%)';
                } else {
                    // Scrolling up
                    header.style.transform = 'translateY(0)';
                }
                
                lastScrollTop = scrollTop;
            });

            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-20px)';
                    setTimeout(() => {
                        alert.remove();
                    }, 300);
                }, 5000);
            });

            // Add particle effect to page header
            const pageHeader = document.querySelector('.page-header');
            if (pageHeader) {
                for (let i = 0; i < 20; i++) {
                    const particle = document.createElement('div');
                    particle.style.position = 'absolute';
                    particle.style.width = '3px';
                    particle.style.height = '3px';
                    particle.style.background = 'radial-gradient(circle, #f97316, #ef4444)';
                    particle.style.borderRadius = '50%';
                    particle.style.opacity = '0.6';
                    particle.style.left = Math.random() * 100 + '%';
                    particle.style.animationName = 'particle-float';
                    particle.style.animationDuration = (Math.random() * 3 + 4) + 's';
                    particle.style.animationIterationCount = 'infinite';
                    particle.style.animationTimingFunction = 'ease-in-out';
                    particle.style.animationDelay = Math.random() * 3 + 's';
                    pageHeader.appendChild(particle);
                }
            }
        });

        // Add particle animation keyframes
        const style = document.createElement('style');
        style.textContent = `
            @keyframes particle-float {
                0%, 100% { 
                    transform: translateY(100vh) translateX(0px) scale(0);
                    opacity: 0;
                }
                10% {
                    opacity: 0.6;
                    transform: scale(1);
                }
                90% {
                    opacity: 0.6;
                    transform: scale(1);
                }
                100% { 
                    transform: translateY(-50px) translateX(50px) scale(0);
                    opacity: 0;
                }
            }
            
            .focused .form-label {
                color: var(--primary-color);
                transform: translateY(-2px);
            }
            
            .form-input:focus + .form-label,
            .form-select:focus + .form-label,
            .form-textarea:focus + .form-label {
                color: var(--primary-color);
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>