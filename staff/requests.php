<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Only logged-in staff can access
requireStaff();

// Generate CSRF token for forms
$csrf_token = generateCsrfToken();

// Set security headers
setSecurityHeaders();

$staff = getLoggedInStaff();
$sid = (int)$staff['StaffID'];

$msg = '';
$msgType = 'success';
$tab = $_GET['type'] ?? 'list'; // list | profile | module

// Validate tab parameter
$allowedTabs = ['list', 'profile', 'module'];
if (!in_array($tab, $allowedTabs)) {
    $tab = 'list';
}

// Pre-fill module if coming from my_modules page (validate integer)
$preModuleID = isset($_GET['module_id']) ? (int)$_GET['module_id'] : 0;

// Handle form submissions with CSRF protection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    checkPostCsrf();
    
    $pa = $_POST['type'] ?? '';

    if ($pa === 'profile_text') {
        $newName = sanitize($_POST['name'] ?? '');
        $newBio = sanitize($_POST['bio'] ?? '');
        
        // Validate name length
        if (!empty($newName) && strlen($newName) > 100) {
            $msg = 'Name is too long (max 100 characters).';
            $msgType = 'error';
        }
        // Validate bio length
        elseif (!empty($newBio) && strlen($newBio) > 500) {
            $msg = 'Bio is too long (max 500 characters).';
            $msgType = 'error';
        }
        elseif (empty($newName) && empty($newBio)) {
            $msg = 'Please fill in at least one field to change.';
            $msgType = 'error';
        } else {
            $stmt = $conn->prepare("INSERT INTO StaffChangeRequests (StaffID, RequestType, NewName, NewBio) VALUES (?, 'profile', ?, ?)");
            $stmt->bind_param('iss', $sid, $newName, $newBio);
            
            if ($stmt->execute()) {
                $msg = 'Profile update request submitted! Admin will review it shortly.';
                $msgType = 'success';
                $tab = 'list';
                logSecurityEvent("Staff profile request submitted", "Staff ID: $sid, Name: $newName");
            } else {
                $msg = 'Error: ' . $conn->error;
                $msgType = 'error';
            }
            $stmt->close();
        }
    }

    if ($pa === 'module') {
        $moduleID = (int)($_POST['module_id'] ?? 0);
        $newName = sanitize($_POST['mod_name'] ?? '');
        $newDesc = sanitize($_POST['mod_desc'] ?? '');
        
        // Validate name length
        if (!empty($newName) && strlen($newName) > 100) {
            $msg = 'Module name is too long (max 100 characters).';
            $msgType = 'error';
        }
        // Validate description length
        elseif (!empty($newDesc) && strlen($newDesc) > 1000) {
            $msg = 'Module description is too long (max 1000 characters).';
            $msgType = 'error';
        }
        else {
            // Verify staff leads this module using prepared statement
            $chk = $conn->prepare("SELECT ModuleID FROM Modules WHERE ModuleID = ? AND ModuleLeaderID = ?");
            $chk->bind_param('ii', $moduleID, $sid);
            $chk->execute();
            $result = $chk->get_result();
            $isLeader = $result->num_rows > 0;
            $chk->close();
            
            if (!$isLeader) {
                $msg = 'You can only request changes to modules you lead.';
                $msgType = 'error';
                logSecurityEvent("Unauthorized module change attempt", "Staff ID: $sid, Module ID: $moduleID");
            } elseif (empty($newName) && empty($newDesc)) {
                $msg = 'Please fill in at least one field to change.';
                $msgType = 'error';
            } else {
                $stmt = $conn->prepare("INSERT INTO StaffChangeRequests (StaffID, RequestType, ModuleID, NewModuleName, NewModuleDesc) VALUES (?, 'module', ?, ?, ?)");
                $stmt->bind_param('iiss', $sid, $moduleID, $newName, $newDesc);
                
                if ($stmt->execute()) {
                    $msg = 'Module change request submitted! Admin will review it shortly.';
                    $msgType = 'success';
                    $tab = 'list';
                    logSecurityEvent("Staff module change request", "Staff ID: $sid, Module ID: $moduleID");
                } else {
                    $msg = 'Error: ' . $conn->error;
                    $msgType = 'error';
                }
                $stmt->close();
            }
        }
    }
}

// ==================== SECURE QUERIES ====================

