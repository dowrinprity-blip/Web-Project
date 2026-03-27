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

// Validate and sanitize filter programme
$filterProg = isset($_GET['programme']) ? (int)$_GET['programme'] : 0;
if ($filterProg < 0) {
    $filterProg = 0;
}

// Handle POST actions with CSRF protection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    checkPostCsrf();
    
    if (($_POST['action'] ?? '') === 'delete') {
        $iid = (int)$_POST['interest_id'];
        
        // Get student info before deletion for logging
        $stmt = $conn->prepare("SELECT StudentName, Email, ProgrammeID FROM InterestedStudents WHERE InterestID = ?");
        $stmt->bind_param('i', $iid);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();
        $stmt->close();
        
        // Delete the record
        $stmt = $conn->prepare("DELETE FROM InterestedStudents WHERE InterestID = ?");
        $stmt->bind_param('i', $iid);
        
        if ($stmt->execute()) {
            $msg = 'Registration removed.';
            $msgType = 'success';
            logSecurityEvent("Interested student deleted", "Interest ID: $iid, Name: " . ($student['StudentName'] ?? 'Unknown') . ", Admin: " . ($_SESSION['admin_user'] ?? 'unknown'));
        } else {
            $msg = 'Error: ' . $conn->error;
            $msgType = 'error';
        }
        $stmt->close();
    }
}

// ==================== SECURE QUERIES ====================

// Get programmes for filter dropdown
$programmes = [];
$stmt = $conn->prepare("SELECT ProgrammeID, ProgrammeName FROM Programmes ORDER BY ProgrammeName");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $programmes[] = $row;
}
$stmt->close();

// CSV Export with SECURE QUERY
if (isset($_GET['export'])) {
    $sql = "SELECT i.StudentName, i.Email, p.ProgrammeName, l.LevelName, i.RegisteredAt
            FROM InterestedStudents i
            JOIN Programmes p ON i.ProgrammeID = p.ProgrammeID
            JOIN Levels l ON p.LevelID = l.LevelID";
    $params = [];
    $types = "";
    
    if ($filterProg > 0) {
        $sql .= " WHERE i.ProgrammeID = ?";
        $params[] = $filterProg;
        $types = "i";
    }
    $sql .= " ORDER BY p.ProgrammeName, i.RegisteredAt DESC";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Set CSV headers
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="crestfield_mailing_list_' . date('Ymd') . '.csv"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Student Name', 'Email', 'Programme', 'Level', 'Registered At']);
    foreach ($rows as $r) {
        fputcsv($out, $r);
    }
    fclose($out);
    
    logSecurityEvent("Interested students exported", "Filter: " . ($filterProg > 0 ? "Programme ID $filterProg" : "All") . ", Admin: " . ($_SESSION['admin_user'] ?? 'unknown'));
    exit;
}

// Fetch students with SECURE QUERY
$students = [];
$sql = "SELECT i.*, p.ProgrammeName, l.LevelName, l.LevelID
        FROM InterestedStudents i
        JOIN Programmes p ON i.ProgrammeID = p.ProgrammeID
        JOIN Levels l ON p.LevelID = l.LevelID";
$params = [];
$types = "";

if ($filterProg > 0) {
    $sql .= " WHERE i.ProgrammeID = ?";
    $params[] = $filterProg;
    $types = "i";
}
$sql .= " ORDER BY i.RegisteredAt DESC";

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

require_once 'layout.php';
adminHead('Interested Students');
adminSidebar('students');
adminTopbar('Interested Students');

if ($msg): ?>
    <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap;margin-bottom:1.2rem;">
    <form method="GET" style="display:flex;gap:.75rem;align-items:center;flex:1;flex-wrap:wrap;">
        <select name="programme" onchange="this.form.submit()" style="padding:.5rem 1rem;border:1.5px solid var(--border);border-radius:var(--radius);font-family:'DM Sans',sans-serif;font-size:.87rem;background:var(--cream);">
            <option value="0">All Programmes</option>
            <?php foreach ($programmes as $p): ?>
                <option value="<?= (int)$p['ProgrammeID'] ?>" <?= $filterProg == $p['ProgrammeID'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['ProgrammeName']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <span style="color:var(--text-muted);font-size:.85rem;"><?= count($students) ?> student<?= count($students) != 1 ? 's' : '' ?></span>
    </form>
    <a href="students.php?export=1<?= $filterProg > 0 ? '&programme=' . urlencode((string)$filterProg) : '' ?>" 
       class="btn btn-sm btn-success" 
       style="background: #1a6641; color: #fff; padding: .45rem 1rem; border-radius: var(--radius); text-decoration: none;">
        ⬇ Export CSV
    </a>
</div>

<?php if (empty($students)): ?>
<div class="empty-state" style="text-align: center; padding: 3rem; background: #fff; border-radius: var(--radius-lg); box-shadow: 0 2px 12px var(--shadow);">
    <div style="font-size: 3rem; margin-bottom: 1rem;">📋</div>
    <h3 style="font-family: 'Playfair Display', serif; color: var(--navy);">No registrations yet</h3>
    <p style="color: var(--text-muted);">Students who register interest will appear here.</p>
</div>
<?php else: ?>
<div class="admin-table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Student</th>
                <th>Email</th>
                <th>Programme</th>
                <th>Level</th>
                <th>Registered</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $s): ?>
            <tr>
                <td style="color:var(--text-muted);font-size:.75rem;"><?= (int)$s['InterestID'] ?></td>
                <td><strong><?= htmlspecialchars($s['StudentName']) ?></strong></td>
                <td><a href="mailto:<?= htmlspecialchars($s['Email']) ?>" style="color:var(--gold);font-size:.85rem;"><?= htmlspecialchars($s['Email']) ?></a></td>
                <td style="font-size:.85rem;"><?= htmlspecialchars($s['ProgrammeName']) ?></td>
                <td>
                    <span class="badge <?= $s['LevelID'] == 1 ? 'badge-ug' : 'badge-pg' ?>">
                        <?= htmlspecialchars($s['LevelName']) ?>
                    </span>
                </td>
                <td style="color:var(--text-muted);font-size:.78rem;"><?= htmlspecialchars(date('d M Y', strtotime($s['RegisteredAt']))) ?></td>
                <td>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Remove registration for <?= htmlspecialchars(addslashes($s['StudentName'])) ?>? This action cannot be undone.')">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="interest_id" value="<?= (int)$s['InterestID'] ?>">
                        <button type="submit" class="btn btn-sm btn-del" style="background: #c0392b; color: #fff; border: none; padding: .4rem .8rem; border-radius: 4px; cursor: pointer;">Remove</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php adminFooter(); ?>