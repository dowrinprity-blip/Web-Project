<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Only admins can access
requireAdmin();

// Generate CSRF token for forms
$csrf_token = generateCsrfToken();

// Set security headers
setSecurityHeaders();

$action  = $_GET['action'] ?? 'list';
$msg     = '';
$msgType = 'success';

// ── POST HANDLERS WITH CSRF PROTECTION ─────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    checkPostCsrf();
    
    $pa       = $_POST['action'] ?? '';
    $name     = sanitize($_POST['name'] ?? '');
    $levelID  = (int)($_POST['level_id'] ?? 0);
    $leaderID = isset($_POST['leader_id']) && $_POST['leader_id'] !== '' ? (int)$_POST['leader_id'] : null;
    $desc     = sanitize($_POST['description'] ?? '');
    $pid      = (int)($_POST['programme_id'] ?? 0);

    if ($pa === 'create') {
        if (!$name || !$levelID) {
            $msg = 'Name and level are required.';
            $msgType = 'error';
        } else {
            $stmt = $conn->prepare("INSERT INTO Programmes (ProgrammeName, LevelID, ProgrammeLeaderID, Description) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('siis', $name, $levelID, $leaderID, $desc);
            
            if ($stmt->execute()) {
                $msg = "Programme '$name' created.";
                $msgType = 'success';
                $action = 'list';
                logSecurityEvent("Programme created", "Programme: $name, Admin: " . ($_SESSION['admin_user'] ?? 'unknown'));
            } else {
                $msg = 'Error: ' . $conn->error;
                $msgType = 'error';
            }
            $stmt->close();
        }
        
    } elseif ($pa === 'update') {
        $stmt = $conn->prepare("UPDATE Programmes SET ProgrammeName=?, LevelID=?, ProgrammeLeaderID=?, Description=? WHERE ProgrammeID=?");
        $stmt->bind_param('siisi', $name, $levelID, $leaderID, $desc, $pid);
        
        if ($stmt->execute()) {
            $msg = 'Programme updated.';
            $msgType = 'success';
            $action = 'list';
            logSecurityEvent("Programme updated", "Programme ID: $pid, Admin: " . ($_SESSION['admin_user'] ?? 'unknown'));
        } else {
            $msg = 'Error: ' . $conn->error;
            $msgType = 'error';
        }
        $stmt->close();
        
    } elseif ($pa === 'delete') {
        // Log before deletion
        logSecurityEvent("Programme deletion attempted", "Programme ID: $pid, Admin: " . ($_SESSION['admin_user'] ?? 'unknown'));
        
        // Delete from ProgrammeModules first (foreign key constraint)
        $stmt = $conn->prepare("DELETE FROM ProgrammeModules WHERE ProgrammeID=?");
        $stmt->bind_param('i', $pid);
        $stmt->execute();
        $stmt->close();
        
        // Delete the programme
        $stmt2 = $conn->prepare("DELETE FROM Programmes WHERE ProgrammeID=?");
        $stmt2->bind_param('i', $pid);
        
        if ($stmt2->execute()) {
            $msg = 'Programme deleted.';
            $msgType = 'success';
            $action = 'list';
            logSecurityEvent("Programme deleted", "Programme ID: $pid, Admin: " . ($_SESSION['admin_user'] ?? 'unknown'));
        } else {
            $msg = 'Error: ' . $conn->error;
            $msgType = 'error';
        }
        $stmt2->close();
        
    } elseif ($pa === 'assign_module') {
        $mid  = (int)($_POST['module_id'] ?? 0);
        $year = (int)($_POST['year'] ?? 1);
        
        // Check if already assigned
        $chk = $conn->prepare("SELECT ProgrammeModuleID FROM ProgrammeModules WHERE ProgrammeID=? AND ModuleID=?");
        $chk->bind_param('ii', $pid, $mid);
        $chk->execute();
        $result = $chk->get_result();
        
        if ($result->num_rows === 0) {
            $stmt = $conn->prepare("INSERT INTO ProgrammeModules (ProgrammeID, ModuleID, Year) VALUES (?, ?, ?)");
            $stmt->bind_param('iii', $pid, $mid, $year);
            
            if ($stmt->execute()) {
                $msg = 'Module assigned.';
                $msgType = 'success';
                logSecurityEvent("Module assigned to programme", "Programme ID: $pid, Module ID: $mid, Admin: " . ($_SESSION['admin_user'] ?? 'unknown'));
            } else {
                $msg = 'Error: ' . $conn->error;
                $msgType = 'error';
            }
            $stmt->close();
        } else {
            $msg = 'Module already assigned to this programme.';
            $msgType = 'info';
        }
        $chk->close();
        
        $action = 'edit';
        $_GET['id'] = $pid;
        
    } elseif ($pa === 'remove_module') {
        $pmID = (int)($_POST['pm_id'] ?? 0);
        
        $stmt = $conn->prepare("DELETE FROM ProgrammeModules WHERE ProgrammeModuleID=?");
        $stmt->bind_param('i', $pmID);
        
        if ($stmt->execute()) {
            $msg = 'Module removed.';
            $msgType = 'success';
            logSecurityEvent("Module removed from programme", "ProgrammeModule ID: $pmID, Admin: " . ($_SESSION['admin_user'] ?? 'unknown'));
        } else {
            $msg = 'Error: ' . $conn->error;
            $msgType = 'error';
        }
        $stmt->close();
        
        $action = 'edit';
        $_GET['id'] = $pid;
    }
}