// Fetch staff's modules for the module form using prepared statement
$myMods = [];
$stmt = $conn->prepare("SELECT ModuleID, ModuleName FROM Modules WHERE ModuleLeaderID = ? ORDER BY ModuleName");
$stmt->bind_param('i', $sid);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $myMods[] = $row;
}
$stmt->close();

// Fetch all requests using prepared statement
$allRequests = [];
$stmt = $conn->prepare("
    SELECT r.*, m.ModuleName 
    FROM StaffChangeRequests r
    LEFT JOIN Modules m ON r.ModuleID = m.ModuleID
    WHERE r.StaffID = ? 
    ORDER BY r.SubmittedAt DESC
");
$stmt->bind_param('i', $sid);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $allRequests[] = $row;
}
$stmt->close();

require_once 'layout.php';
staffHead('My Requests');
staffSidebar('requests', $staff);
staffTopbar('Change Requests');
?>

<!-- TABS -->
<div style="display:flex;gap:.5rem;margin-bottom:1.5rem;border-bottom:2px solid var(--border);padding-bottom:0;">
    <a href="?type=list" style="padding:.6rem 1.2rem;font-size:.88rem;font-weight:600;border-radius:var(--radius) var(--radius) 0 0;<?= $tab === 'list' ? 'background:var(--navy);color:#fff;' : 'background:var(--cream);color:var(--navy);border:1px solid var(--border);' ?>">
        📋 All Requests (<?= count($allRequests) ?>)
    </a>
    <a href="?type=profile" style="padding:.6rem 1.2rem;font-size:.88rem;font-weight:600;border-radius:var(--radius) var(--radius) 0 0;<?= $tab === 'profile' ? 'background:var(--navy);color:#fff;' : 'background:var(--cream);color:var(--navy);border:1px solid var(--border);' ?>">
        👤 Profile Update
    </a>
    <a href="?type=module" style="padding:.6rem 1.2rem;font-size:.88rem;font-weight:600;border-radius:var(--radius) var(--radius) 0 0;<?= $tab === 'module' ? 'background:var(--navy);color:#fff;' : 'background:var(--cream);color:var(--navy);border:1px solid var(--border);' ?>">
        📚 Module Change
    </a>
</div>

<?php if ($msg): ?>
    <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<!-- ALL REQUESTS LIST -->
<?php if ($tab === 'list'): ?>
<?php if (empty($allRequests)): ?>
<div class="panel" style="text-align:center;padding:3rem;">
    <div style="font-size:3rem;margin-bottom:1rem;">📝</div>
    <h3 style="font-family:'Playfair Display',serif;color:var(--navy);">No Requests Yet</h3>
    <p style="color:var(--text-muted);margin-bottom:1.2rem;">Use the tabs above to submit a profile update or module change request.</p>
</div>
<?php else: ?>
<?php foreach ($allRequests as $r): ?>
<div class="request-card">
    <div class="request-card-body">
        <div class="request-card-title">
            <?php if ($r['RequestType'] === 'profile'): ?>
                👤 Profile Update
                <?php if (!empty($r['NewName'])): ?> — Name: <em><?= htmlspecialchars($r['NewName']) ?></em><?php endif; ?>
            <?php else: ?>
                📚 Module Change: <em><?= htmlspecialchars($r['ModuleName'] ?? 'Unknown') ?></em>
                <?php if (!empty($r['NewModuleName'])): ?> → <em><?= htmlspecialchars($r['NewModuleName']) ?></em><?php endif; ?>
            <?php endif; ?>
        </div>
        <div class="request-card-meta">
            Submitted <?= htmlspecialchars(date('d M Y, H:i', strtotime($r['SubmittedAt']))) ?>
            <?php if (!empty($r['ReviewedAt'])): ?> · Reviewed <?= htmlspecialchars(date('d M Y', strtotime($r['ReviewedAt']))) ?><?php endif; ?>
        </div>
        <?php if (!empty($r['NewBio']) || !empty($r['NewModuleDesc'])): ?>
        <div style="font-size:.8rem;color:#555;margin-top:.4rem;font-style:italic;">
            "<?= htmlspecialchars(substr($r['NewBio'] ?: $r['NewModuleDesc'], 0, 100)) ?>…"
        </div>
        <?php endif; ?>
        <?php if ($r['Status'] === 'rejected' && !empty($r['AdminNote'])): ?>
        <div class="request-card-note">❌ Admin: "<?= htmlspecialchars($r['AdminNote']) ?>"</div>
        <?php endif; ?>
        <?php if ($r['Status'] === 'approved'): ?>
        <div style="font-size:.8rem;color:#1a6641;margin-top:.4rem;">✅ Changes have been applied to the live site.</div>
        <?php endif; ?>
    </div>
    <span class="badge badge-<?= htmlspecialchars($r['Status']) ?>"><?= ucfirst(htmlspecialchars($r['Status'])) ?></span>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- PROFILE REQUEST FORM -->
