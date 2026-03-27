<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Only admins can access
requireAdmin();

// CSRF token for all forms
$csrf_token = generateCsrfToken();

// Set security headers
setSecurityHeaders();

$msg = ''; 
$msgType = 'success';
$filter = $_GET['filter'] ?? 'all';

// Validate filter - only allow valid values
$allowedFilters = ['all', 'enrolled', 'interested'];
if (!in_array($filter, $allowedFilters)) {
    $filter = 'all';
}

// --- Handle POST actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkPostCsrf(); // centralized CSRF check

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name     = sanitize($_POST['fullname'] ?? '');
        $email    = sanitize($_POST['email'] ?? '');
        $type     = $_POST['student_type'] ?? 'enrolled';
        $course   = sanitize($_POST['course'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validate email format
        if (!validateEmail($email)) {
            $msg = 'Please enter a valid email address.'; 
            $msgType = 'error';
        }
        elseif (!$name || !$email || !$password) {
            $msg = 'Full Name, Email and Password are required.'; 
            $msgType = 'error';
        } 
        // Use stronger password validation
        elseif (!validatePassword($password, 8)) {
            $msg = 'Password must be at least 8 characters with at least one uppercase, one lowercase, and one number.'; 
            $msgType = 'error';
        } 
        // Validate student type
        elseif (!in_array($type, ['enrolled', 'interested'])) {
            $msg = 'Invalid student type.'; 
            $msgType = 'error';
        }
        else {
            // Check email uniqueness with prepared statement
            $chk = $conn->prepare("SELECT AccountID FROM StudentAccounts WHERE Email = ?");
            $chk->bind_param('s', $email); 
            $chk->execute();
            $result = $chk->get_result();
            
            if ($result && $result->num_rows > 0) {
                $msg = "Email '$email' is already used."; 
                $msgType = 'error';
                logSecurityEvent("Student account creation failed", "Email already exists: $email, Admin: " . ($_SESSION['admin_user'] ?? 'unknown'));
            } else {
                $hash = hashPassword($password);
                $stmt = $conn->prepare("INSERT INTO StudentAccounts (FullName, Email, StudentType, CourseInfo, PasswordHash, CreatedAt) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param('sssss', $name, $email, $type, $course, $hash);
                
                if ($stmt->execute()) {
                    $msg = "Student account created for '$name'."; 
                    $msgType = 'success';
                    logSecurityEvent("Student account created", "Name: $name, Email: $email, Type: $type, Admin: " . ($_SESSION['admin_user'] ?? 'unknown'));
                    // Redirect to clear POST data
                    header("Location: student_accounts.php?msg=" . urlencode($msg) . "&type=success&filter=$filter");
                    exit;
                } else {
                    $msg = 'Error: ' . $conn->error; 
                    $msgType = 'error';
                }
                $stmt->close();
            }
            $chk->close();
        }

    } elseif ($action === 'reset_password') {
        $accID = (int)($_POST['account_id'] ?? 0);
        $password = $_POST['password'] ?? '';
        
        // Use stronger password validation
        if (!validatePassword($password, 8)) { 
            $msg = 'Password must be at least 8 characters with at least one uppercase, one lowercase, and one number.'; 
            $msgType = 'error'; 
        } else {
            $hash = hashPassword($password);
            $stmt = $conn->prepare("UPDATE StudentAccounts SET PasswordHash = ? WHERE AccountID = ?");
            $stmt->bind_param('si', $hash, $accID); 
            
            if ($stmt->execute()) {
                $msg = 'Password reset successfully.'; 
                $msgType = 'success';
                logSecurityEvent("Student password reset", "Account ID: $accID, Admin: " . ($_SESSION['admin_user'] ?? 'unknown'));
            } else {
                $msg = 'Error: ' . $conn->error; 
                $msgType = 'error';
            }
            $stmt->close();
        }

    } elseif ($action === 'delete') {
        $accID = (int)($_POST['account_id'] ?? 0);
        
        // Get student info before deletion for logging
        $stmt = $conn->prepare("SELECT FullName, Email FROM StudentAccounts WHERE AccountID = ?");
        $stmt->bind_param('i', $accID);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();
        $stmt->close();
        
        $stmt = $conn->prepare("DELETE FROM StudentAccounts WHERE AccountID = ?");
        $stmt->bind_param('i', $accID); 
        
        if ($stmt->execute()) {
            $msg = 'Student account deleted.'; 
            $msgType = 'success';
            logSecurityEvent("Student account deleted", "Account ID: $accID, Name: " . ($student['FullName'] ?? 'Unknown') . ", Admin: " . ($_SESSION['admin_user'] ?? 'unknown'));
        } else {
            $msg = 'Error: ' . $conn->error; 
            $msgType = 'error';
        }
        $stmt->close();
    }
}

// Check for messages in URL (after redirect)
if (isset($_GET['msg'])) {
    $msg = $_GET['msg'];
    $msgType = $_GET['type'] ?? 'success';
}

// --- CSV export with SECURE QUERY ---
if (isset($_GET['export'])) {
    // Use prepared statement for CSV export
    $sql = "SELECT FullName, Email, StudentType, CourseInfo, CreatedAt FROM StudentAccounts";
    $params = [];
    $types = "";
    
    if ($filter === 'enrolled') {
        $sql .= " WHERE StudentType = ?";
        $params[] = 'enrolled';
        $types = "s";
    } elseif ($filter === 'interested') {
        $sql .= " WHERE StudentType = ?";
        $params[] = 'interested';
        $types = "s";
    }
    $sql .= " ORDER BY CreatedAt DESC";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="student_accounts_' . date('Ymd') . '.csv"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Full Name', 'Email', 'Type', 'Course', 'Joined']);
        foreach ($rows as $r) {
            fputcsv($out, $r);
        }
        fclose($out);
        
        logSecurityEvent("Student accounts exported", "Filter: $filter, Admin: " . ($_SESSION['admin_user'] ?? 'unknown'));
        exit;
    }
    $stmt->close();
}

