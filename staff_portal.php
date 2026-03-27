<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Set security headers
setSecurityHeaders();

$page_title = 'Staff Portal';
$upload_url = BASE_URL . '/uploads/staff_photos/';

// ==================== SECURE QUERIES ====================

// Get staff list with prepared statement
$staffList = [];
$stmt = $conn->prepare("SELECT * FROM Staff ORDER BY Name");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $staffList[] = $row;
}
$stmt->close();

$selectedStaff = null;
$modulesLed    = [];
$programmesLed = [];
$staffID       = isset($_GET['staff_id']) ? (int)$_GET['staff_id'] : 0;

// Validate staff ID
if ($staffID < 0) {
    $staffID = 0;
}

if ($staffID > 0) {
    // Get selected staff with prepared statement
    $stmt = $conn->prepare("SELECT * FROM Staff WHERE StaffID = ?");
    $stmt->bind_param('i', $staffID);
    $stmt->execute();
    $selectedStaff = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($selectedStaff) {
        // Get modules led by this staff member
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
        $stmt->bind_param('i', $staffID);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $modulesLed[] = $row;
        }
        $stmt->close();

        // Get programmes led by this staff member
        $stmt = $conn->prepare("
            SELECT p.*, l.LevelName,
            (SELECT COUNT(*) FROM ProgrammeModules pm WHERE pm.ProgrammeID = p.ProgrammeID) AS ModCount
            FROM Programmes p 
            JOIN Levels l ON p.LevelID = l.LevelID
            WHERE p.ProgrammeLeaderID = ? 
            ORDER BY p.ProgrammeName
        ");
        $stmt->bind_param('i', $staffID);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $programmesLed[] = $row;
        }
        $stmt->close();
    }
}

// Helper function to get staff stats (module/programme counts)
function getStaffStats($conn, $staffID) {
    $modCount = 0;
    $progCount = 0;
    
    $stmt = $conn->prepare("SELECT COUNT(*) as c FROM Modules WHERE ModuleLeaderID = ?");
    $stmt->bind_param('i', $staffID);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $modCount = (int)$row['c'];
    }
    $stmt->close();
    
    $stmt = $conn->prepare("SELECT COUNT(*) as c FROM Programmes WHERE ProgrammeLeaderID = ?");
    $stmt->bind_param('i', $staffID);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $progCount = (int)$row['c'];
    }
    $stmt->close();
    
    return ['modCount' => $modCount, 'progCount' => $progCount];
}

include 'includes/header.php';
?>

<style>
@media (max-width: 768px) {
    div[style*="grid-template-columns:280px"] { grid-template-columns: 1fr !important; }
}
</style>

