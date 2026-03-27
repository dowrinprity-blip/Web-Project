<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Generate CSRF token for login form
$csrf_token = generateCsrfToken();
setSecurityHeaders();

// If already logged in → go to dashboard
if (isStudentLoggedIn()) {
    header('Location: ' . BASE_URL . '/student/dashboard.php');
    exit;
}

$error = '';
$showLockoutMessage = false;
$ip = $_SERVER['REMOTE_ADDR'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkPostCsrf();
    
    $email = sanitize(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    
    // Rate limiting
    if (!checkRateLimit($ip, 5, 300)) {
        $error = 'Too many login attempts. Please try again after 5 minutes.';
        logSecurityEvent("Student rate limit exceeded", "IP: $ip, Email: $email");
        $showLockoutMessage = true;
    } elseif (isAccountLocked($ip, 5, 900)) {
        $error = 'Account temporarily locked. Please try again after 15 minutes.';
        logSecurityEvent("Student locked account attempt", "IP: $ip, Email: $email");
        $showLockoutMessage = true;
    } else {
        if (studentLogin($email, $password, $conn)) {
            resetFailedLogins($ip);
            logSecurityEvent("Student login SUCCESS", "Email: $email, IP: $ip");
            header('Location: ' . BASE_URL . '/student/dashboard.php');
            exit;
        } else {
            $attempts = trackFailedLogin($ip);
            $error = 'Invalid email or password.';
            if ($attempts < 5) {
                $error .= " You have " . (5 - $attempts) . " attempt(s) remaining.";
            }
            logSecurityEvent("Student login FAILED", "Email: $email, IP: $ip, Attempts: $attempts");
            
            if ($attempts >= 5) {
                $error = 'Account locked. Please try again after 15 minutes.';
                $showLockoutMessage = true;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login — Crestfield University</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0a2540 0%, #1e3a6e 100%);
            min-height: 100vh; display:flex; align-items:center; justify-content:center; padding:2rem;
            font-family: 'DM Sans', sans-serif;
        }
        .login-card {
            background:#fff; border-radius:16px; padding:3rem 2.5rem; width:100%; max-width:420px;
            box-shadow:0 32px 80px rgba(0,0,0,.4); border-top:4px solid #c5a028;
        }
        .login-brand { text-align:center; margin-bottom:2rem; }
        .login-brand .crest { font-size:2.5rem; color:#c5a028; display:block; margin-bottom:.4rem; }
        .login-brand h1 { font-size:1.6rem; color:#0a2540; margin-bottom:.25rem; }
        .login-brand p { font-size:.82rem; color:#6c757d; text-transform:uppercase; letter-spacing:.1em; }
        .form-group { margin-bottom:1.2rem; }
        .form-group label { display:block; font-size:.82rem; font-weight:600; color:#0a2540; margin-bottom:.4rem; }
        .form-group input {
            width:100%; padding:.7rem 1rem; border:1.5px solid #ddd; border-radius:6px;
            font-size:.95rem; font-family:'DM Sans',sans-serif;
        }
        .form-group input:focus { outline:none; border-color:#c5a028; }
        .alert-error { background:#fdecea; border:1px solid #f5b7b1; color:#922b21; padding:.9rem 1.2rem; border-radius:6px; margin-bottom:1.2rem; }
        .alert-warning { background:#fff3cd; border:1px solid #ffeeba; color:#856404; padding:.9rem 1.2rem; border-radius:6px; margin-bottom:1.2rem; }
        .submit-btn {
            width:100%; padding:12px; background:#c5a028; color:white; border:none;
            border-radius:6px; cursor:pointer; font-weight:bold; font-size:1rem;
        }
        .submit-btn:hover { background:#0a2540; }
        .submit-btn:disabled { opacity:0.6; cursor:not-allowed; }
        .links { margin-top:1.2rem; text-align:center; font-size:.8rem; }
        .links a { color:#0a2540; text-decoration:none; }
        .security-note { margin-top:1rem; font-size:0.7rem; color:#999; text-align:center; }
    </style>
</head>
<body>
<main>
    <div class="login-card">
        <div class="login-brand">
            <span class="crest">⬡</span>
            <h1>Crestfield</h1>
            <p>Student Portal Login</p>
        </div>

        <?php if ($error): ?>
            <div class="alert-<?= $showLockoutMessage ? 'warning' : 'error' ?>">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required autofocus placeholder="student@example.com">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="submit-btn" <?= $showLockoutMessage ? 'disabled' : '' ?>>Login →</button>
        </form>
        <div class="links">
            <a href="<?= BASE_URL ?>/index.php">← Back to Home</a> | 
            <a href="<?= BASE_URL ?>/register_interest.php">Register Interest</a>
        </div>
        <div class="security-note">🔒 Secure login with CSRF protection & rate limiting</div>
    </div>
</main>
</body>
</html>