// --- Fetch accounts with SECURE QUERY ---
$students = [];
$sql = "SELECT sa.*, COUNT(i.InterestID) AS InterestCount
        FROM StudentAccounts sa
        LEFT JOIN InterestedStudents i ON sa.Email = i.Email";
$params = [];
$types = "";

if ($filter === 'enrolled') {
    $sql .= " WHERE sa.StudentType = ?";
    $params[] = 'enrolled';
    $types = "s";
} elseif ($filter === 'interested') {
    $sql .= " WHERE sa.StudentType = ?";
    $params[] = 'interested';
    $types = "s";
}
$sql .= " GROUP BY sa.AccountID ORDER BY sa.CreatedAt DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}
$stmt->close();

// --- Load layout ---
require_once 'layout.php';
adminHead('Student Accounts'); 
adminSidebar('student_accounts'); 
adminTopbar('Student Accounts');
?>

<?php if ($msg): ?>
    <div class="alert alert-<?= $msgType ?>">
        <?= htmlspecialchars($msg) ?>
    </div>
<?php endif; ?>

<!-- FILTERS + EXPORT -->
<div style="display: flex; gap: .75rem; align-items: center; flex-wrap: wrap; margin-bottom: 1.2rem;">
    <?php foreach(['all' => 'All', 'enrolled' => 'Enrolled', 'interested' => 'Prospective'] as $val => $label): ?>
        <a href="?filter=<?= urlencode($val) ?>" style="padding: .45rem 1rem; font-size: .85rem; font-weight: 600; border-radius: var(--radius); text-decoration: none; <?= $filter === $val ? 'background: var(--navy); color: #fff;' : 'background: var(--cream); color: var(--navy); border: 1px solid var(--border);' ?>">
            <?= htmlspecialchars($label) ?>
        </a>
    <?php endforeach; ?>
    <span style="flex: 1;"></span>
    <a href="?export=1<?= $filter !== 'all' ? '&filter=' . urlencode($filter) : '' ?>" class="btn btn-sm btn-success" style="background: #1a6641; color: #fff; padding: .45rem 1rem; border-radius: var(--radius); text-decoration: none;">⬇ Export CSV</a>
</div>

