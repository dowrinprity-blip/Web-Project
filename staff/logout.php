<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Log the logout if staff was logged in
if (isStaffLoggedIn()) {
    $staff = getLoggedInStaff();
    $username = $staff['Username'] ?? 'Unknown';
    $ip = $_SERVER['REMOTE_ADDR'];
    logSecurityEvent("Staff logout", "User: $username, IP: $ip");
}

// Only allow POST requests for security
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    checkPostCsrf();
    
    // Call the centralized logout function
    staffLogout();
    exit;
}

// For GET requests, show confirmation page (prevents accidental logout)
$csrf_token = generateCsrfToken();
setSecurityHeaders();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Logout - Crestfield Staff</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DM Sans', sans-serif;
            background: linear-gradient(135deg, #0a2540 0%, #1e3a6e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .logout-card {
            background: #fff;
            border-radius: 16px;
            padding: 2.5rem;
            max-width: 400px;
            width: 100%;
            text-align: center;
            box-shadow: 0 32px 80px rgba(0,0,0,.4);
            border-top: 4px solid #c5a028;
        }
        
        .logout-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        h2 {
            color: #0a2540;
            margin-bottom: 0.5rem;
            font-size: 1.5rem;
        }
        
        p {
            color: #666;
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }
        
        .button-group {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-logout {
            background: #c5a028;
            color: white;
        }
        
        .btn-logout:hover {
            background: #0a2540;
        }
        
        .btn-cancel {
            background: #e0e0e0;
            color: #333;
        }
        
        .btn-cancel:hover {
            background: #ccc;
        }
        
        .security-note {
            margin-top: 1.5rem;
            font-size: 0.7rem;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="logout-card">
        <div class="logout-icon">🔒</div>
        <h2>Confirm Logout</h2>
        <p>Are you sure you want to logout from the staff portal?</p>
        
        <div class="button-group">
            <form method="POST" style="display: inline;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <button type="submit" class="btn btn-logout">Yes, Logout</button>
            </form>
            <a href="<?= BASE_URL ?>/staff/dashboard.php" class="btn btn-cancel">Cancel</a>
        </div>
        
        <div class="security-note">
            🔒 Secure logout with CSRF protection
        </div>
    </div>
</body>
</html>