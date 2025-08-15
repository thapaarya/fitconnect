<?php
/**
 * FitConnect Simulated Database Configuration
 * University of Windsor CS Project
 * 
 * This file simulates database operations using JSON files
 * No real database server required!
 */

// Define data directory
define('DATA_DIR', __DIR__ . '/data/');

// Ensure data directory exists
if (!file_exists(DATA_DIR)) {
    mkdir(DATA_DIR, 0755, true);
}

/**
 * Simulated Database Class
 */
class SimulatedDatabase {
    private $tables = [];
    private $autoIncrementCounters = [];
    private $lastInsertId = 0;
    
    public function __construct() {
        $this->initializeTables();
        $this->loadData();
    }
    
    /**
     * Initialize table structures
     */
    private function initializeTables() {
        $this->tables = [
            'users' => [],
            'services' => [],
            'service_options' => [],
            'bookings' => [],
            'reviews' => [],
            'site_settings' => []
        ];
        
        $this->autoIncrementCounters = [
            'users' => 1,
            'services' => 1,
            'service_options' => 1,
            'bookings' => 1,
            'reviews' => 1,
            'site_settings' => 1
        ];
    }
    
    /**
     * Load data from JSON files
     */
    private function loadData() {
        foreach ($this->tables as $table => $data) {
            $file = DATA_DIR . $table . '.json';
            if (file_exists($file)) {
                $content = file_get_contents($file);
                $this->tables[$table] = json_decode($content, true) ?: [];
            }
        }
        
        // Load auto increment counters
        $counterFile = DATA_DIR . 'counters.json';
        if (file_exists($counterFile)) {
            $content = file_get_contents($counterFile);
            $counters = json_decode($content, true);
            if ($counters) {
                $this->autoIncrementCounters = array_merge($this->autoIncrementCounters, $counters);
            }
        }
        
        // Initialize with sample data if tables are empty
        $this->initializeSampleData();
    }
    
    /**
     * Save data to JSON files
     */
    private function saveData() {
        foreach ($this->tables as $table => $data) {
            $file = DATA_DIR . $table . '.json';
            file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
        }
        
        // Save auto increment counters
        $counterFile = DATA_DIR . 'counters.json';
        file_put_contents($counterFile, json_encode($this->autoIncrementCounters, JSON_PRETTY_PRINT));
    }
    
    /**
     * Execute a simulated SQL query
     */
    public function query($sql) {
        return new SimulatedPDOStatement($this, $sql);
    }
    
