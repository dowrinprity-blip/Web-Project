<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Only logged-in staff can access
requireStaff();

// Set security headers
setSecurityHeaders();

$staff = getLoggedInStaff();
$sid = (int)$staff['StaffID'];

// Log staff access to this page
logSecurityEvent("Staff viewed programmes", "Staff ID: $sid, Username: " . ($staff['Username'] ?? 'unknown'));

// Get programmes with secure prepared statement
$programmes = [];
$stmt = $conn->prepare("
    SELECT p.*, l.LevelName,
    (SELECT COUNT(*) FROM ProgrammeModules pm WHERE pm.ProgrammeID = p.ProgrammeID) AS ModCount
    FROM Programmes p 
    JOIN Levels l ON p.LevelID = l.LevelID
    WHERE p.ProgrammeLeaderID = ? 
    ORDER BY p.ProgrammeName
");
$stmt->bind_param('i', $sid);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $programmes[] = $row;
}
$stmt->close();

require_once 'layout.php';
staffHead('My Programmes');
staffSidebar('programmes', $staff);
staffTopbar('My Programmes');
?>

<?php if (empty($programmes)): ?>
<div class="panel" style="text-align:center;padding:3rem;">
    <div style="font-size:3rem;margin-bottom:1rem;">🎓</div>
    <h3 style="font-family:'Playfair Display',serif;color:var(--navy);">No Programmes Assigned</h3>
    <p style="color:var(--text-muted);">You are not currently leading any programmes.</p>
</div>
<?php else: ?>
<div style="margin-bottom:1rem;color:var(--text-muted);font-size:.88rem;">
    You are leading <strong><?= count($programmes) ?></strong> programme<?= count($programmes) != 1 ? 's' : '' ?>.
</div>

<div class="cards-grid" style="max-width:900px;">
    <?php foreach ($programmes as $p): ?>
    <div class="card">
        <div class="card-colour-bar"></div>
        <div class="card-body">
            <span class="card-badge <?= $p['LevelID'] == 2 ? 'pg' : '' ?>">
                <?= htmlspecialchars($p['LevelName']) ?>
            </span>
            <h3><?= htmlspecialchars($p['ProgrammeName']) ?></h3>
            <p><?= htmlspecialchars(substr($p['Description'] ?? '', 0, 110)) ?>…</p>
        </div>
        <div class="card-footer">
            <span class="card-footer-info">📚 <?= (int)$p['ModCount'] ?> modules</span>
            <a href="<?= BASE_URL ?>/programme_detail.php?id=<?= (int)$p['ProgrammeID'] ?>" 
               class="card-arrow" 
               target="_blank"
               rel="noopener noreferrer">→</a>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php staffFooter(); ?>