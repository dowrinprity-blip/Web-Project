<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Only admins can access
requireAdmin();

// Generate CSRF token for forms
$csrf_token = generateCsrfToken();

// Set security headers
setSecurityHeaders();

$msg = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    checkPostCsrf();
    
    $pa       = $_POST['action']   ?? '';
    $staffID  = (int)($_POST['staff_id']  ?? 0);
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $accID    = (int)($_POST['account_id'] ?? 0);
    
    // Validate username
    if (!validateUsername($username)) {
        $msg = 'Username must be 3-50 characters and contain only letters, numbers, and underscores.';
        $msgType = 'error';
    }
    elseif ($pa === 'create') {
        if (!$staffID || !$username || !$password) {
            $msg = 'Staff member, username and password are all required.';
            $msgType = 'error';
        } 
        // Use centralized password validation
        elseif (!validatePassword($password, 8)) {
            $msg = 'Password must be at least 8 characters with at least one uppercase, one lowercase, and one number.';
            $msgType = 'error';
        } 
        else {
            // Check username not taken using prepared statement
            $chk = $conn->prepare("SELECT AccountID FROM StaffAccounts WHERE Username = ?");
            $chk->bind_param('s', $username);
            $chk->execute();
            $result = $chk->get_result();
            
            if ($result->num_rows > 0) {
                $msg = "Username '$username' is already taken.";
                $msgType = 'error';
            } else {
                // Use centralized hash function
                $hash = hashPassword($password);
                $stmt = $conn->prepare("INSERT INTO StaffAccounts (StaffID, Username, PasswordHash) VALUES (?, ?, ?)");
                $stmt->bind_param('iss', $staffID, $username, $hash);
                
                if ($stmt->execute()) {
                    $msg = "Account created for staff ID $staffID.";
                    $msgType = 'success';
                    logSecurityEvent("Staff account created", "Staff ID: $staffID, Username: $username, Admin: " . ($_SESSION['admin_user'] ?? 'unknown'));
                } else {
                    $msg = 'Error: ' . $conn->error;
                    $msgType = 'error';
                }
                $stmt->close();
            }
            $chk->close();
        }
        
    } elseif ($pa === 'reset_password') {
        // Use centralized password validation
        if (!validatePassword($password, 8)) {
            $msg = 'Password must be at least 8 characters with at least one uppercase, one lowercase, and one number.';
            $msgType = 'error';
        } else {
            // Use centralized hash function
            $hash = hashPassword($password);
            $stmt = $conn->prepare("UPDATE StaffAccounts SET PasswordHash = ? WHERE AccountID = ?");
            $stmt->bind_param('si', $hash, $accID);
            
            if ($stmt->execute()) {
                $msg = 'Password reset successfully.';
                $msgType = 'success';
                logSecurityEvent("Staff password reset", "Account ID: $accID, Admin: " . ($_SESSION['admin_user'] ?? 'unknown'));
            } else {
                $msg = 'Error: ' . $conn->error;
                $msgType = 'error';
            }
            $stmt->close();
        }
        
    } elseif ($pa === 'delete') {
        // Log before deletion
        logSecurityEvent("Staff account deletion attempted", "Account ID: $accID, Admin: " . ($_SESSION['admin_user'] ?? 'unknown'));
        
        $stmt = $conn->prepare("DELETE FROM StaffAccounts WHERE AccountID = ?");
        $stmt->bind_param('i', $accID);
        
        if ($stmt->execute()) {
            $msg = 'Staff account deleted.';
            $msgType = 'success';
            logSecurityEvent("Staff account deleted", "Account ID: $accID, Admin: " . ($_SESSION['admin_user'] ?? 'unknown'));
        } else {
            $msg = 'Error: ' . $conn->error;
            $msgType = 'error';
        }
        $stmt->close();
    }
}

// ==================== SECURE QUERIES ====================

// Staff without accounts - using prepared statement
$withoutAccounts = [];
$stmt = $conn->prepare("
    SELECT s.* FROM Staff s
    WHERE s.StaffID NOT IN (SELECT StaffID FROM StaffAccounts)
    ORDER BY s.Name
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $withoutAccounts[] = $row;
}
$stmt->close();

// Staff with accounts - using prepared statement
$withAccounts = [];
$stmt = $conn->prepare("
    SELECT sa.*, st.Name FROM StaffAccounts sa
    JOIN Staff st ON sa.StaffID = st.StaffID
    ORDER BY st.Name
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $withAccounts[] = $row;
}
$stmt->close();

require_once 'layout.php';
adminHead('Staff Accounts');
adminSidebar('staff_accounts');
adminTopbar('Staff Accounts');
?>

