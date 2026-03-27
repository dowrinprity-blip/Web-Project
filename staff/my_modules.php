<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Only logged-in staff can access
requireStaff();

// Generate CSRF token for any forms on this page
$csrf_token = generateCsrfToken();

// Set security headers
setSecurityHeaders();

$staff = getLoggedInStaff();
$sid = (int)$staff['StaffID'];

// Log staff access to this page
logSecurityEvent("Staff viewed modules", "Staff ID: $sid, Username: " . ($staff['Username'] ?? 'unknown'));

// Get modules with secure prepared statement
$modules = [];
$stmt = $conn->prepare("
    SELECT m.*,
    GROUP_CONCAT(DISTINCT p.ProgrammeName ORDER BY p.ProgrammeName SEPARATOR '||') AS Programmes,
    GROUP_CONCAT(DISTINCT pm.Year ORDER BY pm.Year SEPARATOR '||') AS Years
    FROM Modules m
    LEFT JOIN ProgrammeModules pm ON m.ModuleID = pm.ModuleID
    LEFT JOIN Programmes p ON pm.ProgrammeID = p.ProgrammeID
    WHERE m.ModuleLeaderID = ?
    GROUP BY m.ModuleID ORDER BY m.ModuleName
");
$stmt->bind_param('i', $sid);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $modules[] = $row;
}
$stmt->close();

require_once 'layout.php';
staffHead('My Modules');
staffSidebar('modules', $staff);
staffTopbar('My Modules');
?>

<?php if (empty($modules)): ?>
<div class="panel" style="text-align:center;padding:3rem;">
    <div style="font-size:3rem;margin-bottom:1rem;">📭</div>
    <h3 style="font-family:'Playfair Display',serif;color:var(--navy);">No Modules Assigned</h3>
    <p style="color:var(--text-muted);">You are not currently leading any modules.</p>
</div>
<?php else: ?>
<div style="margin-bottom:1rem;color:var(--text-muted);font-size:.88rem;">
    You are leading <strong><?= count($modules) ?></strong> module<?= count($modules) != 1 ? 's' : '' ?>.
</div>

<?php foreach ($modules as $mod): ?>
<div class="panel" style="margin-bottom:1rem;">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;">
        <div style="flex:1;">
            <h2 style="border:none;padding:0;margin-bottom:.5rem;"><?= htmlspecialchars($mod['ModuleName']) ?></h2>
            <p style="color:var(--text-muted);font-size:.88rem;line-height:1.6;margin-bottom:.75rem;">
                <?= htmlspecialchars($mod['Description'] ?? 'No description available.') ?>
            </p>
            <?php if (!empty($mod['Programmes'])): ?>
            <div style="display:flex;flex-wrap:wrap;gap:.35rem;">
                <?php 
                $programmes = explode('||', $mod['Programmes']);
                foreach ($programmes as $pname): 
                ?>
                <span style="background:var(--cream);border:1px solid var(--border);color:var(--navy);font-size:.72rem;padding:.2rem .6rem;border-radius:99px;font-weight:600;">
                    <?= htmlspecialchars($pname) ?>
                </span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <!-- SECURE REQUEST LINK - Uses POST with CSRF -->
        <form method="POST" action="<?= BASE_URL ?>/staff/requests.php" style="display:inline;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
    <input type="hidden" name="action" value="module_request">
    <input type="hidden" name="module_id" value="<?= (int)$mod['ModuleID'] ?>">
    <button type="submit" class="btn btn-sm" style="background:var(--gold);color:#fff;white-space:nowrap;border:none;cursor:pointer;padding:.5rem 1rem;border-radius:var(--radius);">
        Request Changes →
    </button>
</form>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php staffFooter(); ?>