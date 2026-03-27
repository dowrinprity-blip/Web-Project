<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

setSecurityHeaders();

$upload_url = BASE_URL . '/uploads/staff_photos/';

$staff_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($staff_id <= 0) {
    header('Location: staff_portal.php');
    exit;
}

// Get staff details
$staff = null;
$stmt = $conn->prepare("
    SELECT s.*, sa.Bio AS AccountBio, sa.PhotoPath 
    FROM Staff s
    LEFT JOIN StaffAccounts sa ON s.StaffID = sa.StaffID
    WHERE s.StaffID = ?
");
$stmt->bind_param('i', $staff_id);
$stmt->execute();
$staff = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$staff) {
    header('Location: staff_portal.php');
    exit;
}

// Get modules this staff leads
$modules = [];
$stmt = $conn->prepare("
    SELECT m.*, 
           GROUP_CONCAT(DISTINCT p.ProgrammeName ORDER BY p.ProgrammeName SEPARATOR ', ') AS Programmes
    FROM Modules m
    LEFT JOIN ProgrammeModules pm ON m.ModuleID = pm.ModuleID
    LEFT JOIN Programmes p ON pm.ProgrammeID = p.ProgrammeID
    WHERE m.ModuleLeaderID = ?
    GROUP BY m.ModuleID
    ORDER BY m.ModuleName
");
$stmt->bind_param('i', $staff_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $modules[] = $row;
}
$stmt->close();

// Get programmes this staff leads
$programmes = [];
$stmt = $conn->prepare("
    SELECT p.*, l.LevelName
    FROM Programmes p
    JOIN Levels l ON p.LevelID = l.LevelID
    WHERE p.ProgrammeLeaderID = ?
    ORDER BY p.ProgrammeName
");
$stmt->bind_param('i', $staff_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $programmes[] = $row;
}
$stmt->close();

$page_title = $staff['Name'];
include 'includes/header.php';

// Get initials
$initials = '';
$nameParts = explode(' ', $staff['Name']);
foreach ($nameParts as $part) {
    if (!empty($part) && ctype_alpha($part[0])) {
        $initials .= $part[0];
    }
}
$initials = strtoupper(substr($initials, 0, 2));

// FIXED: Check if photo exists
$hasPhoto = !empty($staff['Photo']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/University/uploads/staff_photos/' . $staff['Photo']);
$photoSrc = $upload_url . rawurlencode($staff['Photo'] ?? '');
?>

<style>
    .staff-profile {
        max-width: 900px;
        margin: 2rem auto;
        background: #fff;
        border-radius: var(--radius-lg);
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    .staff-profile-header {
        background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
        padding: 2rem;
        text-align: center;
        color: white;
    }
    .staff-profile-avatar {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        margin: 0 auto 1rem;
        background: var(--gold);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 4rem;
        font-weight: bold;
        color: var(--navy);
        overflow: hidden;
        border: 4px solid var(--gold);
    }
    .staff-profile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .staff-profile-name {
        font-family: 'Playfair Display', serif;
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }
    .staff-profile-title {
        font-size: 0.9rem;
        opacity: 0.8;
    }
    .staff-profile-content {
        padding: 2rem;
    }
    .section-title {
        font-family: 'Playfair Display', serif;
        font-size: 1.2rem;
        color: var(--navy);
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid var(--gold);
    }
    .bio-text {
        line-height: 1.8;
        color: var(--text-muted);
        margin-bottom: 1.5rem;
    }
    .module-item {
        padding: 0.75rem;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        margin-bottom: 0.5rem;
        transition: all 0.2s;
    }
    .module-item:hover {
        background: var(--cream);
        border-color: var(--gold);
    }
    .module-name {
        font-weight: 600;
        color: var(--navy);
        margin-bottom: 0.25rem;
    }
    .module-desc {
        font-size: 0.8rem;
        color: var(--text-muted);
    }
    .back-link {
        display: inline-block;
        margin-bottom: 1rem;
        color: var(--gold);
        text-decoration: none;
    }
    .back-link:hover {
        text-decoration: underline;
    }
</style>

<div class="staff-profile">
    <div class="staff-profile-header">
        <div class="staff-profile-avatar">
            <?php if ($hasPhoto): ?>
                <img src="<?= htmlspecialchars($photoSrc) ?>" alt="<?= htmlspecialchars($staff['Name']) ?>">
            <?php else: ?>
                <?= htmlspecialchars($initials) ?>
            <?php endif; ?>
        </div>
        <h1 class="staff-profile-name"><?= htmlspecialchars($staff['Name']) ?></h1>
        <div class="staff-profile-title">
            <?php 
            $titles = [];
            if (count($modules) > 0) $titles[] = 'Module Leader (' . count($modules) . ' modules)';
            if (count($programmes) > 0) $titles[] = 'Programme Leader (' . count($programmes) . ' programmes)';
            echo $titles ? implode(' | ', $titles) : 'Academic Staff';
            ?>
        </div>
    </div>
    
    <div class="staff-profile-content">
        <a href="staff_portal.php" class="back-link">← Back to Staff Directory</a>
        
        <?php if (!empty($staff['Bio']) || !empty($staff['AccountBio'])): ?>
            <h2 class="section-title">About</h2>
            <div class="bio-text">
                <?= nl2br(htmlspecialchars($staff['Bio'] ?? $staff['AccountBio'])) ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($modules)): ?>
            <h2 class="section-title">📚 Modules Led</h2>
            <?php foreach ($modules as $mod): ?>
                <div class="module-item">
                    <div class="module-name">
                        <a href="module_detail.php?id=<?= (int)$mod['ModuleID'] ?>" style="color: var(--navy); text-decoration: none;">
                            <?= htmlspecialchars($mod['ModuleName']) ?>
                        </a>
                    </div>
                    <?php if (!empty($mod['Description'])): ?>
                        <div class="module-desc"><?= htmlspecialchars(substr($mod['Description'], 0, 120)) ?>…</div>
                    <?php endif; ?>
                    <?php if (!empty($mod['Programmes'])): ?>
                        <div style="font-size: 0.7rem; color: var(--gold); margin-top: 0.25rem;">
                            Part of: <?= htmlspecialchars($mod['Programmes']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if (!empty($programmes)): ?>
            <h2 class="section-title">🎓 Programmes Led</h2>
            <?php foreach ($programmes as $prog): ?>
                <div class="module-item">
                    <div class="module-name">
                        <a href="programme_detail.php?id=<?= (int)$prog['ProgrammeID'] ?>" style="color: var(--navy); text-decoration: none;">
                            <?= htmlspecialchars($prog['ProgrammeName']) ?>
                        </a>
                    </div>
                    <div class="module-desc">Level: <?= htmlspecialchars($prog['LevelName']) ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>