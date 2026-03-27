<?php
$current_page = basename($_SERVER['PHP_SELF']);
if (!function_exists('isStudentLoggedIn')) {
    require_once __DIR__ . '/auth.php';
}

$staffLoggedIn   = function_exists('isStaffLoggedIn')   && isStaffLoggedIn();
$studentLoggedIn = function_exists('isStudentLoggedIn') && isStudentLoggedIn();
$adminLoggedIn   = function_exists('isAdminLoggedIn')   && isAdminLoggedIn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' — ' : '' ?>Crestfield University</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/University/css/style.css">
    <style>
        .portal-menu-wrap { position: relative; margin-left: .5rem; }
        .portal-dots-btn {
            background: rgba(255,255,255,.1);
            border: 1px solid rgba(255,255,255,.2);
            color: #fff; border-radius: var(--radius);
            width: 38px; height: 38px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; font-size: 1.4rem; transition: background .2s; flex-shrink: 0;
        }
        .portal-dots-btn:hover { background: rgba(201,150,58,.25); }
        .portal-dropdown {
            display: none; position: absolute; right: 0; top: calc(100% + 10px);
            background: #fff; border-radius: var(--radius-lg);
            box-shadow: 0 12px 40px rgba(11,29,58,.22);
            min-width: 230px; overflow: hidden; z-index: 500;
            border: 1px solid var(--border);
        }
        .portal-dropdown.open { display: block; }
        .portal-dropdown-header {
            background: var(--navy); color: rgba(255,255,255,.6);
            font-size: .68rem; font-weight: 700; letter-spacing: .1em;
            text-transform: uppercase; padding: .65rem 1.1rem;
        }
        .portal-dropdown a {
            display: flex; align-items: center; gap: .75rem;
            padding: .75rem 1.1rem; font-size: .88rem; font-weight: 500;
            color: var(--navy); border-bottom: 1px solid #f5f2ee; transition: background .15s;
        }
        .portal-dropdown a:last-child { border-bottom: none; }
        .portal-dropdown a:hover { background: #fdf9f3; }
        .portal-dropdown a .pd-icon { font-size: 1rem; width: 22px; text-align: center; }
        .portal-dropdown a .pd-label { flex: 1; }
        .portal-dropdown a .pd-arrow { font-size: .8rem; color: var(--gold); }
        .portal-dropdown a.pd-active { background: #fdf6e3; font-weight: 600; }
        .portal-dropdown a.pd-logout { color: #922b21; }
        .portal-dropdown a.pd-logout:hover { background: #fdecea; }
    </style>
</head>
<body>

<header class="site-header">
    <div class="header-inner">
        <a href="<?= BASE_URL ?>/index.php" class="logo">
            <span class="logo-crest">⬡</span>
            <span class="logo-text"><strong>Crestfield</strong> University</span>
        </a>

        <nav class="main-nav">
            <a href="<?= BASE_URL ?>/index.php"           class="<?= $current_page === 'index.php'           ? 'active' : '' ?>">Home</a>
            <a href="<?= BASE_URL ?>/index.php#about">About</a>
            <a href="<?= BASE_URL ?>/index.php#faq">FAQ</a>
            <a href="<?= BASE_URL ?>/programmes.php"      class="<?= $current_page === 'programmes.php'      ? 'active' : '' ?>">Programmes</a>
            <a href="<?= BASE_URL ?>/modules.php"         class="<?= $current_page === 'modules.php'         ? 'active' : '' ?>">Modules</a>
            <a href="<?= BASE_URL ?>/staff_directory.php" class="<?= $current_page === 'staff_directory.php' ? 'active' : '' ?>">Staff</a>
        </nav>

        <!-- 3-dot portal menu -->
        <div class="portal-menu-wrap">
            <button class="portal-dots-btn" onclick="togglePortalMenu()" aria-label="Portal menu" title="Login / My Portal">⋮</button>

            <div class="portal-dropdown" id="portal-dropdown">

                <?php if ($adminLoggedIn): ?>
                <div class="portal-dropdown-header">Admin Portal</div>
                <a href="<?= BASE_URL ?>/admin/index.php" class="pd-active">
                    <span class="pd-icon">🛡️</span><span class="pd-label">Admin Dashboard</span><span class="pd-arrow">→</span>
                </a>
                <a href="<?= BASE_URL ?>/admin/logout.php" class="pd-logout">
                    <span class="pd-icon">⏻</span><span class="pd-label">Sign Out</span>
                </a>

                <?php elseif ($staffLoggedIn): ?>
                <?php $sd = getLoggedInStaff(); ?>
                <div class="portal-dropdown-header">Staff Portal</div>
                <a href="<?= BASE_URL ?>/staff/dashboard.php" class="pd-active">
                    <span class="pd-icon">👤</span><span class="pd-label"><?= htmlspecialchars($sd['Name'] ?? 'My Portal') ?></span><span class="pd-arrow">→</span>
                </a>
                <a href="<?= BASE_URL ?>/staff/profile.php">
                    <span class="pd-icon">✏️</span><span class="pd-label">My Profile</span><span class="pd-arrow">→</span>
                </a>
                <a href="<?= BASE_URL ?>/staff/my_modules.php">
                    <span class="pd-icon">📚</span><span class="pd-label">My Modules</span><span class="pd-arrow">→</span>
                </a>
                <a href="<?= BASE_URL ?>/staff/logout.php" class="pd-logout">
                    <span class="pd-icon">⏻</span><span class="pd-label">Sign Out</span>
                </a>

                <?php elseif ($studentLoggedIn): ?>
                <?php $stu = getLoggedInStudent(); ?>
                <div class="portal-dropdown-header">Student Portal</div>
                <a href="<?= BASE_URL ?>/student/dashboard.php" class="pd-active">
                    <span class="pd-icon">🎓</span><span class="pd-label"><?= htmlspecialchars($stu['FullName'] ?? 'My Portal') ?></span><span class="pd-arrow">→</span>
                </a>
                <a href="<?= BASE_URL ?>/student/my_profile.php">
                    <span class="pd-icon">👤</span><span class="pd-label">My Profile</span><span class="pd-arrow">→</span>
                </a>
                <a href="<?= BASE_URL ?>/student/my_interests.php">
                    <span class="pd-icon">⭐</span><span class="pd-label">My Interests</span><span class="pd-arrow">→</span>
                </a>
                <a href="<?= BASE_URL ?>/student/logout.php" class="pd-logout">
                    <span class="pd-icon">⏻</span><span class="pd-label">Sign Out</span>
                </a>

                <?php else: ?>
                <a href="<?= BASE_URL ?>/my-interests.php">
    <span class="pd-icon">⭐</span>
    <span class="pd-label">View My Interests</span>
    <span class="pd-arrow">→</span>
</a>

                <div class="portal-dropdown-header">Student Access</div>
                <a href="<?= BASE_URL ?>/student/login.php">
                    <span class="pd-icon">🎓</span><span class="pd-label">Student Login</span><span class="pd-arrow">→</span>
                </a>
                
                <div class="portal-dropdown-header">Staff Access</div>
                <a href="<?= BASE_URL ?>/staff/login.php">
                    <span class="pd-icon">👔</span><span class="pd-label">Staff Login</span><span class="pd-arrow">→</span>
                </a>
                <div class="portal-dropdown-header">Admin Access</div>
                <a href="<?= BASE_URL ?>/admin/login.php">
                    <span class="pd-icon">🛡️</span><span class="pd-label">Admin Login</span><span class="pd-arrow">→</span>
                </a>
                <?php endif; ?>

            </div>
        </div>

        <button class="nav-toggle" onclick="document.querySelector('.main-nav').classList.toggle('open')">☰</button>
    </div>
</header>

<script>
function togglePortalMenu() {
    document.getElementById('portal-dropdown').classList.toggle('open');
}
document.addEventListener('click', function(e) {
    var wrap = document.querySelector('.portal-menu-wrap');
    if (wrap && !wrap.contains(e.target)) {
        document.getElementById('portal-dropdown').classList.remove('open');
    }
});
</script>
