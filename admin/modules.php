<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Only admins can access
requireAdmin();

// Generate CSRF token for forms
$csrf_token = generateCsrfToken();

// Set security headers
setSecurityHeaders();

$action = $_GET['action'] ?? 'list';
$msg = '';
$msgType = 'success';

// Handle POST requests with CSRF protection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    checkPostCsrf();
    
    $pa       = $_POST['action'] ?? '';
    $name     = sanitize($_POST['name'] ?? '');
    $leaderID = isset($_POST['leader_id']) && $_POST['leader_id'] !== '' ? (int)$_POST['leader_id'] : null;
    $desc     = sanitize($_POST['description'] ?? '');
    $mid      = (int)($_POST['module_id'] ?? 0);
    
    if ($pa === 'create') {
        if (!$name) {
            $msg = 'Module name is required.';
            $msgType = 'error';
        } else {
            $stmt = $conn->prepare("INSERT INTO Modules (ModuleName, ModuleLeaderID, Description) VALUES (?, ?, ?)");
            $stmt->bind_param('sis', $name, $leaderID, $desc);
            
            if ($stmt->execute()) {
                $msg = "Module '$name' created.";
                $msgType = 'success';
                $action = 'list';
                logSecurityEvent("Module created", "Module: $name, Admin: " . ($_SESSION['admin_user'] ?? 'unknown'));
            } else {
                $msg = 'Error: ' . $conn->error;
                $msgType = 'error';
            }
            $stmt->close();
        }
    } elseif ($pa === 'update') {
        $stmt = $conn->prepare("UPDATE Modules SET ModuleName=?, ModuleLeaderID=?, Description=? WHERE ModuleID=?");
        $stmt->bind_param('sisi', $name, $leaderID, $desc, $mid);
        
        if ($stmt->execute()) {
            $msg = 'Module updated.';
            $msgType = 'success';
            $action = 'list';
            logSecurityEvent("Module updated", "Module ID: $mid, Admin: " . ($_SESSION['admin_user'] ?? 'unknown'));
        } else {
            $msg = 'Error: ' . $conn->error;
            $msgType = 'error';
        }
        $stmt->close();
        
    } elseif ($pa === 'delete') {
        // Log before deletion
        logSecurityEvent("Module deletion attempted", "Module ID: $mid, Admin: " . ($_SESSION['admin_user'] ?? 'unknown'));
        
        // Delete from ProgrammeModules first (foreign key constraint)
        $stmt = $conn->prepare("DELETE FROM ProgrammeModules WHERE ModuleID=?");
        $stmt->bind_param('i', $mid);
        $stmt->execute();
        $stmt->close();
        
        // Delete the module
        $stmt2 = $conn->prepare("DELETE FROM Modules WHERE ModuleID=?");
        $stmt2->bind_param('i', $mid);
        
        if ($stmt2->execute()) {
            $msg = 'Module deleted.';
            $msgType = 'success';
            $action = 'list';
            logSecurityEvent("Module deleted", "Module ID: $mid, Admin: " . ($_SESSION['admin_user'] ?? 'unknown'));
        } else {
            $msg = 'Error: ' . $conn->error;
            $msgType = 'error';
        }
        $stmt2->close();
    }
}

// ==================== SECURE QUERIES ====================

// Get staff list using prepared statement
$staff = [];
$stmt = $conn->prepare("SELECT StaffID, Name FROM Staff ORDER BY Name");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $staff[] = $row;
}
$stmt->close();

require_once 'layout.php';

// ── EDIT ──────────────────────────────────────────────
if ($action === 'edit' && isset($_GET['id'])) {
    $eid = (int)$_GET['id'];
    
    // Get module details using prepared statement
    $mod = null;
    $stmt = $conn->prepare("SELECT * FROM Modules WHERE ModuleID=?");
    $stmt->bind_param('i', $eid);
    $stmt->execute();
    $mod = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$mod) {
        // Module not found, redirect to list
        header('Location: modules.php');
        exit;
    }
    
    // Get programmes using this module
    $usedIn = [];
    $stmt = $conn->prepare("
        SELECT p.ProgrammeName, pm.Year 
        FROM ProgrammeModules pm 
        JOIN Programmes p ON pm.ProgrammeID = p.ProgrammeID 
        WHERE pm.ModuleID=?
    ");
    $stmt->bind_param('i', $eid);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $usedIn[] = $row;
    }
    $stmt->close();
    
    adminHead('Edit Module');
    adminSidebar('modules');
    adminTopbar('Edit Module');
    
    if ($msg): ?>
        <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    
    <div class="admin-form-wrap">
        <h2>✏️ Edit Module</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="module_id" value="<?= (int)$eid ?>">
            
            <div class="form-group">
                <label>Module Name *</label>
                <input type="text" name="name" value="<?= htmlspecialchars($mod['ModuleName']) ?>" required>
            </div>
            
            <div class="form-group">
                <label>Module Leader</label>
                <select name="leader_id">
                    <option value="">— Select —</option>
                    <?php foreach ($staff as $st): ?>
                        <option value="<?= (int)$st['StaffID'] ?>" <?= $mod['ModuleLeaderID'] == $st['StaffID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($st['Name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3"><?= htmlspecialchars($mod['Description'] ?? '') ?></textarea>
            </div>
            
            <div style="display:flex;gap:.75rem;">
                <button type="submit" class="btn btn-sm btn-add">Save Changes</button>
                <a href="modules.php" class="btn btn-sm" style="background:var(--cream);color:var(--navy);border:1px solid var(--border);">Cancel</a>
            </div>
        </form>
        
        <?php if (!empty($usedIn)): ?>
            <hr style="margin:2rem 0;border-color:var(--border);">
            <h3 style="font-size:.95rem;margin-bottom:.8rem;">Used in <?= count($usedIn) ?> Programme(s)</h3>
            <ul style="list-style:none;font-size:.85rem;line-height:2.2;color:var(--text-muted);">
                <?php foreach ($usedIn as $u): ?>
                    <li>📚 <?= htmlspecialchars($u['ProgrammeName']) ?> — Year <?= (int)$u['Year'] ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <?php adminFooter();
    exit;
}

