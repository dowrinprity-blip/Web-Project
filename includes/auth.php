<?php
require_once __DIR__ . '/config.php';

// ==================== SESSION SECURITY ====================
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session parameters
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', 1);
    ini_set('session.use_only_cookies', 1);
    
    session_start();
}

// Regenerate session ID periodically to prevent fixation
if (!isset($_SESSION['last_regeneration'])) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 1800) { // Regenerate every 30 minutes
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// ==================== SECURITY HEADERS ====================
function setSecurityHeaders() {
    // Prevent XSS in older browsers
    header('X-XSS-Protection: 1; mode=block');
    
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Prevent clickjacking
    header('X-Frame-Options: SAMEORIGIN');
    
    // Referrer policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Content Security Policy (CSP) - Adjust as needed
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com;");
}

// Apply security headers on every page
setSecurityHeaders();

// ==================== CSRF PROTECTION ====================

/**
 * Generate CSRF token
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCsrfToken($token) {
    if (empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check POST CSRF token (enforce protection)
 */
function checkPostCsrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!verifyCsrfToken($token)) {
            // Log the attempt for security monitoring
            error_log("CSRF attack attempt from IP: " . $_SERVER['REMOTE_ADDR'] . " at " . date('Y-m-d H:i:s'));
            die('Security validation failed. Please refresh the page and try again.');
        }
    }
}

// ==================== INPUT SANITIZATION ====================

/**
 * Sanitize input to prevent XSS
 * Converts special characters to HTML entities
 */
function sanitize($val) {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}

/**
 * Strict sanitization - removes ALL HTML tags
 * Use this for data that should NEVER contain HTML
 */
function sanitizeStrict($val) {
    return htmlspecialchars(strip_tags(trim($val)), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize entire array recursively
 */
function sanitizeArray($data) {
    if (!is_array($data)) {
        return sanitize($data);
    }
    return array_map('sanitizeArray', $data);
}

/**
 * Escape string for database (use prepared statements instead)
 * This is a fallback only - always use prepared statements
 */
function escapeString($conn, $str) {
    return $conn->real_escape_string($str);
}

// ==================== VALIDATION FUNCTIONS ====================

/**
 * Validate email format
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate password strength
 * Requirements: min 8 chars, at least one uppercase, one lowercase, one number
 */
function validatePassword($password, $minLength = 8) {
    if (strlen($password) < $minLength) {
        return false;
    }
    // Enforce strong password requirements
    if (!preg_match('/[A-Z]/', $password)) {
        return false; // At least one uppercase
    }
    if (!preg_match('/[a-z]/', $password)) {
        return false; // At least one lowercase
    }
    if (!preg_match('/[0-9]/', $password)) {
        return false; // At least one number
    }
    return true;
}

/**
 * Validate username (alphanumeric only)
 */
function validateUsername($username, $minLength = 3, $maxLength = 50) {
    $username = trim($username);
    if (strlen($username) < $minLength || strlen($username) > $maxLength) {
        return false;
    }
    return preg_match('/^[a-zA-Z0-9_]+$/', $username);
}

/**
 * Validate phone number
 */
function validatePhone($phone) {
    return preg_match('/^[0-9+\-\s()]{10,20}$/', $phone);
}

// ==================== PASSWORD HASHING ====================

/**
 * Hash password securely
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Check if password needs rehash
 */
function needsRehash($hash) {
    return password_needs_rehash($hash, PASSWORD_DEFAULT, ['cost' => 12]);
}

// ==================== RATE LIMITING ====================

/**
 * Rate limiting to prevent brute force attacks
 */
function checkRateLimit($key, $limit = 5, $window = 300) {
    if (!isset($_SESSION['rate_limit'][$key])) {
        $_SESSION['rate_limit'][$key] = ['count' => 1, 'first_attempt' => time()];
        return true;
    }
    
    $data = $_SESSION['rate_limit'][$key];
    $now = time();
    
    if ($now - $data['first_attempt'] > $window) {
        // Reset if window has passed
        $_SESSION['rate_limit'][$key] = ['count' => 1, 'first_attempt' => $now];
        return true;
    }
    
    if ($data['count'] >= $limit) {
        return false; // Rate limit exceeded
    }
    
    $_SESSION['rate_limit'][$key]['count']++;
    return true;
}

// ==================== LOGIN ATTEMPT TRACKING ====================

/**
 * Track failed login attempts
 */
function trackFailedLogin($identifier) {
    $key = 'failed_logins_' . $identifier;
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 1, 'first_attempt' => time()];
    } else {
        $_SESSION[$key]['count']++;
    }
    return $_SESSION[$key]['count'];
}