<?php elseif ($tab === 'profile'): ?>
<div class="panel">
    <h2>👤 Request Profile Update</h2>
    <div class="alert alert-info">All changes require admin approval before appearing on the public site.</div>
    <p style="color:var(--text-muted);font-size:.88rem;margin-bottom:1.2rem;">
        To update your profile photo or name, use the <a href="<?= BASE_URL ?>/staff/profile.php" style="color:var(--gold);">Profile page</a> which includes photo upload.
        This form is for bio/name text changes only.
    </p>
    <form method="POST">
        <!-- CSRF Protection Token -->
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <input type="hidden" name="type" value="profile_text">
        
        <div class="form-group">
            <label>New Name (leave blank to keep current)</label>
            <input type="text" name="name" placeholder="<?= htmlspecialchars($staff['Name']) ?>" maxlength="100">
        </div>
        
        <div class="form-group">
            <label>Updated Bio (max 500 characters)</label>
            <textarea name="bio" rows="4" maxlength="500" placeholder="Write a brief professional bio..."></textarea>
            <small style="color:var(--text-muted);font-size:.7rem;"><span id="bio-counter-profile">0</span>/500 characters</small>
        </div>
        
        <p style="font-size:.8rem;color:var(--text-muted);margin-bottom:1rem;">
            For photo changes, visit your <a href="<?= BASE_URL ?>/staff/profile.php" style="color:var(--gold);">Profile page</a>.
        </p>
        
        <button type="submit" class="btn btn-navy btn-sm" style="padding:.7rem 1.75rem;">Submit Request →</button>
    </form>
</div>

<script>
// Bio character counter for profile form
const profileBio = document.querySelector('textarea[name="bio"]');
const profileCounter = document.getElementById('bio-counter-profile');
if (profileBio && profileCounter) {
    profileBio.addEventListener('input', function() {
        profileCounter.textContent = this.value.length;
    });
}
</script>

<!-- MODULE CHANGE REQUEST FORM -->
<?php elseif ($tab === 'module'): ?>
<div class="panel">
    <h2>📚 Request Module Change</h2>
    <div class="alert alert-info">You can only request changes to modules you lead. Admin must approve before changes go live.</div>
    
    <?php if (empty($myMods)): ?>
        <p style="color:var(--text-muted);">You are not leading any modules, so cannot submit module change requests.</p>
    <?php else: ?>
    <form method="POST">
        <!-- CSRF Protection Token -->
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <input type="hidden" name="type" value="module">
        
        <div class="form-group">
            <label>Module *</label>
            <select name="module_id" required>
                <option value="">— Select Module —</option>
                <?php foreach ($myMods as $m): ?>
                <option value="<?= (int)$m['ModuleID'] ?>" <?= $preModuleID == $m['ModuleID'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($m['ModuleName']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>New Module Name (leave blank to keep current)</label>
            <input type="text" name="mod_name" maxlength="100" placeholder="Updated module name...">
        </div>
        
        <div class="form-group">
            <label>New Description (max 1000 characters, leave blank to keep current)</label>
            <textarea name="mod_desc" rows="4" maxlength="1000" placeholder="Updated module description..."></textarea>
            <small style="color:var(--text-muted);font-size:.7rem;"><span id="bio-counter-module">0</span>/1000 characters</small>
        </div>
        
        <button type="submit" class="btn btn-navy btn-sm" style="padding:.7rem 1.75rem;">Submit Request →</button>
    </form>
    
    <script>
    // Description character counter for module form
    const moduleDesc = document.querySelector('textarea[name="mod_desc"]');
    const moduleCounter = document.getElementById('bio-counter-module');
    if (moduleDesc && moduleCounter) {
        moduleDesc.addEventListener('input', function() {
            moduleCounter.textContent = this.value.length;
        });
    }
    </script>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php staffFooter(); ?>