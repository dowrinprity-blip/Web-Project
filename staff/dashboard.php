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

// Log staff dashboard access
logSecurityEvent("Staff dashboard accessed", "Staff ID: $sid, Username: " . ($staff['Username'] ?? 'unknown'));

// ==================== SECURE QUERIES USING PREPARED STATEMENTS ====================

// Get modules count
$modulesCount = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as c FROM Modules WHERE ModuleLeaderID = ?");
$stmt->bind_param('i', $sid);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $modulesCount = (int)$row['c'];
}
$stmt->close();

// Get programmes count
$programmesCount = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as c FROM Programmes WHERE ProgrammeLeaderID = ?");
$stmt->bind_param('i', $sid);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $programmesCount = (int)$row['c'];
}
$stmt->close();

// Get pending requests count
$pendingCount = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as c FROM StaffChangeRequests WHERE StaffID = ? AND Status = 'pending'");
$stmt->bind_param('i', $sid);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $pendingCount = (int)$row['c'];
}
$stmt->close();

// Get approved requests count
$approvedCount = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as c FROM StaffChangeRequests WHERE StaffID = ? AND Status = 'approved'");
$stmt->bind_param('i', $sid);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $approvedCount = (int)$row['c'];
}
$stmt->close();

// Get recent requests
$recentReqs = [];
$stmt = $conn->prepare("
    SELECT r.*, m.ModuleName 
    FROM StaffChangeRequests r
    LEFT JOIN Modules m ON r.ModuleID = m.ModuleID
    WHERE r.StaffID = ? 
    ORDER BY r.SubmittedAt DESC 
    LIMIT 5
");
$stmt->bind_param('i', $sid);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recentReqs[] = $row;
}
$stmt->close();

require_once 'layout.php';
staffHead('Dashboard');
staffSidebar('dashboard', $staff);
staffTopbar('Dashboard', $staff);
?>

<div class="stat-cards">
    <div class="stat-card">
        <div class="stat-card-num"><?= htmlspecialchars((string)$modulesCount) ?></div>
        <div class="stat-card-label">Modules Leading</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-num"><?= htmlspecialchars((string)$programmesCount) ?></div>
        <div class="stat-card-label">Programmes Leading</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-num"><?= htmlspecialchars((string)$pendingCount) ?></div>
        <div class="stat-card-label">Pending Requests</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-num"><?= htmlspecialchars((string)$approvedCount) ?></div>
        <div class="stat-card-label">Approved Requests</div>
    </div>
</div>

<!-- WELCOME PANEL -->
<div class="panel">
    <h2>Welcome back, <?= htmlspecialchars(explode(' ', $staff['Name'])[0]) ?> 👋</h2>
    <p style="color:var(--text-muted);font-size:.9rem;line-height:1.7;">
        This is your staff portal. You can update your profile, view your modules and programmes,
        and submit change requests. All changes require admin approval before going live.
    </p>
    <div style="display:flex;gap:.75rem;flex-wrap:wrap;margin-top:1.2rem;">
        
        <a href="<?= BASE_URL ?>/staff/requests.php" class="btn btn-sm" style="background:var(--gold);color:#fff;">Submit a Request</a>
        <a href="<?= BASE_URL ?>/staff/my_modules.php" class="btn btn-sm" style="background:var(--cream);color:var(--navy);border:1px solid var(--border);">My Modules</a>
    </div>
</div>

<!-- RECENT REQUESTS -->
<div class="panel">
    <h2>📝 Recent Requests</h2>
    <?php if (empty($recentReqs)): ?>
        <p style="color:var(--text-muted);font-size:.88rem;">No requests submitted yet. <a href="<?= BASE_URL ?>/staff/requests.php" style="color:var(--gold);">Submit your first request →</a></p>
    <?php else: ?>
        <?php foreach ($recentReqs as $r): ?>
        <div class="request-card">
            <div class="request-card-body">
                <div class="request-card-title">
                    <?= $r['RequestType'] === 'profile' ? '👤 Profile Update' : '📚 Module Change: ' . htmlspecialchars($r['ModuleName'] ?? 'Unknown') ?>
                </div>
                <div class="request-card-meta">
                    Submitted <?= htmlspecialchars(date('d M Y, H:i', strtotime($r['SubmittedAt']))) ?>
                    <?php if ($r['ReviewedAt']): ?> · Reviewed <?= htmlspecialchars(date('d M Y', strtotime($r['ReviewedAt']))) ?><?php endif; ?>
                </div>
                <?php if ($r['Status'] === 'rejected' && !empty($r['AdminNote'])): ?>
                    <div class="request-card-note">❌ Admin note: <?= htmlspecialchars($r['AdminNote']) ?></div>
                <?php endif; ?>
            </div>
            <span class="badge badge-<?= htmlspecialchars($r['Status']) ?>"><?= ucfirst(htmlspecialchars($r['Status'])) ?></span>
        </div>
        <?php endforeach; ?>
        <a href="<?= BASE_URL ?>/staff/requests.php" style="font-size:.85rem;color:var(--gold);">View all requests →</a>
    <?php endif; ?>
</div>

<?php staffFooter(); ?>