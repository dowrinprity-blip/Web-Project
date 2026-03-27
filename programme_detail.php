<?php
require_once 'includes/db.php';
require_once 'includes/auth.php'; // For security headers and functions

// Set security headers
setSecurityHeaders();

// Validate and sanitize ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { 
    header('Location: programmes.php'); 
    exit; 
}

// Fetch programme with prepared statement
$prog = null;
$stmt = $conn->prepare("
    SELECT p.*, l.LevelName, s.Name AS LeaderName, s.Photo AS LeaderPhoto, s.Bio AS LeaderBio
    FROM Programmes p
    JOIN Levels l ON p.LevelID = l.LevelID
    LEFT JOIN Staff s ON p.ProgrammeLeaderID = s.StaffID
    WHERE p.ProgrammeID = ?
");
$stmt->bind_param('i', $id);
$stmt->execute();
$prog = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Check if programme exists
if (!$prog) { 
    header('Location: programmes.php'); 
    exit; 
}

$page_title = htmlspecialchars($prog['ProgrammeName']);

// Fetch modules grouped by year with prepared statement
$modules_by_year = [];
$stmt = $conn->prepare("
    SELECT m.ModuleID, m.ModuleName, m.Description, s.Name AS LeaderName, s.Photo AS LeaderPhoto, pm.Year
    FROM ProgrammeModules pm
    JOIN Modules m ON pm.ModuleID = m.ModuleID
    LEFT JOIN Staff s ON m.ModuleLeaderID = s.StaffID
    WHERE pm.ProgrammeID = ?
    ORDER BY pm.Year, m.ModuleName
");
$stmt->bind_param('i', $id);
$stmt->execute();
$modules_result = $stmt->get_result();

while ($mod = $modules_result->fetch_assoc()) {
    $modules_by_year[$mod['Year']][] = $mod;
}
$stmt->close();

// Module count
$total_mods = array_sum(array_map('count', $modules_by_year));

// Interested students count with prepared statement
$interested = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as c FROM InterestedStudents WHERE ProgrammeID = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $interested = (int)$row['c'];
}
$stmt->close();

// Generate CSRF token for register interest link (if needed)
$csrf_token = generateCsrfToken();

include 'includes/header.php';
?>

<div class="page-hero">
    <div class="page-hero-inner">
        <div class="breadcrumb">
            <a href="<?= htmlspecialchars(BASE_URL) ?>/index.php">Home</a> /
            <a href="<?= htmlspecialchars(BASE_URL) ?>/programmes.php">Programmes</a> /
            <?= htmlspecialchars($prog['ProgrammeName']) ?>
        </div>
        <span class="card-badge <?= $prog['LevelName'] === 'Postgraduate' ? 'pg' : '' ?>" style="margin-bottom:.75rem;display:inline-block;">
            <?= htmlspecialchars($prog['LevelName']) ?>
        </span>
        <h1><?= htmlspecialchars($prog['ProgrammeName']) ?></h1>
        
        <?php if (!empty($prog['LeaderName'])): ?>
        <div class="staff-chip">
            <?php
            // Generate leader initials safely
            $lInits = '';
            $nameParts = explode(' ', $prog['LeaderName']);
            foreach ($nameParts as $word) {
                if (!empty($word) && ctype_alpha($word[0])) {
                    $lInits .= $word[0];
                }
            }
            $lInits = strtoupper(substr($lInits, 0, 2));
            ?>
            <div class="staff-chip-avatar">
                <?php if (!empty($prog['LeaderPhoto'])): ?>
                    <img src="<?= htmlspecialchars(BASE_URL) ?>/uploads/staff_photos/<?= htmlspecialchars(rawurlencode($prog['LeaderPhoto'])) ?>"
                         alt="<?= htmlspecialchars($prog['LeaderName']) ?>"
                         onerror="this.parentNode.textContent='<?= htmlspecialchars($lInits) ?>'">
                <?php else: ?>
                    <?= htmlspecialchars($lInits) ?>
                <?php endif; ?>
            </div>
            <span>Programme Leader: <strong><?= htmlspecialchars($prog['LeaderName']) ?></strong></span>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="detail-layout">
    <!-- MAIN CONTENT -->
    <div class="detail-main">
        <h2>About This Programme</h2>
        <p><?= nl2br(htmlspecialchars($prog['Description'] ?? 'No description available.')) ?></p>

        <h2>Modules</h2>
        <?php if (empty($modules_by_year)): ?>
            <p style="color:var(--text-muted);">No modules assigned to this programme yet.</p>
        <?php else: ?>
            <?php foreach ($modules_by_year as $year => $mods): ?>
            <div class="module-year">
                <div class="year-label">
                    <?php if ($prog['LevelName'] === 'Postgraduate'): ?>
                        📚 Programme Modules
                    <?php else: ?>
                        📅 Year <?= (int)$year ?>
                    <?php endif; ?>
                </div>
                <div class="module-pill-list">
                    <?php foreach ($mods as $mod):
                        // Generate module leader initials safely
                        $mInits = '';
                        if (!empty($mod['LeaderName'])) {
                            $mNameParts = explode(' ', $mod['LeaderName']);
                            foreach ($mNameParts as $word) {
                                if (!empty($word) && ctype_alpha($word[0])) {
                                    $mInits .= $word[0];
                                }
                            }
                            $mInits = strtoupper(substr($mInits, 0, 2));
                        }
                    ?>
                    <a href="<?= htmlspecialchars(BASE_URL) ?>/module_detail.php?id=<?= (int)$mod['ModuleID'] ?>" class="module-pill">
                        <div>
                            <div class="module-pill-name"><?= htmlspecialchars($mod['ModuleName']) ?></div>
                            <div class="module-pill-leader" style="display:flex;align-items:center;gap:.4rem;">
                                <div class="mini-avatar" style="width:24px;height:24px;border-radius:50%;background:var(--gold);color:var(--navy);display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:bold;overflow:hidden;">
                                    <?php if (!empty($mod['LeaderPhoto'])): ?>
                                        <img src="<?= htmlspecialchars(BASE_URL) ?>/uploads/staff_photos/<?= htmlspecialchars(rawurlencode($mod['LeaderPhoto'])) ?>"
                                             alt="" style="width:100%;height:100%;object-fit:cover;"
                                             onerror="this.parentNode.textContent='<?= htmlspecialchars($mInits) ?>'">
                                    <?php else: ?>
                                        <?= htmlspecialchars($mInits ?: '?') ?>
                                    <?php endif; ?>
                                </div>
                                <?= htmlspecialchars($mod['LeaderName'] ?? 'TBC') ?>
                            </div>
                        </div>
                        <span style="color:var(--gold);font-size:.9rem;">→</span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- SIDEBAR -->
    <div class="detail-sidebar">
        <div class="sidebar-card">
            <h3>Programme Info</h3>
            <div class="sidebar-info">
                <div class="sidebar-row">
                    <span class="sidebar-icon">🎓</span>
                    <div>
                        <div class="sidebar-row-label">Award Level</div>
                        <div class="sidebar-row-val"><?= htmlspecialchars($prog['LevelName']) ?></div>
                    </div>
                </div>
                <div class="sidebar-row">
                    <span class="sidebar-icon">
                        <?php if (!empty($prog['LeaderPhoto'])): ?>
                        <div style="width:28px;height:28px;border-radius:50%;overflow:hidden;border:1.5px solid var(--gold);">
                            <img src="<?= htmlspecialchars(BASE_URL) ?>/uploads/staff_photos/<?= htmlspecialchars(rawurlencode($prog['LeaderPhoto'])) ?>"
                                 alt="" style="width:100%;height:100%;object-fit:cover;"
                                 onerror="this.style.display='none'">
                        </div>
                        <?php else: ?>
                        👤
                        <?php endif; ?>
                    </span>
                    <div>
                        <div class="sidebar-row-label">Programme Leader</div>
                        <div class="sidebar-row-val"><?= htmlspecialchars($prog['LeaderName'] ?? 'TBC') ?></div>
                    </div>
                </div>
                <div class="sidebar-row">
                    <span class="sidebar-icon">📚</span>
                    <div>
                        <div class="sidebar-row-label">Total Modules</div>
                        <div class="sidebar-row-val"><?= (int)$total_mods ?> modules</div>
                    </div>
                </div>
                <div class="sidebar-row">
                    <span class="sidebar-icon">⏱️</span>
                    <div>
                        <div class="sidebar-row-label">Duration</div>
                        <div class="sidebar-row-val"><?= $prog['LevelName'] === 'Postgraduate' ? '1 Year' : '3 Years' ?> Full-time</div>
                    </div>
                </div>
                <div class="sidebar-row">
                    <span class="sidebar-icon">❤️</span>
                    <div>
                        <div class="sidebar-row-label">Interested Students</div>
                        <div class="sidebar-row-val"><?= (int)$interested ?> registered</div>
                    </div>
                </div>
            </div>
            <!-- SECURE Register Interest Link - Use POST form for better security -->
            <form method="POST" action="<?= htmlspecialchars(BASE_URL) ?>/register_interest.php" style="display:inline;width:100%;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <input type="hidden" name="programme_id" value="<?= (int)$id ?>">
                <button type="submit" class="btn btn-gold" style="width:100%;cursor:pointer;">Register Interest →</button>
            </form>
            <a href="<?= htmlspecialchars(BASE_URL) ?>/programmes.php" class="btn btn-outline" style="width:100%;justify-content:center;margin-top:.5rem;">← All Programmes</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>