/**
 * Check if account is locked
 */
function isAccountLocked($identifier, $maxAttempts = 5, $lockoutTime = 900) {
    $key = 'failed_logins_' . $identifier;
    if (!isset($_SESSION[$key])) {
        return false;
    }
    
    $data = $_SESSION[$key];
    if ($data['count'] >= $maxAttempts) {
        $now = time();
        if ($now - $data['first_attempt'] < $lockoutTime) {
            return true; // Account is locked
        } else {
            // Reset after lockout period
            unset($_SESSION[$key]);
            return false;
        }
    }
    return false;
}

/**
 * Reset failed login attempts
 */
function resetFailedLogins($identifier) {
    $key = 'failed_logins_' . $identifier;
    unset($_SESSION[$key]);
}

// ==================== SESSION MANAGEMENT ====================

/**
 * Regenerate session ID
 */
function regenerateSessionId() {
    session_regenerate_id(true);
}

/**
 * Set session timeout
 */
function setSessionTimeout($timeoutMinutes = 30) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > ($timeoutMinutes * 60))) {
        // Session expired
        session_unset();
        session_destroy();
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}

// ==================== ADMIN AUTH ====================

function isAdminLoggedIn() {
    if (!setSessionTimeout()) return false;
    return isset($_SESSION['crestfield_admin']) && $_SESSION['crestfield_admin'] === true;
}