    /**
     * Prepare a simulated statement
     */
    public function prepare($sql) {
        return new SimulatedPDOStatement($this, $sql);
    }
    
    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->lastInsertId;
    }
    
    /**
     * Execute SET timezone (dummy operation)
     */
    public function exec($sql) {
        // Ignore timezone and other SET commands
        return true;
    }
    
    /**
     * Internal method to execute queries
     */
    public function executeQuery($sql, $params = []) {
        $sql = trim($sql);
        $sqlUpper = strtoupper($sql);
        
        if (strpos($sqlUpper, 'SELECT') === 0) {
            return $this->executeSelect($sql, $params);
        } elseif (strpos($sqlUpper, 'INSERT') === 0) {
            return $this->executeInsert($sql, $params);
        } elseif (strpos($sqlUpper, 'UPDATE') === 0) {
            return $this->executeUpdate($sql, $params);
        } elseif (strpos($sqlUpper, 'DELETE') === 0) {
            return $this->executeDelete($sql, $params);
        }
        
        return [];
    }
    
    /**
     * Execute SELECT queries
     */
    private function executeSelect($sql, $params) {
        // Simple SELECT parsing
        if (strpos($sql, 'site_settings') !== false && strpos($sql, 'default_theme') !== false) {
            return [['setting_value' => 'energy']];
        }
        
        if (strpos($sql, 'FROM services') !== false) {
            $services = $this->tables['services'];
            $users = $this->tables['users'];
            $serviceOptions = $this->tables['service_options'];
            $reviews = $this->tables['reviews'];
            $bookings = $this->tables['bookings'];
            
            $result = [];
            foreach ($services as $service) {
                if ($service['status'] !== 'active') continue;
                
                // Find trainer
                $trainer = null;
                foreach ($users as $user) {
                    if ($user['id'] == $service['trainer_id']) {
                        $trainer = $user;
                        break;
                    }
                }
                
                // Calculate pricing
                $prices = [];
                foreach ($serviceOptions as $option) {
                    if ($option['service_id'] == $service['id']) {
                        $prices[] = $option['price'];
                    }
                }
                
                // Calculate ratings
                $ratings = [];
                foreach ($reviews as $review) {
                    if ($review['service_id'] == $service['id']) {
                        $ratings[] = $review['rating'];
                    }
                }
                
                $serviceData = $service;
                $serviceData['first_name'] = $trainer ? $trainer['first_name'] : null;
                $serviceData['last_name'] = $trainer ? $trainer['last_name'] : null;
                $serviceData['min_price'] = $prices ? min($prices) : 25;
                $serviceData['max_price'] = $prices ? max($prices) : 100;
                $serviceData['avg_rating'] = $ratings ? array_sum($ratings) / count($ratings) : 0;
                $serviceData['review_count'] = count($ratings);
                
                $result[] = $serviceData;
                
                // Limit for homepage
                if (strpos($sql, 'LIMIT 3') !== false && count($result) >= 3) {
                    break;
                }
            }
            
            return $result;
        }
        
        if (strpos($sql, 'FROM users') !== false) {
            $users = $this->tables['users'];
            $result = [];
            
            foreach ($users as $user) {
                if (strpos($sql, "user_type = 'trainer'") !== false && $user['user_type'] !== 'trainer') {
                    continue;
                }
                if (strpos($sql, "user_type = 'member'") !== false && $user['user_type'] !== 'member') {
                    continue;
                }
                if (strpos($sql, "status = 'active'") !== false && $user['status'] !== 'active') {
                    continue;
                }
                
                $userData = $user;
                
                // Add stats for trainers
                if ($user['user_type'] === 'trainer') {
                    $userData['services_count'] = 0;
                    $userData['total_bookings'] = 0;
                    $userData['avg_rating'] = 0;
                    $userData['review_count'] = 0;
                    
                    // Count services
                    foreach ($this->tables['services'] as $service) {
                        if ($service['trainer_id'] == $user['id'] && $service['status'] === 'active') {
                            $userData['services_count']++;
                        }
                    }
                    
                    // Count bookings and ratings
                    foreach ($this->tables['bookings'] as $booking) {
                        foreach ($this->tables['services'] as $service) {
                            if ($service['id'] == $booking['service_id'] && $service['trainer_id'] == $user['id']) {
                                if ($booking['status'] === 'completed') {
                                    $userData['total_bookings']++;
                                }
                            }
                        }
                    }
                }
                
                $result[] = $userData;
                
                if (strpos($sql, 'LIMIT 1') !== false) {
                    break;
                }
            }
            
            return $result;
        }
        
        if (strpos($sql, 'COUNT(*)') !== false) {
            if (strpos($sql, 'services') !== false) {
                return [['COUNT(*)' => count(array_filter($this->tables['services'], function($s) { return $s['status'] === 'active'; }))]];
            }
            if (strpos($sql, "user_type = 'trainer'") !== false) {
                return [['COUNT(*)' => count(array_filter($this->tables['users'], function($u) { return $u['user_type'] === 'trainer' && $u['status'] === 'active'; }))]];
            }
            if (strpos($sql, "user_type = 'member'") !== false) {
                return [['COUNT(*)' => count(array_filter($this->tables['users'], function($u) { return $u['user_type'] === 'member' && $u['status'] === 'active'; }))]];
            }
            if (strpos($sql, 'bookings') !== false) {
                return [['COUNT(*)' => count(array_filter($this->tables['bookings'], function($b) { return $b['status'] === 'completed'; }))]];
            }
        }
        
        if (strpos($sql, 'FROM reviews') !== false) {
            $reviews = $this->tables['reviews'];
            $result = [];
            
            foreach ($reviews as $review) {
                if ($review['rating'] < 4) continue;
                
                // Find user and service
                $user = null;
                foreach ($this->tables['users'] as $u) {
                    if ($u['id'] == $review['user_id']) {
                        $user = $u;
                        break;
                    }
                }
                
                $service = null;
                foreach ($this->tables['services'] as $s) {
                    if ($s['id'] == $review['service_id']) {
                        $service = $s;
                        break;
                    }
                }
                
                if ($user && $service) {
                    $reviewData = $review;
                    $reviewData['first_name'] = $user['first_name'];
                    $reviewData['last_name'] = $user['last_name'];
                    $reviewData['service_name'] = $service['name'];
                    $result[] = $reviewData;
                }
                
                if (count($result) >= 3) break;
            }
            
            return $result;
        }
        
        if (strpos($sql, 'DISTINCT category') !== false) {
            $categories = [];
            foreach ($this->tables['services'] as $service) {
                if ($service['status'] === 'active' && !in_array($service['category'], $categories)) {
                    $categories[] = $service['category'];
                }
            }
            return array_map(function($cat) { return ['category' => $cat]; }, $categories);
        }
        
        if (strpos($sql, 'DISTINCT specializations') !== false) {
            $specializations = [];
            foreach ($this->tables['users'] as $user) {
                if ($user['user_type'] === 'trainer' && $user['status'] === 'active' && $user['specializations']) {
                    $specializations[] = $user['specializations'];
                }
            }
            return array_map(function($spec) { return ['specializations' => $spec]; }, array_unique($specializations));
        }
        
        // Handle user authentication queries
        if (strpos($sql, 'WHERE email =') !== false || strpos($sql, 'WHERE username =') !== false) {
            $users = $this->tables['users'];
            foreach ($users as $user) {
                if (strpos($sql, 'email') !== false && $user['email'] === $params[0] && $user['status'] === 'active') {
                    return [$user];
                }
                if (strpos($sql, 'username') !== false && $user['username'] === $params[0] && $user['status'] === 'active') {
                    return [$user];
                }
            }
            return [];
        }
        
        return [];
    }
    
    /**
     * Execute INSERT queries
     */
    private function executeInsert($sql, $params) {
        if (strpos($sql, 'INTO users') !== false) {
            $user = [
                'id' => $this->autoIncrementCounters['users']++,
                'username' => $params[0],
                'email' => $params[1],
                'password_hash' => $params[2],
                'user_type' => $params[3],
                'first_name' => $params[4],
                'last_name' => $params[5],
                'phone' => $params[6] ?? null,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'profile_image' => null,
                'bio' => null,
                'specializations' => null,
                'hourly_rate' => null
            ];
            
            $this->tables['users'][] = $user;
            $this->lastInsertId = $user['id'];
            $this->saveData();
            return true;
        }
        
        return false;
    }
    
    /**
     * Execute UPDATE queries
     */
    private function executeUpdate($sql, $params) {
        if (strpos($sql, 'UPDATE users') !== false && strpos($sql, 'WHERE id =') !== false) {
            $userId = end($params);
            foreach ($this->tables['users'] as &$user) {
                if ($user['id'] == $userId) {
                    $user['updated_at'] = date('Y-m-d H:i:s');
                    $this->saveData();
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Execute DELETE queries
     */
    private function executeDelete($sql, $params) {
        // Implementation for DELETE operations
        return false;
    }
    
    /**
     * Initialize sample data (from SQL file)
     */
    private function initializeSampleData() {
        if (empty($this->tables['users'])) {
            $this->tables['users'] = [
                [
                    'id' => 1,
                    'username' => 'admin',
                    'email' => 'admin@fitconnect.ca',
                    'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
                    'user_type' => 'admin',
                    'first_name' => 'Sarah',
                    'last_name' => 'Johnson',
                    'phone' => '519-555-0001',
                    'status' => 'active',
                    'created_at' => '2025-01-01 00:00:00',
                    'updated_at' => '2025-01-01 00:00:00',
                    'profile_image' => null,
                    'bio' => 'Platform administrator with 10 years experience in fitness management.',
                    'specializations' => 'Administration, Business Management',
                    'hourly_rate' => null
                ],
                [
                    'id' => 2,
                    'username' => 'mike_trainer',
                    'email' => 'mike@fitconnect.ca',
                    'password_hash' => password_hash('trainer123', PASSWORD_DEFAULT),
                    'user_type' => 'trainer',
                    'first_name' => 'Mike',
                    'last_name' => 'Chen',
                    'phone' => '519-555-0002',
                    'status' => 'active',
                    'created_at' => '2025-01-02 00:00:00',
                    'updated_at' => '2025-01-02 00:00:00',
                    'profile_image' => null,
                    'bio' => 'Certified personal trainer specializing in strength training and HIIT workouts. 8 years of experience helping clients achieve their fitness goals.',
                    'specializations' => 'Strength Training, HIIT, Weight Loss',
                    'hourly_rate' => 75.00
                ],
                [
                    'id' => 3,
                    'username' => 'lisa_yoga',
                    'email' => 'lisa@fitconnect.ca',
                    'password_hash' => password_hash('trainer123', PASSWORD_DEFAULT),
                    'user_type' => 'trainer',
                    'first_name' => 'Lisa',
                    'last_name' => 'Rodriguez',
                    'phone' => '519-555-0003',
                    'status' => 'active',
                    'created_at' => '2025-01-03 00:00:00',
                    'updated_at' => '2025-01-03 00:00:00',
                    'profile_image' => null,
                    'bio' => 'Registered Yoga Teacher (RYT-500) with expertise in various yoga styles. Passionate about mindfulness and holistic wellness.',
                    'specializations' => 'Yoga, Meditation, Flexibility',
                    'hourly_rate' => 65.00
                ],
                [
                    'id' => 9,
                    'username' => 'john_member',
                    'email' => 'john@example.com',
                    'password_hash' => password_hash('member123', PASSWORD_DEFAULT),
                    'user_type' => 'member',
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'phone' => '519-555-0101',
                    'status' => 'active',
                    'created_at' => '2025-01-09 00:00:00',
                    'updated_at' => '2025-01-09 00:00:00',
                    'profile_image' => null,
                    'bio' => null,
                    'specializations' => null,
                    'hourly_rate' => null
                ]
            ];
            $this->autoIncrementCounters['users'] = 10;
        }
        
        if (empty($this->tables['services'])) {
            $this->tables['services'] = [
                [
                    'id' => 1,
                    'trainer_id' => 3,
                    'name' => 'Hatha Yoga Fundamentals',
                    'description' => 'Perfect for beginners, this gentle yoga class focuses on basic postures, breathing techniques, and relaxation.',
                    'category' => 'yoga',
                    'duration_minutes' => 60,
                    'max_participants' => 15,
                    'location' => 'Studio A',
                    'is_virtual' => false,
                    'status' => 'active',
                    'image_url' => 'images/hatha-yoga.jpg',
                    'video_url' => 'assets/videos/hatha-preview.mp4',
                    'created_at' => '2025-01-02 00:00:00'
                ],
                [
                    'id' => 2,
                    'trainer_id' => 2,
                    'name' => 'HIIT Bootcamp',
                    'description' => 'High-intensity interval training combining cardio and strength exercises.',
                    'category' => 'hiit',
                    'duration_minutes' => 45,
                    'max_participants' => 12,
                    'location' => 'Gym Floor',
                    'is_virtual' => false,
                    'status' => 'active',
                    'image_url' => 'images/hiit-bootcamp.jpg',
                    'video_url' => 'assets/videos/hiit-preview.mp4',
                    'created_at' => '2025-01-03 00:00:00'
                ],
                [
                    'id' => 3,
                    'trainer_id' => 3,
                    'name' => 'Virtual Yoga Sessions',
                    'description' => 'Join our live online yoga classes from the comfort of your home.',
                    'category' => 'yoga',
                    'duration_minutes' => 60,
                    'max_participants' => 25,
                    'location' => 'Online',
                    'is_virtual' => true,
                    'status' => 'active',
                    'image_url' => 'images/virtual-yoga.jpg',
                    'video_url' => 'assets/videos/virtual-yoga-preview.mp4',
                    'created_at' => '2025-01-02 00:00:00'
                ]
            ];
            $this->autoIncrementCounters['services'] = 4;
        }
        
        if (empty($this->tables['service_options'])) {
            $this->tables['service_options'] = [
                ['id' => 1, 'service_id' => 1, 'option_name' => 'Drop-in', 'price' => 18.00, 'description' => 'Single class pass', 'sessions_included' => 1],
                ['id' => 2, 'service_id' => 1, 'option_name' => '5-Class Pack', 'price' => 80.00, 'description' => 'Five classes', 'sessions_included' => 5],
                ['id' => 3, 'service_id' => 2, 'option_name' => 'Single Session', 'price' => 20.00, 'description' => 'One HIIT session', 'sessions_included' => 1],
                ['id' => 4, 'service_id' => 3, 'option_name' => 'Live Class', 'price' => 15.00, 'description' => 'Single virtual session', 'sessions_included' => 1]
            ];
            $this->autoIncrementCounters['service_options'] = 5;
        }
        
        if (empty($this->tables['reviews'])) {
            $this->tables['reviews'] = [
                [
                    'id' => 1,
                    'user_id' => 9,
                    'service_id' => 1,
                    'booking_id' => 1,
                    'rating' => 5,
                    'review_text' => 'Amazing introduction to yoga! Lisa is an excellent instructor.',
                    'created_at' => '2024-08-10 00:00:00'
                ],
                [
                    'id' => 2,
                    'user_id' => 9,
                    'service_id' => 2,
                    'booking_id' => 2,
                    'rating' => 4,
                    'review_text' => 'Great HIIT workout! Really challenging but worth it.',
                    'created_at' => '2024-08-12 00:00:00'
                ]
            ];
            $this->autoIncrementCounters['reviews'] = 3;
        }
        
        if (empty($this->tables['bookings'])) {
            $this->tables['bookings'] = [
                [
                    'id' => 1,
                    'user_id' => 9,
                    'service_id' => 1,
                    'option_id' => 1,
                    'booking_date' => '2024-08-10',
                    'booking_time' => '09:00:00',
                    'status' => 'completed',
                    'total_amount' => 18.00,
                    'payment_status' => 'paid',
                    'notes' => 'First yoga class',
                    'created_at' => '2024-08-09 00:00:00'
                ]
            ];
            $this->autoIncrementCounters['bookings'] = 2;
        }
        
        if (empty($this->tables['site_settings'])) {
            $this->tables['site_settings'] = [
                ['id' => 1, 'setting_key' => 'default_theme', 'setting_value' => 'energy', 'updated_by' => 1, 'updated_at' => '2025-01-01 00:00:00']
            ];
            $this->autoIncrementCounters['site_settings'] = 2;
        }
        
        $this->saveData();
    }
}

/**
 * Simulated PDO Statement Class
 */
class SimulatedPDOStatement {
    private $db;
    private $sql;
    private $results = [];
    private $currentIndex = 0;
    
    public function __construct($db, $sql) {
        $this->db = $db;
        $this->sql = $sql;
    }
    
    public function execute($params = []) {
        $this->results = $this->db->executeQuery($this->sql, $params);
        $this->currentIndex = 0;
        return true;
    }
    
    public function fetch($fetchStyle = null) {
        if ($this->currentIndex < count($this->results)) {
            return $this->results[$this->currentIndex++];
        }
        return false;
    }
    
    public function fetchAll($fetchStyle = null) {
        return $this->results;
    }
    
    public function fetchColumn($columnNumber = 0) {
        if (!empty($this->results)) {
            $firstRow = $this->results[0];
            if (is_array($firstRow)) {
                $values = array_values($firstRow);
                return $values[$columnNumber] ?? null;
            }
        }
        return false;
    }
}

// Create the simulated database instance
$pdo = new SimulatedDatabase();

// Maintain backward compatibility with existing helper functions
function getDatabase() {
    global $pdo;
    return $pdo;
}

function executeQuery($sql, $params = []) {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function getSingleRow($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetch();
}

function getMultipleRows($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll();
}

function getSingleValue($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchColumn();
}

// Define constants for development
if (!defined('DEVELOPMENT')) {
    define('DEVELOPMENT', true);
}
?>