<?php if ($msg): ?>
    <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 340px;gap:1.5rem;align-items:start;">

    <!-- EXISTING ACCOUNTS -->
    <div>
        <h2 style="font-family:'Playfair Display',serif;font-size:1.1rem;color:var(--navy);margin-bottom:1rem;">
            Active Staff Accounts (<?= count($withAccounts) ?>)
        </h2>
        
        <?php if (empty($withAccounts)): ?>
            <div class="panel" style="text-align:center;padding:2rem;color:var(--text-muted);">
                No staff accounts created yet. Use the form to create one.
            </div>
        <?php else: ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Staff Name</th>
                            <th>Username</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($withAccounts as $a): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($a['Name']) ?></strong></td>
                                <td style="font-family:monospace;font-size:.85rem;">@<?= htmlspecialchars($a['Username']) ?></td>
                                <td style="font-size:.78rem;color:var(--text-muted);">
                                    <?= htmlspecialchars(date('d M Y', strtotime($a['CreatedAt']))) ?>
                                </td>
                                <td>
                                    <div style="display:flex;gap:.4rem;flex-wrap:wrap;">
                                        <!-- Reset password button -->
                                        <button class="btn btn-sm btn-edit"
                                            onclick="showReset(<?= (int)$a['AccountID'] ?>, '<?= htmlspecialchars(addslashes($a['Name'])) ?>')">
                                            Reset PW
                                        </button>
                                        
                                        <!-- Delete account form with CSRF -->
                                        <form method="POST" style="display:inline;" 
                                              onsubmit="return confirm('Delete account for <?= htmlspecialchars(addslashes($a['Name'])) ?>? This action cannot be undone.')">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="account_id" value="<?= (int)$a['AccountID'] ?>">
                                            <button type="submit" class="btn btn-sm btn-del">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- RESET PASSWORD FORM (hidden by default) -->
        <div id="reset-panel" class="panel" style="margin-top:1rem;display:none;background:#fff;border-radius:var(--radius-lg);padding:1.5rem;border:1px solid var(--border);">
            <h2 id="reset-title" style="font-size:1.1rem;color:var(--navy);margin-bottom:1rem;">Reset Password</h2>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="account_id" id="reset-account-id">
                
                <div class="form-group">
                    <label>New Password *</label>
                    <input type="password" name="password" id="reset-password" 
                           minlength="8" required 
                           style="width:100%;padding:.65rem;border:1.5px solid var(--border);border-radius:var(--radius);">
                    <small style="color:var(--text-muted);font-size:.75rem;">
                        Minimum 8 characters, at least one uppercase, one lowercase, and one number.
                    </small>
                </div>
                
                <div style="display:flex;gap:.75rem;">
                    <button type="submit" class="btn btn-sm btn-add">Reset Password</button>
                    <button type="button" onclick="document.getElementById('reset-panel').style.display='none'" 
                            class="btn btn-sm" style="background:var(--cream);color:var(--navy);border:1px solid var(--border);">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- CREATE ACCOUNT FORM -->
    <div class="admin-form-wrap">
        <h2>+ Create Staff Account</h2>
        
        <?php if (empty($withoutAccounts)): ?>
            <p style="color:var(--text-muted);font-size:.88rem;">All staff members already have accounts.</p>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <input type="hidden" name="action" value="create">
                
                <div class="form-group">
                    <label>Staff Member *</label>
                    <select name="staff_id" required>
                        <option value="">— Select Staff —</option>
                        <?php foreach ($withoutAccounts as $s): ?>
                            <option value="<?= (int)$s['StaffID'] ?>"><?= htmlspecialchars($s['Name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" name="username" required 
                           placeholder="e.g. dr.johnson" autocomplete="off"
                           pattern="[a-zA-Z0-9_]{3,50}"
                           title="Username must be 3-50 characters and contain only letters, numbers, and underscores">
                    <small style="color:var(--text-muted);font-size:.75rem;">3-50 characters, letters, numbers, underscores only.</small>
                </div>
                
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" required 
                           minlength="8" autocomplete="new-password"
                           pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).{8,}"
                           title="Minimum 8 characters, at least one uppercase, one lowercase, and one number">
                    <small style="color:var(--text-muted);font-size:.75rem;">
                        Minimum 8 characters, at least one uppercase, one lowercase, and one number.
                    </small>
                </div>
                
                <button type="submit" class="btn btn-sm btn-add">Create Account</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
function showReset(id, name) {
    document.getElementById('reset-title').textContent = 'Reset Password — ' + name;
    document.getElementById('reset-account-id').value = id;
    document.getElementById('reset-password').value = '';
    document.getElementById('reset-panel').style.display = 'block';
    document.getElementById('reset-panel').scrollIntoView({behavior: 'smooth'});
}
</script>

<?php adminFooter(); ?>