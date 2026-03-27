<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Generate CSRF token for login form
$csrf_token = generateCsrfToken();

// Set security headers
setSecurityHeaders();

// If already logged in → go to dashboard
if (isStaffLoggedIn()) {
    header('Location: ' . BASE_URL . '/staff/dashboard.php');
    exit;
}

$error = '';
$showLockoutMessage = false;

// Rate limiting key (use IP)
$ip = $_SERVER['REMOTE_ADDR'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check CSRF token
    checkPostCsrf();
    
    $u = sanitize(trim($_POST['username'] ?? ''));
    $p = $_POST['password'] ?? '';
    
    // Check rate limit
    if (!checkRateLimit($ip, 5, 300)) {
        $error = 'Too many login attempts. Please try again after 5 minutes.';
        logSecurityEvent("Staff rate limit exceeded", "IP: $ip, Username: $u");
        $showLockoutMessage = true;
    }
    // Check if account is locked
    elseif (isAccountLocked($ip, 5, 900)) {
        $error = 'Account temporarily locked due to multiple failed attempts. Please try again after 15 minutes.';
        logSecurityEvent("Staff locked account attempt", "IP: $ip, Username: $u");
        $showLockoutMessage = true;
    }
    else {
        // Attempt login
        if (staffLogin($u, $p, $conn)) {
            // Login successful - reset failed attempts
            resetFailedLogins($ip);
            
            // Log successful login
            logSecurityEvent("Staff login SUCCESS", "Username: $u, IP: $ip");
            
            // Redirect to dashboard
            header('Location: ' . BASE_URL . '/staff/dashboard.php');
            exit;
        } else {
            // Login failed - track the attempt
            $attempts = trackFailedLogin($ip);
            $remainingAttempts = 5 - $attempts;
            
            $error = 'Invalid username or password.';
            if ($remainingAttempts > 0) {
                $error .= " You have $remainingAttempts attempt(s) remaining.";
            }
            
            // Log failed login attempt
            logSecurityEvent("Staff login FAILED", "Username: $u, IP: $ip, Attempts: $attempts");
            
            // Check if account should be locked after this attempt
            if ($attempts >= 5) {
                $error = 'Account locked due to too many failed attempts. Please try again after 15 minutes.';
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
    <title>Staff Login — Crestfield University</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 60%, #1e3a6e 100%);
            min-height: 100vh; display:flex; align-items:center; justify-content:center; padding:2rem;
        }
        .login-card {
            background:#fff; border-radius:var(--radius-lg);
            padding:3rem 2.5rem; width:100%; max-width:420px;
            box-shadow:0 32px 80px rgba(0,0,0,.4);
            border-top:4px solid var(--gold);
        }
        .login-brand { text-align:center; margin-bottom:2rem; }
        .login-brand .crest { font-size:2.5rem; color:var(--gold); display:block; margin-bottom:.4rem; }
        .login-brand h1 { font-size:1.6rem; color:var(--navy); margin-bottom:.25rem; }
        .login-brand p { font-size:.82rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:.1em; }
        .form-group { margin-bottom:1.2rem; }
        .form-group label { display:block; font-size:.82rem; font-weight:600; color:var(--navy); margin-bottom:.4rem; text-transform:uppercase; letter-spacing:.04em; }
        .form-group input {
            width:100%; padding:.7rem 1rem;
            border:1.5px solid var(--border); border-radius:var(--radius);
            font-size:.95rem; font-family:'DM Sans',sans-serif;
            background:var(--cream); color:var(--text); outline:none; transition:border-color .2s;
        }
        .form-group input:focus { border-color:var(--gold); background:#fff; }
        .form-group input:disabled {
            background: #e9ecef;
            cursor: not-allowed;
        }
        .alert-error { 
            background:#fdecea; border:1px solid #f5b7b1; color:#922b21; 
            padding:.9rem 1.2rem; border-radius:var(--radius); font-size:.88rem; margin-bottom:1.2rem; 
        }
        .alert-warning {
            background:#fff3cd; border:1px solid #ffeeba; color:#856404;
            padding:.9rem 1.2rem; border-radius:var(--radius); font-size:.88rem; margin-bottom:1.2rem;
        }
        .submit-btn {
            width:100%; padding:12px; background:var(--gold); color:white;
            border:none; border-radius:6px; cursor:pointer; font-weight:bold;
            font-size:1rem; transition:0.3s;
        }
        .submit-btn:hover { background:var(--navy); }
        .submit-btn:disabled {
            opacity:0.6; cursor:not-allowed;
        }
        .links { display:flex; justify-content:space-between; margin-top:1.2rem; font-size:.8rem; color:var(--text-muted); }
        .links a { color:var(--navy); text-decoration:none; }
        .links a:hover { text-decoration:underline; }
        .security-note {
            margin-top:1rem; font-size:0.7rem; color:#999; text-align:center;
        }
    </style>
</head>
<body>
<main>
    <div class="login-card">
        <div class="login-brand">
            <span class="crest">⬡</span>
            <h1>Crestfield</h1>
            <p>Staff Portal Login</p>
        </div>

        <?php if ($error): ?>
            <div class="alert-<?= $showLockoutMessage ? 'warning' : 'error' ?>">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <!-- CSRF Protection Token -->
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            
            <div class="form-group">
                <label for="username">Username</label>
                <input id="username" type="text" name="username" required autofocus autocomplete="username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       <?= $showLockoutMessage ? 'disabled' : '' ?>>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" required autocomplete="current-password"
                       <?= $showLockoutMessage ? 'disabled' : '' ?>>
            </div>
            
            <button type="submit" class="submit-btn" <?= $showLockoutMessage ? 'disabled' : '' ?>>
                Sign In →
            </button>
        </form>

        <div class="links">
            <a href="<?= BASE_URL ?>/index.php">← Back to main site</a>
            <a href="<?= BASE_URL ?>/admin/login.php">Admin login</a>
        </div>
        
        <div class="security-note">
            🔒 Secure login with CSRF protection & rate limiting
        </div>
    </div>
</main>

<script>
// Add client-side rate limiting feedback
document.querySelector('form')?.addEventListener('submit', function(e) {
    var submitBtn = this.querySelector('button[type="submit"]');
    if (submitBtn && !submitBtn.disabled) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Please wait...';
        setTimeout(function() {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Sign In →';
        }, 2000);
    }
});
</script>
</body>
</html>