// ── CREATE ─────────────────────────────────────────────
if ($action === 'create') {
    adminHead('New Module');
    adminSidebar('modules');
    adminTopbar('New Module');
    
    if ($msg): ?>
        <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    
    <div class="admin-form-wrap">
        <h2>+ Create New Module</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="action" value="create">
            
            <div class="form-group">
                <label>Module Name *</label>
                <input type="text" name="name" required placeholder="e.g. Introduction to Programming">
            </div>
            
            <div class="form-group">
                <label>Module Leader</label>
                <select name="leader_id">
                    <option value="">— Select —</option>
                    <?php foreach ($staff as $st): ?>
                        <option value="<?= (int)$st['StaffID'] ?>"><?= htmlspecialchars($st['Name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3" placeholder="Brief module overview..."></textarea>
            </div>
            
            <div style="display:flex;gap:.75rem;">
                <button type="submit" class="btn btn-sm btn-add">Create Module</button>
                <a href="modules.php" class="btn btn-sm" style="background:var(--cream);color:var(--navy);border:1px solid var(--border);">Cancel</a>
            </div>
        </form>
    </div>
    <?php adminFooter();
    exit;
}

// ── LIST ───────────────────────────────────────────────
// Get modules with secure prepared statement
$modules = [];
$stmt = $conn->prepare("
    SELECT m.*, s.Name AS LeaderName,
    (SELECT COUNT(*) FROM ProgrammeModules pm WHERE pm.ModuleID = m.ModuleID) AS ProgCount
    FROM Modules m 
    LEFT JOIN Staff s ON m.ModuleLeaderID = s.StaffID
    ORDER BY m.ModuleName
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $modules[] = $row;
}
$stmt->close();

adminHead('Modules');
adminSidebar('modules');
adminTopbar('Modules');

if ($msg): ?>
    <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem;">
    <span style="color:var(--text-muted);font-size:.88rem;"><?= count($modules) ?> modules</span>
    <a href="modules.php?action=create" class="btn btn-sm btn-add">+ New Module</a>
</div>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Module Name</th>
                <th>Leader</th>
                <th>Used In</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($modules as $m): ?>
            <tr>
                <td style="color:var(--text-muted);font-size:.78rem;"><?= (int)$m['ModuleID'] ?></td>
                <td>
                    <strong><?= htmlspecialchars($m['ModuleName']) ?></strong>
                    <?php if (!empty($m['Description'])): ?>
                        <div style="font-size:.75rem;color:var(--text-muted);margin-top:2px;">
                            <?= htmlspecialchars(substr($m['Description'], 0, 80)) ?>…
                        </div>
                    <?php endif; ?>
                </td>
                <td style="font-size:.85rem;"><?= htmlspecialchars($m['LeaderName'] ?? '—') ?></td>
                <td style="color:var(--text-muted);font-size:.85rem;">
                    <?= (int)$m['ProgCount'] ?> programme<?= $m['ProgCount'] != 1 ? 's' : '' ?>
                </td>
                <td>
                    <div style="display:flex;gap:.4rem;">
                        <a href="modules.php?action=edit&id=<?= (int)$m['ModuleID'] ?>" class="btn btn-sm btn-edit">Edit</a>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this module? This will also remove it from all programmes.')">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="module_id" value="<?= (int)$m['ModuleID'] ?>">
                            <button type="submit" class="btn btn-sm btn-del">Delete</button>
                        </form>
                        <a href="<?= BASE_URL ?>/module_detail.php?id=<?= (int)$m['ModuleID'] ?>" target="_blank" class="btn btn-sm" style="background:var(--cream);color:var(--navy);border:1px solid var(--border);">View</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php adminFooter(); ?>