<div class="page-hero">
    <div class="page-hero-inner">
        <div class="breadcrumb">
            <a href="<?= htmlspecialchars(BASE_URL) ?>/index.php">Home</a> / Staff Portal
        </div>
        <h1>Faculty &amp; Staff Portal</h1>
        <p>Browse academic staff and their teaching responsibilities across all programmes and modules.</p>
        <div style="margin-top:1.5rem;">
            <?php if (isStaffLoggedIn()): $sd = getLoggedInStaff(); ?>
                <a href="<?= htmlspecialchars(BASE_URL) ?>/staff/dashboard.php" class="btn btn-gold">
                    Go to My Dashboard →
                </a>
                <span style="color:rgba(255,255,255,.55);font-size:.85rem;margin-left:1rem;">
                    Logged in as <?= htmlspecialchars($sd['Name']) ?>
                </span>
            <?php else: ?>
                <a href="<?= htmlspecialchars(BASE_URL) ?>/staff/login.php" class="btn btn-gold">Staff Login →</a>
                <span style="color:rgba(255,255,255,.5);font-size:.82rem;margin-left:1rem;">
                    Are you a staff member? Sign in to manage your profile.
                </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<div style="max-width:1200px;margin:0 auto;padding:2.5rem 2rem;display:grid;grid-template-columns:280px 1fr;gap:2.5rem;align-items:start;">

    <!-- STAFF LIST SIDEBAR -->
    <aside>
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;position:sticky;top:100px;">
            <div style="background:var(--navy);color:var(--gold-light);padding:1rem 1.25rem;font-size:.8rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;">
                👤 Staff Members (<?= count($staffList) ?>)
            </div>
            <?php foreach ($staffList as $st):
                $stats = getStaffStats($conn, $st['StaffID']);
                $mCount = $stats['modCount'];
                $pCount = $stats['progCount'];
                
                // FIXED: Check if photo exists
                $hasPhoto = !empty($st['Photo']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/University/uploads/staff_photos/' . $st['Photo']);
                $photoSrc = $upload_url . rawurlencode($st['Photo'] ?? '');
                
                $spParts = explode(' ', $st['Name']);
                $spInitials = '';
                foreach ($spParts as $w) {
                    if (!empty($w) && ctype_alpha($w[0])) {
                        $spInitials .= $w[0];
                    }
                }
                $spInitials = strtoupper(substr($spInitials, 0, 2));
                $isActive = $staffID == $st['StaffID'];
            ?>
            <a href="staff_profile.php?id=<?= (int)$st['StaffID'] ?>"
               style="display:flex;align-items:center;gap:.75rem;padding:.65rem 1.25rem;border-bottom:1px solid var(--border);transition:all .2s;<?= $isActive ? 'background:var(--navy);color:#fff;' : 'color:var(--text);' ?>">
                <div style="width:32px;height:32px;border-radius:50%;background:var(--gold);color:var(--navy);display:flex;align-items:center;justify-content:center;font-family:'Playfair Display',serif;font-size:.75rem;font-weight:900;flex-shrink:0;overflow:hidden;">
                    <?php if ($hasPhoto): ?>
                        <img src="<?= htmlspecialchars($photoSrc) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
                    <?php else: ?>
                        <?= htmlspecialchars($spInitials) ?>
                    <?php endif; ?>
                </div>
                <div>
                    <div style="font-size:.85rem;font-weight:<?= $isActive ? '600' : '400' ?>;">
                        <?= htmlspecialchars($st['Name']) ?>
                    </div>
                    <div style="font-size:.72rem;<?= $isActive ? 'color:rgba(255,255,255,.55);' : 'color:var(--text-muted);' ?>;margin-top:.1rem;">
                        <?= (int)$mCount ?> module<?= $mCount != 1 ? 's' : '' ?> · <?= (int)$pCount ?> programme<?= $pCount != 1 ? 's' : '' ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </aside>

    <!-- STAFF DETAIL -->
    <main>
        <?php if (!$selectedStaff): ?>
        <div style="text-align:center;padding:4rem 2rem;border:2px dashed var(--border);border-radius:var(--radius-lg);color:var(--text-muted);">
            <div style="font-size:3rem;margin-bottom:1rem;">👈</div>
            <h2 style="font-family:'Playfair Display',serif;color:var(--navy);font-size:1.4rem;margin-bottom:.5rem;">Select a Staff Member</h2>
            <p>Choose a faculty member from the list to view their modules and programmes.</p>
        </div>

        <?php else:
            // Get initials safely
            $parts = explode(' ', $selectedStaff['Name']);
            $initials = '';
            foreach ($parts as $w) {
                if (!empty($w) && ctype_alpha($w[0] ?? '')) {
                    $initials .= $w[0];
                }
            }
            $initials = strtoupper(substr($initials, 0, 2));
        ?>

        <!-- PROFILE CARD -->
        <div style="background:linear-gradient(135deg,var(--navy),var(--navy-mid));border-radius:var(--radius-lg);padding:2rem;display:flex;align-items:center;gap:1.5rem;margin-bottom:1.5rem;">
            <?php
            // FIXED: Check if photo exists
            $hasPhoto = !empty($selectedStaff['Photo']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/University/uploads/staff_photos/' . $selectedStaff['Photo']);
            $photoSrc = $upload_url . rawurlencode($selectedStaff['Photo'] ?? '');
            ?>
            <div style="width:80px;height:80px;border-radius:50%;background:var(--gold);color:var(--navy);display:flex;align-items:center;justify-content:center;font-family:'Playfair Display',serif;font-size:1.6rem;font-weight:900;flex-shrink:0;overflow:hidden;border:3px solid var(--gold);">
                <?php if ($hasPhoto): ?>
                    <img src="<?= htmlspecialchars($photoSrc) ?>" alt="<?= htmlspecialchars($selectedStaff['Name']) ?>" style="width:100%;height:100%;object-fit:cover;">
                <?php else: ?>
                    <?= htmlspecialchars($initials) ?>
                <?php endif; ?>
            </div>
            <div>
                <h1 style="font-family:'Playfair Display',serif;font-size:1.6rem;color:#fff;margin-bottom:.3rem;">
                    <?= htmlspecialchars($selectedStaff['Name']) ?>
                </h1>
                <p style="color:rgba(255,255,255,.6);font-size:.88rem;">
                    <?= count($modulesLed) ?> module<?= count($modulesLed) != 1 ? 's' : '' ?> leading &nbsp;·&nbsp;
                    <?= count($programmesLed) ?> programme<?= count($programmesLed) != 1 ? 's' : '' ?> leading
                </p>
            </div>
        </div>

        <!-- BIO -->
        <?php if (!empty($selectedStaff['Bio'])): ?>
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius-lg);padding:1.25rem 1.5rem;margin-bottom:1.5rem;">
            <h2 style="font-family:'Playfair Display',serif;font-size:1rem;color:var(--navy);margin-bottom:.6rem;">About</h2>
            <p style="font-size:.88rem;color:var(--text-muted);line-height:1.7;"><?= htmlspecialchars($selectedStaff['Bio']) ?></p>
        </div>
        <?php endif; ?>

        <!-- MODULES LED -->
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;margin-bottom:1.5rem;">
            <div style="background:var(--cream);border-bottom:1px solid var(--border);padding:1rem 1.5rem;display:flex;align-items:center;justify-content:space-between;">
                <h2 style="font-family:'Playfair Display',serif;font-size:1.1rem;color:var(--navy);">📚 Modules Leading</h2>
                <span style="background:var(--navy);color:var(--gold-light);border-radius:99px;padding:.2rem .75rem;font-size:.75rem;font-weight:700;"><?= count($modulesLed) ?></span>
            </div>
            <div style="padding:1.25rem 1.5rem;">
                <?php if (empty($modulesLed)): ?>
                <p style="color:var(--text-muted);font-size:.88rem;">Not leading any modules currently.</p>
                <?php else: ?>
                <div class="module-pill-list">
                    <?php foreach ($modulesLed as $mod): ?>
                    <a href="<?= htmlspecialchars(BASE_URL) ?>/module_detail.php?id=<?= (int)$mod['ModuleID'] ?>" class="module-pill">
                        <div>
                            <div class="module-pill-name"><?= htmlspecialchars($mod['ModuleName']) ?></div>
                            <?php if (!empty($mod['Description'])): ?>
                            <div class="module-pill-leader">
                                <?= htmlspecialchars(substr($mod['Description'], 0, 90)) ?>…
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($mod['Programmes'])): ?>
                            <div style="display:flex;flex-wrap:wrap;gap:.35rem;margin-top:.4rem;">
                                <?php 
                                $programmeNames = explode('||', $mod['Programmes']);
                                foreach ($programmeNames as $pname): 
                                ?>
                                <span style="background:var(--cream);border:1px solid var(--border);color:var(--navy);font-size:.7rem;padding:.15rem .55rem;border-radius:99px;font-weight:600;">
                                    <?= htmlspecialchars($pname) ?>
                                </span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <span style="color:var(--gold);font-size:.9rem;">→</span>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- PROGRAMMES LED -->
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;">
            <div style="background:var(--cream);border-bottom:1px solid var(--border);padding:1rem 1.5rem;display:flex;align-items:center;justify-content:space-between;">
                <h2 style="font-family:'Playfair Display',serif;font-size:1.1rem;color:var(--navy);">🎓 Programmes Leading</h2>
                <span style="background:var(--navy);color:var(--gold-light);border-radius:99px;padding:.2rem .75rem;font-size:.75rem;font-weight:700;"><?= count($programmesLed) ?></span>
            </div>
            <div style="padding:1.25rem 1.5rem;">
                <?php if (empty($programmesLed)): ?>
                <p style="color:var(--text-muted);font-size:.88rem;">Not leading any programmes currently.</p>
                <?php else: ?>
                <div class="module-pill-list">
                    <?php foreach ($programmesLed as $prog): ?>
                    <a href="<?= htmlspecialchars(BASE_URL) ?>/programme_detail.php?id=<?= (int)$prog['ProgrammeID'] ?>" class="module-pill">
                        <div>
                            <div class="module-pill-name"><?= htmlspecialchars($prog['ProgrammeName']) ?></div>
                            <div class="module-pill-leader">
                                <span class="card-badge <?= $prog['LevelID'] == 2 ? 'pg' : '' ?>" style="font-size:.68rem;padding:.15rem .55rem;">
                                    <?= htmlspecialchars($prog['LevelName']) ?>
                                </span>
                                &nbsp;<?= (int)$prog['ModCount'] ?> modules
                            </div>
                        </div>
                        <span style="color:var(--gold);font-size:.9rem;">→</span>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php endif; ?>
    </main>
</div>

<?php include 'includes/footer.php'; ?>