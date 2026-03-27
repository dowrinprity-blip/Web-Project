<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Only admins can access
requireAdmin();

// Log admin dashboard access
logSecurityEvent("Admin dashboard accessed", "User: " . ($_SESSION['admin_user'] ?? 'unknown'));

// Generate CSRF token for any potential forms
$csrf_token = generateCsrfToken();

// ==================== SECURE QUERIES USING PREPARED STATEMENTS ====================

$stats = [];

// Programmes count
$stmt = $conn->prepare("SELECT COUNT(*) as c FROM Programmes");
$stmt->execute();
$stats['programmes'] = $stmt->get_result()->fetch_assoc()['c'];
$stmt->close();

// Modules count
$stmt = $conn->prepare("SELECT COUNT(*) as c FROM Modules");
$stmt->execute();
$stats['modules'] = $stmt->get_result()->fetch_assoc()['c'];
$stmt->close();

// Staff count
$stmt = $conn->prepare("SELECT COUNT(*) as c FROM Staff");
$stmt->execute();
$stats['staff'] = $stmt->get_result()->fetch_assoc()['c'];
$stmt->close();

// Students count (InterestedStudents)
$stmt = $conn->prepare("SELECT COUNT(*) as c FROM InterestedStudents");
$stmt->execute();
$stats['students'] = $stmt->get_result()->fetch_assoc()['c'];
$stmt->close();

// Recent registrations
$recent = [];
$stmt = $conn->prepare("
    SELECT i.StudentName, i.Email, i.RegisteredAt, p.ProgrammeName
    FROM InterestedStudents i
    JOIN Programmes p ON i.ProgrammeID = p.ProgrammeID
    ORDER BY i.RegisteredAt DESC LIMIT 8
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent[] = $row;
}
$stmt->close();

// Popular programmes
$popular = [];
$stmt = $conn->prepare("
    SELECT p.ProgrammeName, p.ProgrammeID, COUNT(i.InterestID) AS total
    FROM Programmes p
    LEFT JOIN InterestedStudents i ON p.ProgrammeID = i.ProgrammeID
    GROUP BY p.ProgrammeID ORDER BY total DESC LIMIT 6
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $popular[] = $row;
}
$stmt->close();

// Pending requests count
$pendingReqs = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as c FROM StaffChangeRequests WHERE Status = 'pending'");
$stmt->execute();
if ($row = $stmt->get_result()->fetch_assoc()) {
    $pendingReqs = (int)$row['c'];
}
$stmt->close();

require_once 'layout.php';
adminHead('Dashboard');
adminSidebar('dashboard');
adminTopbar('Dashboard');
?>

<?php if ($pendingReqs > 0): ?>
<div class="alert alert-warning" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;">
    <span>⚠️ <strong><?= (int)$pendingReqs ?></strong> staff change request<?= $pendingReqs != 1 ? 's' : '' ?> awaiting your review.</span>
    <a href="/university/admin/requests.php" class="btn btn-sm" style="background:#7a5c1e;color:#fff;">Review Now →</a>
</div>
<?php endif; ?>

<!-- STAT CARDS -->
<div class="stat-cards">
    <div class="stat-card">
        <div class="stat-card-num"><?= htmlspecialchars((string)$stats['programmes']) ?></div>
        <div class="stat-card-label">Programmes</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-num"><?= htmlspecialchars((string)$stats['modules']) ?></div>
        <div class="stat-card-label">Modules</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-num"><?= htmlspecialchars((string)$stats['staff']) ?></div>
        <div class="stat-card-label">Staff Members</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-num"><?= htmlspecialchars((string)$stats['students']) ?></div>
        <div class="stat-card-label">Registrations</div>
    </div>
</div>

<!-- QUICK ACTIONS -->
<div style="background:#fff;border-radius:var(--radius-lg);padding:1.5rem;box-shadow:0 2px 12px var(--shadow);margin-bottom:1.5rem;">
    <h2 style="font-size:1rem;color:var(--navy);margin-bottom:1rem;">Quick Actions</h2>
    <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
        <a href="/university/admin/programmes.php?action=create" class="btn btn-sm btn-add">+ New Programme</a>
        <a href="/university/admin/modules.php?action=create" class="btn btn-sm btn-edit">+ New Module</a>
        <a href="/university/admin/staff.php" class="btn btn-sm btn-edit">+ Add Staff</a>
        <a href="/university/admin/students.php?export=1" class="btn btn-sm btn-success">⬇ Export Mailing List</a>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start;">

    <!-- RECENT REGISTRATIONS -->
    <div style="background:#fff;border-radius:var(--radius-lg);padding:1.5rem;box-shadow:0 2px 12px var(--shadow);">
        <h2 style="font-size:1rem;color:var(--navy);margin-bottom:1rem;">Recent Registrations</h2>
        <?php if (!empty($recent)): ?>
        <table class="admin-table" style="font-size:.82rem;">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Programme</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent as $r): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($r['StudentName']) ?></strong><br>
                        <span style="color:var(--text-muted);font-size:.75rem;"><?= htmlspecialchars($r['Email']) ?></span>
                    </td>
                    <td style="font-size:.78rem;"><?= htmlspecialchars($r['ProgrammeName']) ?></td>
                    <td style="color:var(--text-muted);font-size:.75rem;"><?= htmlspecialchars(date('d M Y', strtotime($r['RegisteredAt']))) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div style="margin-top:1rem;"><a href="/university/admin/students.php" class="btn btn-sm btn-edit">View All →</a></div>
        <?php else: ?>
        <p style="color:var(--text-muted);font-size:.88rem;">No registrations yet.</p>
        <?php endif; ?>
    </div>

    <!-- PROGRAMME INTEREST -->
    <div style="background:#fff;border-radius:var(--radius-lg);padding:1.5rem;box-shadow:0 2px 12px var(--shadow);">
        <h2 style="font-size:1rem;color:var(--navy);margin-bottom:1rem;">Programme Interest</h2>
        <?php foreach ($popular as $p): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:.6rem 0;border-bottom:1px solid #f5f2ee;">
            <a href="/university/programme_detail.php?id=<?= (int)$p['ProgrammeID'] ?>"
               style="font-size:.85rem;color:var(--navy);"><?= htmlspecialchars($p['ProgrammeName']) ?></a>
            <span style="background:var(--gold);color:#fff;border-radius:99px;padding:.15rem .75rem;font-size:.75rem;font-weight:700;"><?= (int)$p['total'] ?></span>
        </div>
        <?php endforeach; ?>
    </div>

</div>

<?php adminFooter(); ?>