function requireAdmin() {
    if (!isAdminLoggedIn()) {
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
}

function adminLogin($username, $password, $conn = null) {
    // Rate limiting
    $ip = $_SERVER['REMOTE_ADDR'];
    if (!checkRateLimit($ip, 5, 300)) {
        return false;
    }
    
    // For database authentication (preferred)
    if ($conn && $username !== 'admin') {
        $stmt = $conn->prepare("SELECT * FROM AdminAccounts WHERE Username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();
        
        if ($admin && verifyPassword($password, $admin['PasswordHash'])) {
            $_SESSION['crestfield_admin'] = true;
            $_SESSION['admin_user'] = $admin['Username'];
            $_SESSION['admin_id'] = $admin['AdminID'];
            $_SESSION['admin_role'] = $admin['Role'] ?? 'admin';
            $_SESSION['login_time'] = time();
            $_SESSION['last_activity'] = time();
            regenerateSessionId();
            return true;
        }
    }
    
    // Demo admin account (for testing only - remove in production)
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['crestfield_admin'] = true;
        $_SESSION['admin_user'] = $username;
        $_SESSION['admin_role'] = 'super_admin';
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        regenerateSessionId();
        return true;
    }
    
    return false;
}

function adminLogout() {
    $_SESSION = array();
    
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    session_destroy();
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// ==================== STAFF AUTH ====================

function isStaffLoggedIn() {
    if (!setSessionTimeout()) return false;
    return isset($_SESSION['crestfield_staff']) && $_SESSION['crestfield_staff'] === true;
}

function requireStaff() {
    if (!isStaffLoggedIn()) {
        header('Location: ' . BASE_URL . '/staff/login.php');
        exit;
    }
}

function getLoggedInStaff() {
    return $_SESSION['staff_data'] ?? null;
}

function staffLogin($username, $password, $conn) {
    // Rate limiting
    $ip = $_SERVER['REMOTE_ADDR'];
    if (!checkRateLimit($ip, 5, 300)) {
        return false;
    }
    
    if (!$conn) return false;
    
    $s = $conn->prepare("
        SELECT sa.*, st.Name, st.Photo, st.Bio AS StaffBio
        FROM StaffAccounts sa
        JOIN Staff st ON sa.StaffID = st.StaffID
        WHERE sa.Username = ? 
    ");
    $s->bind_param('s', $username);
    $s->execute();
    $account = $s->get_result()->fetch_assoc();

    if ($account && verifyPassword($password, $account['PasswordHash'])) {
        $_SESSION['crestfield_staff'] = true;
        $_SESSION['staff_data'] = [
            'AccountID' => $account['AccountID'],
            'StaffID'   => $account['StaffID'],
            'Username'  => $account['Username'],
            'Name'      => $account['Name'],
            'Bio'       => $account['Bio'],
            'StaffBio'  => $account['StaffBio'],
            'Photo'     => $account['Photo'],
            'PhotoPath' => $account['PhotoPath'],
            'Role'      => $account['Role'] ?? 'staff'
        ];
        $_SESSION['last_activity'] = time();
        regenerateSessionId();
        return true;
    }
    return false;
}

function staffLogout() {
    unset($_SESSION['crestfield_staff'], $_SESSION['staff_data']);
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// ==================== STUDENT AUTH ====================

function isStudentLoggedIn() {
    if (!setSessionTimeout()) return false;
    return isset($_SESSION['crestfield_student']) && $_SESSION['crestfield_student'] === true;
}

function requireStudent() {
    if (!isStudentLoggedIn()) {
        header('Location: ' . BASE_URL . '/student/login.php');
        exit;
    }
}

function getLoggedInStudent() {
    return $_SESSION['student_data'] ?? null;
}

function studentLogin($email, $password, $conn) {
    // Rate limiting
    $ip = $_SERVER['REMOTE_ADDR'];
    if (!checkRateLimit($ip, 5, 300)) {
        return false;
    }
    
    if (!$conn) return false;
    
    $s = $conn->prepare("SELECT * FROM StudentAccounts WHERE Email = ?");
    $s->bind_param('s', $email);
    $s->execute();
    $account = $s->get_result()->fetch_assoc();
    
    if ($account && verifyPassword($password, $account['PasswordHash'])) {
        $_SESSION['crestfield_student'] = true;
        $_SESSION['student_data'] = [
            'AccountID'   => $account['AccountID'],
            'FullName'    => $account['FullName'],
            'Email'       => $account['Email'],
            'StudentType' => $account['StudentType'],
            'CourseInfo'  => $account['CourseInfo'],
        ];
        $_SESSION['last_activity'] = time();
        regenerateSessionId();
        return true;
    }
    return false;
}

function studentLogout() {
    unset($_SESSION['crestfield_student'], $_SESSION['student_data']);
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// ==================== ROLE-BASED ACCESS CONTROL ====================

/**
 * Check if current user has specific role
 */
function hasRole($requiredRole) {
    $roles = [
        'super_admin' => ['super_admin'],
        'admin' => ['super_admin', 'admin'],
        'staff' => ['super_admin', 'admin', 'staff'],
        'student' => ['super_admin', 'admin', 'staff', 'student']
    ];
    
    $userRole = $_SESSION['admin_role'] ?? $_SESSION['staff_data']['Role'] ?? 'student';
    
    if (isset($roles[$requiredRole])) {
        return in_array($userRole, $roles[$requiredRole]);
    }
    
    return $userRole === $requiredRole;
}

/**
 * Require specific role
 */
function requireRole($role) {
    if (!hasRole($role)) {
        header('HTTP/1.0 403 Forbidden');
        die('Access denied. Insufficient permissions.');
    }
}

// ==================== SECURITY LOGGING ====================

/**
 * Log security events
 */
function logSecurityEvent($event, $details = '') {
    $logFile = __DIR__ . '/../logs/security.log';
    $logDir = dirname($logFile);
    
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user = $_SESSION['admin_user'] ?? $_SESSION['staff_data']['Username'] ?? 'guest';
    $logEntry = "[$timestamp] [$ip] [$user] [$event] $details" . PHP_EOL;
    
    error_log($logEntry, 3, $logFile);
}

// ==================== DATABASE SECURITY ====================

/**
 * Execute query with prepared statement (helper)
 */
function executeSecureQuery($conn, $sql, $types = '', $params = []) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }
    
    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $stmt->close();
        return $result;
    }
    
    $stmt->close();
    return false;
}

// ==================== AUTO-LOGOUT AFTER INACTIVITY ====================

// Check session timeout on every request
setSessionTimeout(30); // 30 minutes timeout

// ==================== END OF AUTH FILE ====================
?>