// ==================== SECURE QUERIES ====================

// Get levels using prepared statement
$levels = [];
$stmt = $conn->prepare("SELECT * FROM Levels ORDER BY LevelID");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $levels[] = $row;
}
$stmt->close();

// Get staff using prepared statement
$staff = [];
$stmt = $conn->prepare("SELECT StaffID, Name FROM Staff ORDER BY Name");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $staff[] = $row;
}
$stmt->close();

// Get all modules using prepared statement
$allMods = [];
$stmt = $conn->prepare("SELECT ModuleID, ModuleName FROM Modules ORDER BY ModuleName");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $allMods[] = $row;
}
$stmt->close();

require_once 'layout.php';

// ── EDIT PAGE ──────────────────────────────────────────
if ($action === 'edit' && isset($_GET['id'])) {
    $eid = (int)$_GET['id'];
    
    // Get programme details
    $prog = null;
    $stmt = $conn->prepare("SELECT p.*, l.LevelName FROM Programmes p JOIN Levels l ON p.LevelID = l.LevelID WHERE p.ProgrammeID = ?");
    $stmt->bind_param('i', $eid);
    $stmt->execute();
    $prog = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$prog) {
        header('Location: programmes.php');
        exit;
    }
    
    // Get assigned modules
    $assigned = [];
    $assignedIDs = [];
    $stmt = $conn->prepare("
        SELECT pm.*, m.ModuleName, st.Name AS LeaderName 
        FROM ProgrammeModules pm 
        JOIN Modules m ON pm.ModuleID = m.ModuleID 
        LEFT JOIN Staff st ON m.ModuleLeaderID = st.StaffID 
        WHERE pm.ProgrammeID = ? 
        ORDER BY pm.Year, m.ModuleName
    ");
    $stmt->bind_param('i', $eid);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $assigned[] = $row;
        $assignedIDs[] = $row['ModuleID'];
    }
    $stmt->close();
    
    adminHead('Edit Programme');
    adminSidebar('programmes');
    adminTopbar('Edit Programme');
    
    if ($msg): ?>
        <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    
    <div class="admin-form-wrap" style="max-width:900px;">
        <h2>✏️ Edit: <?= htmlspecialchars($prog['ProgrammeName']) ?></h2>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="programme_id" value="<?= (int)$eid ?>">
            
            <div class="form-row">
                <div class="form-group">
                    <label>Programme Name *</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($prog['ProgrammeName']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Level *</label>
                    <select name="level_id">
                        <?php foreach ($levels as $l): ?>
                            <option value="<?= (int)$l['LevelID'] ?>" <?= $prog['LevelID'] == $l['LevelID'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($l['LevelName']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label>Programme Leader</label>
                <select name="leader_id">
                    <option value="">— Select —</option>
                    <?php foreach ($staff as $st): ?>
                        <option value="<?= (int)$st['StaffID'] ?>" <?= $prog['ProgrammeLeaderID'] == $st['StaffID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($st['Name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3"><?= htmlspecialchars($prog['Description'] ?? '') ?></textarea>
            </div>
            
            <div style="display:flex;gap:.75rem;">
                <button type="submit" class="btn btn-sm btn-add">Save Changes</button>
                <a href="programmes.php" class="btn btn-sm" style="background:var(--cream);color:var(--navy);border:1px solid var(--border);">Cancel</a>
            </div>
        </form>

        <hr style="margin:2rem 0;border-color:var(--border);">
        <h2>📚 Assigned Modules (<?= count($assigned) ?>)</h2>
        
        <?php if (!empty($assigned)): ?>
            <div class="admin-table-wrap" style="margin:1rem 0 1.5rem;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Module</th>
                            <th>Leader</th>
                            <th>Year</th>
                            <th>Remove</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assigned as $am): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($am['ModuleName']) ?></strong></td>
                                <td style="color:var(--text-muted);"><?= htmlspecialchars($am['LeaderName'] ?? '—') ?></td>
                                <td><?= $prog['LevelID'] == 2 ? 'PG' : 'Year ' . (int)$am['Year'] ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                        <input type="hidden" name="action" value="remove_module">
                                        <input type="hidden" name="pm_id" value="<?= (int)$am['ProgrammeModuleID'] ?>">
                                        <input type="hidden" name="programme_id" value="<?= (int)$eid ?>">
                                        <button class="btn btn-sm btn-del" onclick="return confirm('Remove module?')">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <h3 style="font-size:.95rem;margin-bottom:.8rem;">+ Add Module</h3>
        <form method="POST" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="action" value="assign_module">
            <input type="hidden" name="programme_id" value="<?= (int)$eid ?>">
            
            <div class="form-group" style="margin:0;">
                <label>Module</label>
                <select name="module_id">
                    <?php foreach ($allMods as $m): ?>
                        <?php if (!in_array($m['ModuleID'], $assignedIDs)): ?>
                            <option value="<?= (int)$m['ModuleID'] ?>"><?= htmlspecialchars($m['ModuleName']) ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <?php if ($prog['LevelID'] == 1): ?>
                <div class="form-group" style="margin:0;">
                    <label>Year</label>
                    <select name="year">
                        <option value="1">Year 1</option>
                        <option value="2">Year 2</option>
                        <option value="3">Year 3</option>
                    </select>
                </div>
            <?php else: ?>
                <input type="hidden" name="year" value="1">
            <?php endif; ?>
            
            <button type="submit" class="btn btn-sm btn-success" style="margin-bottom:1.2rem;">Assign →</button>
        </form>
    </div>
    <?php adminFooter();
    exit;
}

// ── CREATE PAGE ────────────────────────────────────────
if ($action === 'create') {
    adminHead('New Programme');
    adminSidebar('programmes');
    adminTopbar('New Programme');
    
    if ($msg): ?>
        <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    
    <div class="admin-form-wrap">
        <h2>+ Create New Programme</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="action" value="create">
            
            <div class="form-row">
                <div class="form-group">
                    <label>Programme Name *</label>
                    <input type="text" name="name" required placeholder="e.g. BSc Computer Science">
                </div>
                <div class="form-group">
                    <label>Level *</label>
                    <select name="level_id" required>
                        <option value="">— Select —</option>
                        <?php foreach ($levels as $l): ?>
                            <option value="<?= (int)$l['LevelID'] ?>"><?= htmlspecialchars($l['LevelName']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label>Programme Leader</label>
                <select name="leader_id">
                    <option value="">— Select —</option>
                    <?php foreach ($staff as $st): ?>
                        <option value="<?= (int)$st['StaffID'] ?>"><?= htmlspecialchars($st['Name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3" placeholder="Brief overview of the programme..."></textarea>
            </div>
            
            <div style="display:flex;gap:.75rem;">
                <button type="submit" class="btn btn-sm btn-add">Create Programme</button>
                <a href="programmes.php" class="btn btn-sm" style="background:var(--cream);color:var(--navy);border:1px solid var(--border);">Cancel</a>
            </div>
        </form>
    </div>
    <?php adminFooter();
    exit;
}

// ── LIST PAGE ──────────────────────────────────────────
// Get programmes with secure prepared statement
$programmes = [];
$stmt = $conn->prepare("
    SELECT p.*, l.LevelName, s.Name AS LeaderName,
    (SELECT COUNT(*) FROM ProgrammeModules pm WHERE pm.ProgrammeID = p.ProgrammeID) AS ModCount
    FROM Programmes p
    JOIN Levels l ON p.LevelID = l.LevelID
    LEFT JOIN Staff s ON p.ProgrammeLeaderID = s.StaffID
    ORDER BY l.LevelID, p.ProgrammeName
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $programmes[] = $row;
}
$stmt->close();

adminHead('Programmes');
adminSidebar('programmes');
adminTopbar('Programmes');

if ($msg): ?>
    <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem;">
    <span style="color:var(--text-muted);font-size:.88rem;"><?= count($programmes) ?> programmes</span>
    <a href="programmes.php?action=create" class="btn btn-sm btn-add">+ New Programme</a>
</div>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Programme</th>
                <th>Level</th>
                <th>Leader</th>
                <th>Modules</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($programmes as $p): ?>
                <tr>
                    <td style="color:var(--text-muted);font-size:.78rem;"><?= (int)$p['ProgrammeID'] ?></td>
                    <td><strong><?= htmlspecialchars($p['ProgrammeName']) ?></strong></td>
                    <td>
                        <span class="badge <?= $p['LevelID'] == 1 ? 'badge-ug' : 'badge-pg' ?>">
                            <?= htmlspecialchars($p['LevelName']) ?>
                        </span>
                    </td>
                    <td style="font-size:.85rem;"><?= htmlspecialchars($p['LeaderName'] ?? '—') ?></td>
                    <td style="color:var(--text-muted);"><?= (int)$p['ModCount'] ?></td>
                    <td>
                        <div style="display:flex;gap:.4rem;flex-wrap:wrap;">
                            <a href="programmes.php?action=edit&id=<?= (int)$p['ProgrammeID'] ?>" class="btn btn-sm btn-edit">Edit</a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete \'<?= htmlspecialchars($p['ProgrammeName']) ?>\'? This will also remove all module associations.')">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="programme_id" value="<?= (int)$p['ProgrammeID'] ?>">
                                <button type="submit" class="btn btn-sm btn-del">Delete</button>
                            </form>
                            <a href="<?= BASE_URL ?>/programme_detail.php?id=<?= (int)$p['ProgrammeID'] ?>" target="_blank" class="btn btn-sm" style="background:var(--cream);color:var(--navy);border:1px solid var(--border);">View</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php adminFooter(); ?>