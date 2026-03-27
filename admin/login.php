<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Generate CSRF token for login form
$csrf_token = generateCsrfToken();

// If already logged in → go to dashboard
if (isAdminLoggedIn()) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
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
        logSecurityEvent("Rate limit exceeded", "IP: $ip, Username: $u");
        $showLockoutMessage = true;
    }
    // Check if account locked
    elseif (isAccountLocked($ip, 5, 900)) {
        $error = 'Account temporarily locked. Please try again after 15 minutes.';
        logSecurityEvent("Locked account attempt", "IP: $ip, Username: $u");
        $showLockoutMessage = true;
    }
    else {
        // Attempt login
        if (adminLogin($u, $p, $conn)) {
            resetFailedLogins($ip);
            logSecurityEvent("Admin login SUCCESS", "Username: $u, IP: $ip");
            header('Location: ' . BASE_URL . '/admin/index.php');
            exit;
        } else {
            $attempts = trackFailedLogin($ip);
            $error = 'Invalid username or password.';
            if ($attempts < 5) {
                $error .= " You have " . (5 - $attempts) . " attempt(s) remaining.";
            }
            logSecurityEvent("Admin login FAILED", "Username: $u, IP: $ip, Attempts: $attempts");
            
            if ($attempts >= 5) {
                $error = 'Account locked due to too many failed attempts. Please try again after 15 minutes.';
                $showLockoutMessage = true;
            }
        }
    }
}

// Set security headers
setSecurityHeaders();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — Crestfield University</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">

    <style>
        body {
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 60%, #1e3a6e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .login-card {
            background: #fff;
            border-radius: 16px;
            padding: 3rem 2.5rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 32px 80px rgba(0,0,0,.4);
            border-top: 4px solid var(--gold);
        }

        .login-brand {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-brand .crest {
            font-size: 2.5rem;
            color: var(--gold);
            display: block;
            margin-bottom: .4rem;
        }

        .login-brand h1 {
            font-size: 1.6rem;
            color: var(--navy);
            margin-bottom: .25rem;
        }

        .login-brand p {
            font-size: .82rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: .1em;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--navy);
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--gold);
        }

        .submit-btn {
            width: 100%;
            padding: 12px;
            background: var(--gold);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 1rem;
            transition: 0.3s;
        }

        .submit-btn:hover {
            background: var(--navy);
        }

        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #ffeeba;
        }

        .hint {
            margin-top: 1.5rem;
            padding-top: 1.2rem;
            border-top: 1px solid var(--border);
            text-align: center;
            font-size: .78rem;
            color: var(--text-muted);
        }
        
        .hint strong {
            color: var(--gold);
        }
        
        .security-note {
            margin-top: 1rem;
            font-size: 0.7rem;
            color: #999;
            text-align: center;
        }
    </style>
</head>

<body>

<main>
    <div class="login-card">

        <div class="login-brand">
            <span class="crest">⬡</span>
            <h1>Crestfield</h1>
            <p>Administration Portal</p>
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
                <input id="username" type="text" name="username"
                       required autofocus
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       <?= $showLockoutMessage ? 'disabled' : '' ?>>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" required
                       <?= $showLockoutMessage ? 'disabled' : '' ?>>
            </div>

            <button type="submit" class="submit-btn" <?= $showLockoutMessage ? 'disabled' : '' ?>>
                Sign In →
            </button>

        </form>

        <div class="hint">
            Demo: <strong>admin</strong> / <strong>admin123</strong>
        </div>
        
        <div class="security-note">
            🔒 Secure login with CSRF protection & rate limiting
        </div>

    </div>
</main>

<script>
// Optional: Add client-side rate limiting feedback
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