<?php
require_once 'includes/db.php';
require_once 'includes/auth.php'; // For security functions

// Set security headers
setSecurityHeaders();

// Generate CSRF token for form
$csrf_token = generateCsrfToken();

$prog_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Validate programme ID
if ($prog_id < 0) {
    $prog_id = 0;
}

// Pre-load programme if ID passed using SECURE prepared statement
$programme = null;
if ($prog_id > 0) {
    $stmt = $conn->prepare("SELECT ProgrammeID, ProgrammeName FROM Programmes WHERE ProgrammeID = ?");
    $stmt->bind_param('i', $prog_id);
    $stmt->execute();
    $programme = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Load all programmes for dropdown using SECURE prepared statement
$all_progs = [];
$stmt = $conn->prepare("
    SELECT p.ProgrammeID, p.ProgrammeName, l.LevelName
    FROM Programmes p 
    JOIN Levels l ON p.LevelID = l.LevelID
    ORDER BY l.LevelID, p.ProgrammeName
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $all_progs[] = $row;
}
$stmt->close();

$success = false;
$errors = [];

// Rate limiting - prevent spam
$ip = $_SERVER['REMOTE_ADDR'];
$rateLimitKey = 'register_interest_' . $ip;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    checkPostCsrf();
    
    // Check rate limit (max 5 submissions per hour)
    if (!checkRateLimit($rateLimitKey, 5, 3600)) {
        $errors[] = 'Too many registration attempts. Please try again later.';
        logSecurityEvent("Rate limit exceeded", "IP: $ip");
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pid = (int)($_POST['programme_id'] ?? 0);
        
        // ==================== VALIDATION ====================
        
        // Name validation
        if (empty($name)) {
            $errors[] = 'Please enter your full name.';
        } elseif (strlen($name) > 100) {
            $errors[] = 'Name must be 100 characters or fewer.';
        } elseif (!preg_match('/^[a-zA-Z\s\-\'\.]+$/', $name)) {
            $errors[] = 'Name can only contain letters, spaces, hyphens, apostrophes, and periods.';
        }
        
        // Email validation (using centralized function)
        if (empty($email)) {
            $errors[] = 'Please enter your email address.';
        } elseif (!validateEmail($email)) {
            $errors[] = 'Please enter a valid email address.';
        } elseif (strlen($email) > 255) {
            $errors[] = 'Email must be 255 characters or fewer.';
        }
        
        // Programme validation
        if ($pid === 0) {
            $errors[] = 'Please select a programme.';
        } else {
            // Verify programme exists
            $checkStmt = $conn->prepare("SELECT ProgrammeID FROM Programmes WHERE ProgrammeID = ?");
            $checkStmt->bind_param('i', $pid);
            $checkStmt->execute();
            if ($checkStmt->get_result()->num_rows === 0) {
                $errors[] = 'Selected programme does not exist.';
                logSecurityEvent("Invalid programme selection", "IP: $ip, Programme ID: $pid");
            }
            $checkStmt->close();
        }
        
        // Check duplicate with SECURE prepared statement
        if (empty($errors)) {
            $dup = $conn->prepare("SELECT InterestID FROM InterestedStudents WHERE ProgrammeID = ? AND Email = ?");
            $dup->bind_param('is', $pid, $email);
            $dup->execute();
            if ($dup->get_result()->num_rows > 0) {
                $errors[] = 'This email address has already been registered for that programme.';
            }
            $dup->close();
        }
        
        // Insert if no errors
        if (empty($errors)) {
            $ins = $conn->prepare("INSERT INTO InterestedStudents (ProgrammeID, StudentName, Email) VALUES (?, ?, ?)");
            $ins->bind_param('iss', $pid, $name, $email);
            
            if ($ins->execute()) {
                $success = true;
                $prog_id = $pid;
                
                // Log successful registration
                logSecurityEvent("Interest registered", "Programme ID: $pid, Email: $email, IP: $ip");
                
                // Reload programme name
                $s2 = $conn->prepare("SELECT ProgrammeID, ProgrammeName FROM Programmes WHERE ProgrammeID = ?");
                $s2->bind_param('i', $pid);
                $s2->execute();
                $programme = $s2->get_result()->fetch_assoc();
                $s2->close();
            } else {
                $errors[] = 'Something went wrong. Please try again later.';
                logSecurityEvent("Interest registration failed", "Database error: " . $conn->error);
            }
            $ins->close();
        }
    }
}

$page_title = 'Register Interest';
include 'includes/header.php';
?>

