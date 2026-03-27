<?php
// Add this at the VERY TOP of layout.php
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/University');
}

// Set security headers if not already set
if (!headers_sent()) {
    header('X-XSS-Protection: 1; mode=block');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

function staffHead($title) { 
    // Generate CSRF token for logout form
    $csrf_token = generateCsrfToken();
    $_SESSION['staff_layout_csrf'] = $csrf_token;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> — Crestfield Staff</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/University/css/style.css">
</head>
<body>
<?php }

function staffSidebar($active, $staffData) {
    // Get initials
    $parts = explode(' ', $staffData['Name'] ?? 'S');
    $initials = '';
    foreach ($parts as $w) if (!empty($w) && ctype_alpha($w[0])) $initials .= $w[0];
    $initials = strtoupper(substr($initials, 0, 2));
    
    // Get CSRF token for logout
    $csrf_token = generateCsrfToken();
    ?>
<div class="staff-wrap">
<aside class="staff-sidebar">
    <div class="sidebar-brand">
        <span class="crest">⬡</span>
        <div class="sidebar-brand-text">
            <strong>Crestfield</strong>
            <small>Staff Portal</small>
        </div>
    </div>

    <!-- Staff profile chip -->
    <div class="sidebar-profile">
        <div class="sidebar-avatar">
            <?php
            // Prefer Staff.Photo (set by admin), fall back to StaffAccounts.PhotoPath (self-uploaded)
            $sidebarPhoto = '';
            if (!empty($staffData['Photo'])) {
                $sidebarPhoto = '/university/uploads/staff_photos/' . rawurlencode($staffData['Photo']);
            } elseif (!empty($staffData['PhotoPath'])) {
                $sidebarPhoto = $staffData['PhotoPath'];
            }
            ?>
            <?php if ($sidebarPhoto): ?>
                <img src="<?= htmlspecialchars($sidebarPhoto) ?>" alt="<?= htmlspecialchars($staffData['Name']) ?>"
                     onerror="this.parentNode.textContent='<?= htmlspecialchars($initials) ?>'">
            <?php else: ?>
                <?= htmlspecialchars($initials) ?>
            <?php endif; ?>
        </div>
        <div>
            <div class="sidebar-profile-name"><?= htmlspecialchars($staffData['Name']) ?></div>
            <div class="sidebar-profile-role">@<?= htmlspecialchars($staffData['Username']) ?></div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="sidebar-section">Menu</div>
        <a href="<?= BASE_URL ?>/staff/dashboard.php" class="<?= $active==='dashboard'?'active':'' ?>">
            <span class="icon">📊</span> Dashboard
        </a>
        <a href="<?= BASE_URL ?>/staff_profile.php?id=<?= (int)$staffData['StaffID'] ?>" class="<?= $active==='profile'?'active':'' ?>">
    <span class="icon">👤</span> My Profile
</a>
        <a href="<?= BASE_URL ?>/staff/my_modules.php" class="<?= $active==='modules'?'active':'' ?>">
            <span class="icon">📚</span> My Modules
        </a>
        <a href="<?= BASE_URL ?>/staff/my_programmes.php" class="<?= $active==='programmes'?'active':'' ?>">
            <span class="icon">🎓</span> My Programmes
        </a>
        <a href="<?= BASE_URL ?>/staff/requests.php" class="<?= $active==='requests'?'active':'' ?>">
            <span class="icon">📝</span> My Requests
        </a>
        <div class="sidebar-section">Site</div>
        <a href="<?= BASE_URL ?>/index.php" target="_blank">
            <span class="icon">🌐</span> View Public Site
        </a>
    </nav>
    <div class="sidebar-footer">
        <!-- SECURE LOGOUT FORM - POST with CSRF protection -->
        <form method="POST" action="<?= BASE_URL ?>/staff/logout.php" style="margin:0;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <button type="submit" style="background:none;border:none;color:rgba(255,255,255,.5);cursor:pointer;display:flex;align-items:center;gap:.5rem;font-size:.82rem;width:100%;padding:0;">
                <span>⏻</span> Sign Out
            </button>
        </form>
    </div>
</aside>
<div class="staff-body">
<?php }

function staffTopbar($title, $staffData = null) { 
    if ($staffData === null) {
        $staffData = getLoggedInStaff();
    }
    $staffId = isset($staffData['StaffID']) ? (int)$staffData['StaffID'] : 0;
?>
<div class="staff-topbar">
    <h1><?= htmlspecialchars($title) ?></h1>
    <a href="<?= BASE_URL ?>/staff_profile.php?id=<?= $staffId ?>" class="btn btn-sm btn-navy" style="font-size:.8rem;">Edit Profile</a>
</div>
<div class="staff-content">
<?php }

function staffFooter() { ?>
</div><!-- staff-content -->
</div><!-- staff-body -->
</div><!-- staff-wrap -->
</body>
</html>
<?php }
?>