<div style="display: grid; grid-template-columns: 1fr 360px; gap: 1.5rem; align-items: start;">

    <!-- STUDENT TABLE -->
    <div class="admin-table-wrap">
        <?php if (empty($students)): ?>
            <div class="panel" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                <div style="font-size: 3rem; margin-bottom: 1rem;">👥</div>
                <h3 style="font-family: 'Playfair Display', serif; color: var(--navy);">No student accounts yet</h3>
                <p>Students who self-register or are added manually will appear here.</p>
            </div>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Type</th>
                        <th>Course</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $s): ?>
                         hilab
                            <td style="color: var(--text-muted); font-size: .75rem;"><?= htmlspecialchars((string)$s['AccountID']) ?></td>
                            <td><strong><?= htmlspecialchars($s['FullName']) ?></strong></td>
                            <td><a href="mailto:<?= htmlspecialchars($s['Email']) ?>" style="color: var(--gold);"><?= htmlspecialchars($s['Email']) ?></a></td>
                            <td><?= htmlspecialchars(ucfirst($s['StudentType'])) ?></td>
                            <td><?= htmlspecialchars($s['CourseInfo'] ?? '—') ?></td>
                            <td style="color: var(--text-muted); font-size: .78rem;"><?= htmlspecialchars(date('d M Y', strtotime($s['CreatedAt']))) ?></td>
                            <td>
                                <div style="display: flex; gap: .4rem; flex-wrap: wrap;">
                                    <button class="btn btn-sm btn-edit" onclick="showReset(<?= (int)$s['AccountID'] ?>,'<?= htmlspecialchars(addslashes($s['FullName'])) ?>')" style="background: var(--navy); color: #fff; border: none; padding: .4rem .8rem; border-radius: 4px; cursor: pointer;">Reset PW</button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete account for <?= htmlspecialchars(addslashes($s['FullName'])) ?>? This action cannot be undone.')">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="account_id" value="<?= (int)$s['AccountID'] ?>">
                                        <button type="submit" class="btn btn-sm btn-del" style="background: #c0392b; color: #fff; border: none; padding: .4rem .8rem; border-radius: 4px; cursor: pointer;">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- CREATE / RESET FORM -->
    <div>
        <div class="admin-form-wrap" style="background: #fff; border-radius: var(--radius-lg); padding: 1.5rem; box-shadow: 0 2px 12px var(--shadow);">
            <h2 id="form-title" style="font-size: 1.2rem; color: var(--navy); margin-bottom: 1rem;">+ Create Student Account</h2>
            <form method="POST" id="student-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <input type="hidden" name="action" value="create" id="form-action">
                <input type="hidden" name="account_id" value="" id="form-account-id">

                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="fullname" required maxlength="100" style="width: 100%; padding: .6rem; border: 1px solid var(--border); border-radius: var(--radius);">
                </div>
                
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" required style="width: 100%; padding: .6rem; border: 1px solid var(--border); border-radius: var(--radius);">
                    <small style="color: var(--text-muted);">Enter a valid email address</small>
                </div>
                
                <div class="form-group">
                    <label>Student Type</label>
                    <select name="student_type" style="width: 100%; padding: .6rem; border: 1px solid var(--border); border-radius: var(--radius);">
                        <option value="enrolled">Enrolled</option>
                        <option value="interested">Prospective</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Course Info</label>
                    <input type="text" name="course" maxlength="200" style="width: 100%; padding: .6rem; border: 1px solid var(--border); border-radius: var(--radius);">
                </div>
                
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" required minlength="8" 
                           pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).{8,}"
                           title="Minimum 8 characters, at least one uppercase, one lowercase, and one number"
                           style="width: 100%; padding: .6rem; border: 1px solid var(--border); border-radius: var(--radius);">
                    <small style="color: var(--text-muted);">Minimum 8 characters, at least one uppercase, one lowercase, and one number</small>
                </div>

                <div style="display: flex; gap: .5rem;">
                    <button type="submit" class="btn btn-sm btn-add" id="form-btn" style="background: var(--gold); color: #fff; border: none; padding: .6rem 1.2rem; border-radius: var(--radius); cursor: pointer;">Create Account</button>
                    <button type="button" class="btn btn-sm" id="cancel-btn" onclick="resetForm()" style="display: none; background: #95a5a6; color: #fff; border: none; padding: .6rem 1.2rem; border-radius: var(--radius); cursor: pointer;">Cancel</button>
                </div>
            </form>
        </div>

        <!-- Reset Password Panel -->
        <div id="reset-panel" style="margin-top: 1rem; display: none;">
            <div class="admin-form-wrap" style="background: #fff; border-radius: var(--radius-lg); padding: 1.5rem; box-shadow: 0 2px 12px var(--shadow);">
                <h3 id="reset-title" style="font-size: 1.2rem; color: var(--navy); margin-bottom: 1rem;">Reset Password</h3>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="account_id" id="reset-account-id">
                    <div class="form-group">
                        <label>New Password *</label>
                        <input type="password" name="password" id="reset-password" required minlength="8"
                               pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).{8,}"
                               title="Minimum 8 characters, at least one uppercase, one lowercase, and one number"
                               style="width: 100%; padding: .6rem; border: 1px solid var(--border); border-radius: var(--radius);">
                        <small style="color: var(--text-muted);">Minimum 8 characters, at least one uppercase, one lowercase, and one number</small>
                    </div>
                    <div style="display: flex; gap: .5rem;">
                        <button type="submit" class="btn btn-sm btn-add" style="background: var(--gold); color: #fff; border: none; padding: .6rem 1.2rem; border-radius: var(--radius); cursor: pointer;">Reset Password</button>
                        <button type="button" onclick="document.getElementById('reset-panel').style.display='none'" class="btn btn-sm" style="background: #95a5a6; color: #fff; border: none; padding: .6rem 1.2rem; border-radius: var(--radius); cursor: pointer;">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function resetForm() {
    document.getElementById('form-title').textContent = '+ Create Student Account';
    document.getElementById('form-action').value = 'create';
    document.getElementById('form-account-id').value = '';
    document.getElementById('student-form').reset();
    document.getElementById('form-btn').textContent = 'Create Account';
    document.getElementById('cancel-btn').style.display = 'none';
}

function showReset(id, name) {
    document.getElementById('reset-title').textContent = 'Reset Password — ' + name;
    document.getElementById('reset-account-id').value = id;
    document.getElementById('reset-password').value = '';
    document.getElementById('reset-panel').style.display = 'block';
    document.getElementById('reset-panel').scrollIntoView({behavior: 'smooth'});
}
</script>

<?php 
adminFooter(); 
?>