<div class="page-hero">
    <div class="page-hero-inner">
        <div class="breadcrumb">
            <a href="<?= htmlspecialchars(BASE_URL) ?>/index.php">Home</a> /
            <?php if ($programme && !$success): ?>
            <a href="<?= htmlspecialchars(BASE_URL) ?>/programme_detail.php?id=<?= (int)$programme['ProgrammeID'] ?>">
                <?= htmlspecialchars($programme['ProgrammeName']) ?>
            </a> /
            <?php endif; ?>
            Register Interest
        </div>
        <h1>Register Your Interest</h1>
        <p>Let us know you're interested and we'll keep you updated about applications and open days.</p>
    </div>
</div>

<style>
.register-wrap {
    max-width: 680px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.form-card {
    background: #fff;
    border-radius: var(--radius-lg);
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 1px solid var(--border);
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--navy);
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1.5px solid var(--border);
    border-radius: var(--radius);
    font-size: 0.95rem;
    font-family: 'DM Sans', sans-serif;
    transition: border-color 0.2s;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: var(--gold);
}

.form-note {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin-top: 0.4rem;
}

.alert {
    padding: 0.9rem 1.2rem;
    border-radius: var(--radius);
    margin-bottom: 1.2rem;
    font-size: 0.88rem;
}

.alert-error {
    background: #fdecea;
    border: 1px solid #f5b7b1;
    color: #922b21;
}

.submit-btn {
    width: 100%;
    padding: 0.9rem;
    background: var(--gold);
    color: #fff;
    border: none;
    border-radius: var(--radius);
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s;
}

.submit-btn:hover {
    background: var(--navy);
}
</style>

<div class="register-wrap">
    <?php if ($success): ?>
    <!-- SUCCESS STATE -->
    <div style="text-align:center;padding:3rem 0;">
        <div style="font-size:4rem;margin-bottom:1rem;">🎉</div>
        <h2 style="font-family:'Playfair Display',serif;color:var(--navy);margin-bottom:.75rem;">You're Registered!</h2>
        <p style="color:var(--text-muted);margin-bottom:2rem;">
            Thank you for your interest in <strong><?= htmlspecialchars($programme['ProgrammeName'] ?? '') ?></strong>.<br>
            We'll be in touch with the latest news and application details.
        </p>
        <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
            <a href="<?= htmlspecialchars(BASE_URL) ?>/my-interests.php" class="btn btn-gold" style="padding:0.7rem 1.5rem;background:var(--gold);color:#fff;text-decoration:none;border-radius:var(--radius);">
                View My Interests →
            </a>
            <a href="<?= htmlspecialchars(BASE_URL) ?>/programmes.php" class="btn btn-outline" style="padding:0.7rem 1.5rem;background:transparent;border:2px solid var(--navy);color:var(--navy);text-decoration:none;border-radius:var(--radius);">
                Browse More Programmes
            </a>
        </div>
    </div>

    <?php else: ?>
    <!-- FORM -->
    <div class="form-card">
        <h2 style="font-size:1.4rem;color:var(--navy);margin-bottom:.25rem;">Tell Us About Yourself</h2>
        <p style="color:var(--text-muted);font-size:.88rem;margin-bottom:1.75rem;">
            All fields are required. We will never share your details with third parties.
        </p>

        <?php foreach ($errors as $err): ?>
        <div class="alert alert-error">⚠️ <?= htmlspecialchars($err) ?></div>
        <?php endforeach; ?>

        <form method="POST" action="<?= htmlspecialchars(BASE_URL) ?>/register_interest.php<?= $prog_id > 0 ? '?id=' . (int)$prog_id : '' ?>" novalidate>
            <!-- CSRF Protection Token -->
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name"
                       placeholder="e.g. Jane Smith"
                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                       required maxlength="100"
                       pattern="[a-zA-Z\s\-\'\.]+"
                       title="Name can only contain letters, spaces, hyphens, apostrophes, and periods.">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email"
                       placeholder="e.g. jane@example.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       required maxlength="255">
                <p class="form-note">We'll use this to send you programme updates.</p>
            </div>

            <div class="form-group">
                <label for="programme_id">Programme of Interest</label>
                <select id="programme_id" name="programme_id" required>
                    <option value="0">— Select a Programme —</option>
                    <?php
                    $selected_pid = (int)($_POST['programme_id'] ?? $prog_id);
                    $optgroup = '';
                    foreach ($all_progs as $p):
                        if ($p['LevelName'] !== $optgroup) {
                            if ($optgroup) echo '</optgroup>';
                            echo '<optgroup label="' . htmlspecialchars($p['LevelName']) . '">';
                            $optgroup = $p['LevelName'];
                        }
                    ?>
                    <option value="<?= (int)$p['ProgrammeID'] ?>" <?= $selected_pid === (int)$p['ProgrammeID'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['ProgrammeName']) ?>
                    </option>
                    <?php endforeach; 
                    if ($optgroup) echo '</optgroup>'; 
                    ?>
                </select>
            </div>

            <button type="submit" class="submit-btn">Submit Registration →</button>
        </form>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>