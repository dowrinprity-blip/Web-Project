<?php
require_once 'includes/db.php';
require_once 'includes/auth.php'; // For security headers and functions

// Set security headers
setSecurityHeaders();

// Validate and sanitize ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { 
    header('Location: modules.php'); 
    exit; 
}

// Fetch module with prepared statement
$mod = null;
$stmt = $conn->prepare("
    SELECT m.*, s.Name AS LeaderName, s.Photo AS LeaderPhoto, s.Bio AS LeaderBio
    FROM Modules m
    LEFT JOIN Staff s ON m.ModuleLeaderID = s.StaffID
    WHERE m.ModuleID = ?
");
$stmt->bind_param('i', $id);
$stmt->execute();
$mod = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Check if module exists
if (!$mod) { 
    header('Location: modules.php'); 
    exit; 
}

$page_title = htmlspecialchars($mod['ModuleName']);

// Get programmes that include this module with prepared statement
$programmes = [];
$stmt = $conn->prepare("
    SELECT p.ProgrammeID, p.ProgrammeName, l.LevelName, pm.Year
    FROM ProgrammeModules pm
    JOIN Programmes p ON pm.ProgrammeID = p.ProgrammeID
    JOIN Levels l ON p.LevelID = l.LevelID
    WHERE pm.ModuleID = ?
    ORDER BY l.LevelID, p.ProgrammeName
");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $programmes[] = $row;
}
$stmt->close();

// Get leader initials safely
$initials = '';
if (!empty($mod['LeaderName'])) {
    $nameParts = explode(' ', $mod['LeaderName']);
    foreach ($nameParts as $word) {
        if (!empty($word) && ctype_alpha($word[0])) {
            $initials .= $word[0];
        }
    }
    $initials = strtoupper(substr($initials, 0, 2));
}

// Generate safe module code
$moduleCode = 'CREST' . str_pad((string)$mod['ModuleID'], 4, '0', STR_PAD_LEFT);

include 'includes/header.php';
?>

<div class="page-hero">
    <div class="page-hero-inner">
        <div class="breadcrumb">
            <a href="<?= htmlspecialchars(BASE_URL) ?>/index.php">Home</a> /
            <a href="<?= htmlspecialchars(BASE_URL) ?>/modules.php">Modules</a> /
            <?= htmlspecialchars($mod['ModuleName']) ?>
        </div>
        <span class="card-badge" style="margin-bottom:.75rem;display:inline-block;">Module</span>
        <h1><?= htmlspecialchars($mod['ModuleName']) ?></h1>
        <p>Module ID: <?= htmlspecialchars($moduleCode) ?></p>
    </div>
</div>

<div class="detail-layout">
    <!-- MAIN -->
    <div class="detail-main">
        <?php if (!empty($mod['LeaderName'])): ?>
        <div class="module-leader-chip">
            <div class="leader-avatar">
                <?php if (!empty($mod['LeaderPhoto'])): ?>
                    <img src="<?= htmlspecialchars(BASE_URL) ?>/uploads/staff_photos/<?= htmlspecialchars(rawurlencode($mod['LeaderPhoto'])) ?>"
                         alt="<?= htmlspecialchars($mod['LeaderName']) ?>"
                         onerror="this.parentNode.textContent='<?= htmlspecialchars($initials) ?>'">
                <?php else: ?>
                    <?= htmlspecialchars($initials) ?>
                <?php endif; ?>
            </div>
            <span><strong>Module Leader:</strong> <?= htmlspecialchars($mod['LeaderName']) ?></span>
        </div>
        <?php if (!empty($mod['LeaderBio'])): ?>
        <p style="font-size:.85rem;color:var(--text-muted);margin-bottom:1.5rem;padding-left:.4rem;border-left:3px solid var(--gold);padding-left:.75rem;">
            <?= htmlspecialchars($mod['LeaderBio']) ?>
        </p>
        <?php endif; ?>
        <?php endif; ?>

        <h2>Module Overview</h2>
        <p><?= nl2br(htmlspecialchars($mod['Description'] ?? 'No description available for this module.')) ?></p>

        <h2>Part of These Programmes</h2>
        <?php if (empty($programmes)): ?>
            <p style="color:var(--text-muted);">This module is not currently assigned to any programme.</p>
        <?php else: ?>
        <div class="module-pill-list">
            <?php foreach ($programmes as $prog): ?>
            <a href="<?= htmlspecialchars(BASE_URL) ?>/programme_detail.php?id=<?= (int)$prog['ProgrammeID'] ?>" class="module-pill">
                <div>
                    <div class="module-pill-name"><?= htmlspecialchars($prog['ProgrammeName']) ?></div>
                    <div class="module-pill-leader">
                        <?= htmlspecialchars($prog['LevelName']) ?>
                        <?php if (!empty($prog['Year'])): ?> · Year <?= (int)$prog['Year'] ?><?php endif; ?>
                    </div>
                </div>
                <span style="color:var(--gold);">→</span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- SIDEBAR -->
    <div class="detail-sidebar">
        <div class="sidebar-card">
            <h3>Module Details</h3>
            <div class="sidebar-info">
                <div class="sidebar-row">
                    <span class="sidebar-icon">🔢</span>
                    <div>
                        <div class="sidebar-row-label">Module Code</div>
                        <div class="sidebar-row-val"><?= htmlspecialchars($moduleCode) ?></div>
                    </div>
                </div>
                <div class="sidebar-row">
                    <span class="sidebar-icon">
                        <?php if (!empty($mod['LeaderPhoto'])): ?>
                        <div style="width:28px;height:28px;border-radius:50%;overflow:hidden;border:1.5px solid var(--gold);">
                            <img src="<?= htmlspecialchars(BASE_URL) ?>/uploads/staff_photos/<?= htmlspecialchars(rawurlencode($mod['LeaderPhoto'])) ?>"
                                 alt="" style="width:100%;height:100%;object-fit:cover;"
                                 onerror="this.style.display='none'">
                        </div>
                        <?php else: ?>
                        👤
                        <?php endif; ?>
                    </span>
                    <div>
                        <div class="sidebar-row-label">Module Leader</div>
                        <div class="sidebar-row-val"><?= htmlspecialchars($mod['LeaderName'] ?? 'TBC') ?></div>
                    </div>
                </div>
                <div class="sidebar-row">
                    <span class="sidebar-icon">📋</span>
                    <div>
                        <div class="sidebar-row-label">Credits</div>
                        <div class="sidebar-row-val">20 Credits</div>
                    </div>
                </div>
                <div class="sidebar-row">
                    <span class="sidebar-icon">📅</span>
                    <div>
                        <div class="sidebar-row-label">Delivery</div>
                        <div class="sidebar-row-val">Full Academic Year</div>
                    </div>
                </div>
            </div>
            <a href="<?= htmlspecialchars(BASE_URL) ?>/modules.php" class="btn btn-outline" style="width:100%;justify-content:center;">← All Modules</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>