<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_functions.php';

// Require login
requireLogin();

$user = getCurrentUser();
$user_id = getCurrentUserId();

// Get current theme
$stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'default_theme'");
$stmt->execute();
$current_theme = $stmt->fetchColumn() ?: 'energy';

// Get user's booking statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_bookings,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_sessions,
        COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as upcoming_sessions,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_bookings,
        COALESCE(SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END), 0) as total_spent
    FROM bookings 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$booking_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get recent bookings
$stmt = $pdo->prepare("
    SELECT b.*, s.name as service_name, s.duration_minutes, s.category, s.image_url,
           u.first_name as trainer_first_name, u.last_name as trainer_last_name,
           so.option_name, so.price
    FROM bookings b
    JOIN services s ON b.service_id = s.id
    LEFT JOIN users u ON s.trainer_id = u.id
    LEFT JOIN service_options so ON b.option_id = so.id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get upcoming sessions
$stmt = $pdo->prepare("
    SELECT b.*, s.name as service_name, s.duration_minutes, s.location,
           u.first_name as trainer_first_name, u.last_name as trainer_last_name,
           so.option_name
    FROM bookings b
    JOIN services s ON b.service_id = s.id
    LEFT JOIN users u ON s.trainer_id = u.id
    LEFT JOIN service_options so ON b.option_id = so.id
    WHERE b.user_id = ? AND b.status = 'confirmed' AND b.booking_date >= CURDATE()
    ORDER BY b.booking_date ASC, b.booking_time ASC
    LIMIT 3
");
$stmt->execute([$user_id]);
$upcoming_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's progress data
$stmt = $pdo->prepare("
    SELECT metric_type, value, unit, recorded_date
    FROM user_progress 
    WHERE user_id = ? 
    ORDER BY recorded_date DESC
    LIMIT 10
");
$stmt->execute([$user_id]);
$progress_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get favorite services (most booked)
$stmt = $pdo->prepare("
    SELECT s.id, s.name, s.category, s.image_url, COUNT(b.id) as booking_count,
           AVG(r.rating) as avg_rating, u.first_name, u.last_name
    FROM services s
    JOIN bookings b ON s.id = b.service_id
    LEFT JOIN reviews r ON s.id = r.service_id
    LEFT JOIN users u ON s.trainer_id = u.id
    WHERE b.user_id = ?
    GROUP BY s.id
    ORDER BY booking_count DESC
    LIMIT 3
");
$stmt->execute([$user_id]);
$favorite_services = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <meta name="description" content="<?= htmlspecialchars(getUserDisplayName()) ?>'s FitConnect Dashboard - Track your fitness progress, manage bookings, and access your wellness journey.">
    
    <title>Dashboard - FitConnect | <?= htmlspecialchars(getUserDisplayName()) ?></title>
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Theme CSS -->
    <link href="<?= htmlspecialchars($theme_css) ?>" rel="stylesheet">
    
    <style>
        <?php include '../assets/css/main.css'; ?>
        
        .dashboard-page {
            background: var(--bg-primary);
            min-height: 100vh;
        }
        
        .dashboard-header {
            background: var(--gradient-hero);
            padding: calc(80px + var(--space-xl)) 0 var(--space-xl);
            position: relative;
            overflow: hidden;
        }
        
        .dashboard-header::before {
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
        
        .welcome-section {
            position: relative;
            z-index: 2;
        }
        
        .welcome-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: var(--space-lg);
        }
        
        .welcome-text h1 {
            font-size: 2.5rem;
            margin-bottom: var(--space-sm);
            background: var(--gradient-fire);
            background-size: 300% 300%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: fire-gradient 3s ease-in-out infinite;
        }
        
        .welcome-text p {
            color: var(--text-secondary);
            font-size: 1.125rem;
        }
        
        .quick-actions {
            display: flex;
            gap: var(--space-md);
        }
        
        .dashboard-content {
            padding: var(--space-3xl) 0;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: var(--space-xl);
            margin-bottom: var(--space-3xl);
        }
        
        .main-content {
            display: flex;
            flex-direction: column;
            gap: var(--space-xl);
        }
        
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: var(--space-xl);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--space-lg);
            margin-bottom: var(--space-xl);
        }
        
        .stat-card {
            background: var(--bg-primary);
            padding: var(--space-xl);
            border-radius: var(--radius-lg);
            border: 2px solid var(--border-color);
            position: relative;
            overflow: hidden;
            transition: all var(--transition-normal);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--space-md);
        }
        
        .stat-icon {
            width: 40px;
            height: 40px;
            background: var(--gradient-primary);
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: var(--space-xs);
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .card {
            background: var(--bg-primary);
            border-radius: var(--radius-lg);
            border: 2px solid var(--border-color);
            overflow: hidden;
            position: relative;
        }
        
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }
        
        .card-header {
            padding: var(--space-lg);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }
        
        .card-content {
            padding: var(--space-lg);
        }
        
        .booking-item {
            display: flex;
            gap: var(--space-md);
            padding: var(--space-md);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            margin-bottom: var(--space-md);
            transition: all var(--transition-fast);
        }
        
        .booking-item:hover {
            border-color: var(--primary-color);
            transform: translateX(4px);
        }
        
        .booking-image {
            width: 60px;
            height: 60px;
            border-radius: var(--radius-md);
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
        
        .booking-details {
            flex: 1;
        }
        
        .booking-name {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: var(--space-xs);
        }
        
        .booking-meta {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-bottom: var(--space-xs);
        }
        
        .booking-status {
            display: inline-block;
            padding: var(--space-xs) var(--space-sm);
            border-radius: var(--radius-full);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-confirmed {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-completed {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .upcoming-session {
            display: flex;
            gap: var(--space-md);
            padding: var(--space-md);
            background: var(--bg-secondary);
            border-radius: var(--radius-md);
            margin-bottom: var(--space-md);
        }
        
        .session-date {
            text-align: center;
            padding: var(--space-sm);
            background: var(--gradient-primary);
            color: white;
            border-radius: var(--radius-md);
            min-width: 60px;
        }
        
        .session-day {
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1;
        }
        
        .session-month {
            font-size: 0.75rem;
            text-transform: uppercase;
        }
        
        .session-info {
            flex: 1;
        }
        
        .session-name {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: var(--space-xs);
        }
        
        .session-time {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        
        .progress-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--space-sm) 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .progress-item:last-child {
            border-bottom: none;
        }
        
        .progress-metric {
            font-weight: 500;
            color: var(--text-primary);
        }
        
        .progress-value {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .empty-state {
            text-align: center;
            padding: var(--space-2xl);
            color: var(--text-secondary);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: var(--space-lg);
            color: var(--text-light);
        }
        
        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                order: -1;
            }
        }
        
        @media (max-width: 768px) {
            .welcome-content {
                flex-direction: column;
                text-align: center;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .quick-actions {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .welcome-text h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body class="theme-<?= htmlspecialchars($current_theme) ?>">
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
                            <a href="../pages/services.php" class="nav-link">
                                <i class="fas fa-dumbbell"></i> Services
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="../pages/trainers