<?php
function adminHead($title) { ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> — Crestfield Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/University/css/style.css">
    
    <style>
        /* ── ADMIN LAYOUT ── */
        body { background: #f0ede8; }

        .admin-wrap { display: flex; min-height: 100vh; }

        .admin-sidebar {
            width: 250px; 
            height: 100vh;
            background: var(--navy);
            border-right: 3px solid var(--gold);
            display: flex; 
            flex-direction: column;
            position: fixed; 
            top: 0; 
            left: 0; 
            z-index: 200;
            overflow-y: auto;
            overflow-x: hidden;
        }
        
        /* Custom scrollbar styling */
        .admin-sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        .admin-sidebar::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.1);
        }
        
        .admin-sidebar::-webkit-scrollbar-thumb {
            background: var(--gold);
            border-radius: 3px;
        }
        
        .admin-sidebar::-webkit-scrollbar-thumb:hover {
            background: var(--gold-light);
        }

        .sidebar-brand {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,.1);
            display: flex; align-items: center; gap: .6rem;
        }
        .sidebar-brand .crest { font-size: 1.6rem; color: var(--gold); }
        .sidebar-brand-text { color: #fff; }
        .sidebar-brand-text strong { font-family: 'Playfair Display', serif; font-size: 1rem; display: block; }
        .sidebar-brand-text small { font-size: .72rem; color: rgba(255,255,255,.45); letter-spacing: .06em; text-transform: uppercase; }

        .sidebar-nav { flex: 1; padding: 1rem 0; }
        .sidebar-section {
            font-size: .68rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase;
            color: rgba(255,255,255,.3); padding: 1rem 1.5rem .4rem;
        }
        .sidebar-nav a {
            display: flex; align-items: center; gap: .75rem;
            padding: .65rem 1.5rem;
            color: rgba(255,255,255,.7);
            font-size: .875rem; font-weight: 500;
            border-left: 3px solid transparent;
            transition: all .2s;
            text-decoration: none;
        }
        .sidebar-nav a:hover { color: #fff; background: rgba(201,150,58,.12); }
        .sidebar-nav a.active { color: #fff; background: rgba(201,150,58,.15); border-left-color: var(--gold); }
        .sidebar-nav .icon { font-size: 1rem; width: 20px; text-align: center; }

        .sidebar-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid rgba(255,255,255,.1);
            margin-top: auto;
        }
        .sidebar-footer button {
            background: none;
            border: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: .5rem;
            font-size: .82rem;
            color: rgba(255,255,255,.5);
            transition: color .2s;
            padding: 0;
        }
        .sidebar-footer button:hover { color: var(--gold-light); }

        .admin-body { margin-left: 250px; flex: 1; display: flex; flex-direction: column; }

        .admin-topbar {
            background: #fff; border-bottom: 1px solid var(--border);
            padding: 1rem 2rem;
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 100;
        }
        .admin-topbar h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem; color: var(--navy);
        }
        .admin-user {
            display: flex; align-items: center; gap: .75rem;
            font-size: .85rem; color: var(--text-muted);
        }
        .admin-avatar {
            width: 32px; height: 32px; border-radius: 50%;
            background: var(--navy); color: var(--gold);
            display: flex; align-items: center; justify-content: center;
            font-family: 'Playfair Display', serif; font-size: .85rem; font-weight: 700;
        }

        .admin-content { padding: 2rem; flex: 1; }

        /* ── STAT CARDS ── */
        .stat-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px,1fr)); gap: 1rem; margin-bottom: 2rem; }
        .stat-card {
            background: #fff; border-radius: var(--radius-lg);
            padding: 1.5rem; border-left: 4px solid var(--gold);
            box-shadow: 0 2px 12px var(--shadow);
        }
        .stat-card-num { font-family: 'Playfair Display', serif; font-size: 2rem; font-weight: 900; color: var(--navy); line-height: 1; }
        .stat-card-label { font-size: .75rem; text-transform: uppercase; letter-spacing: .08em; color: var(--text-muted); font-weight: 600; margin-top: .3rem; }

        /* ── ADMIN TABLE ── */
        .admin-table-wrap { background: #fff; border-radius: var(--radius-lg); box-shadow: 0 2px 12px var(--shadow); overflow: hidden; }
        .admin-table { width: 100%; border-collapse: collapse; font-size: .875rem; }
        .admin-table th {
            background: var(--navy); color: var(--gold-light);
            padding: .85rem 1.2rem; text-align: left;
            font-size: .72rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase;
        }
        .admin-table td { padding: .8rem 1.2rem; border-bottom: 1px solid #f5f2ee; vertical-align: middle; }
        .admin-table tr:last-child td { border-bottom: none; }
        .admin-table tr:hover td { background: #fdfaf6; }

        /* ── BADGES ── */
        .badge {
            display: inline-block; padding: .2rem .65rem; border-radius: 99px;
            font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em;
        }
        .badge-ug { background: var(--cream); color: var(--navy); border: 1px solid var(--border); }
        .badge-pg { background: #f0ebe0; color: #7a5c1e; border: 1px solid #d4b888; }

        /* ── ADMIN FORM ── */
        .admin-form-wrap { background: #fff; border-radius: var(--radius-lg); padding: 2rem; box-shadow: 0 2px 12px var(--shadow); max-width: 780px; }
        .admin-form-wrap h2 { font-size: 1.3rem; color: var(--navy); padding-bottom: 1rem; border-bottom: 1px solid var(--border); margin-bottom: 1.5rem; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .form-group { margin-bottom: 1.2rem; }
        .form-group label { display: block; font-size: .82rem; font-weight: 600; color: var(--navy); margin-bottom: .4rem; text-transform: uppercase; letter-spacing: .04em; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: .65rem 1rem;
            border: 1.5px solid var(--border); border-radius: var(--radius);
            font-size: .9rem; font-family: 'DM Sans', sans-serif;
            background: var(--cream); color: var(--text);
            outline: none; transition: border-color .2s;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: var(--gold); background: #fff; }

        /* ── ALERT ── */
        .alert { padding: .9rem 1.2rem; border-radius: var(--radius); font-size: .88rem; margin-bottom: 1.2rem; }
        .alert-success { background: #eaf7f0; border: 1px solid #b2dfc7; color: #1a6641; }
        .alert-error   { background: #fdecea; border: 1px solid #f5b7b1; color: #922b21; }
        .alert-warning { background: #fff3cd; border: 1px solid #ffeeba; color: #856404; }
        .alert-info    { background: #eaf0fb; border: 1px solid #b2c8f0; color: #1a3a7a; }

        /* ── ACTION BTNS ── */
        .btn-edit { background: var(--navy); color: #fff; }
        .btn-edit:hover { background: var(--navy-mid); }
        .btn-del { background: #c0392b; color: #fff; }
        .btn-del:hover { background: #a93226; }
        .btn-add { background: var(--gold); color: #fff; }
        .btn-add:hover { background: var(--gold-light); }
        .btn-success { background: #1a6641; color: #fff; }
        .btn-success:hover { background: #155234; }

        @media (max-width: 768px) {
            .admin-sidebar { width: 200px; }
            .admin-body { margin-left: 200px; }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<?php }

function adminSidebar($active = '') { 
    // Get pending requests count using prepared statement (SECURE)
    $pendingCount = 0;
    global $conn;
    
    if (isset($conn) && $conn) {
        $stmt = $conn->prepare("SELECT COUNT(*) as c FROM StaffChangeRequests WHERE Status = 'pending'");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $pendingCount = (int)$row['c'];
            }
            $stmt->close();
        }
    }
    
    // Generate CSRF token for logout form
    $csrf_token = generateCsrfToken();
?>
<div class="admin-wrap">
<aside class="admin-sidebar">
    <div class="sidebar-brand">
        <span class="crest">⬡</span>
        <div class="sidebar-brand-text">
            <strong>Crestfield</strong>
            <small>Admin Portal</small>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="sidebar-section">Main</div>
        <a href="<?= BASE_URL ?>/admin/index.php" class="<?= $active==='dashboard' ? 'active' : '' ?>">
            <span class="icon">📊</span> Dashboard
        </a>

        <div class="sidebar-section">Manage</div>
        <a href="<?= BASE_URL ?>/admin/programmes.php" class="<?= $active==='programmes' ? 'active' : '' ?>">
            <span class="icon">🎓</span> Programmes
        </a>
        <a href="<?= BASE_URL ?>/admin/modules.php" class="<?= $active==='modules' ? 'active' : '' ?>">
            <span class="icon">📚</span> Modules
        </a>
        <a href="<?= BASE_URL ?>/admin/staff.php" class="<?= $active==='staff' ? 'active' : '' ?>">
            <span class="icon">👤</span> Staff
        </a>

        <div class="sidebar-section">Students</div>
        <a href="<?= BASE_URL ?>/admin/students.php" class="<?= $active==='students' ? 'active' : '' ?>">
            <span class="icon">📋</span> Interested Students
        </a>
        <a href="<?= BASE_URL ?>/admin/student_accounts.php" class="<?= $active==='student_accounts' ? 'active' : '' ?>">
            <span class="icon">👥</span> Student Accounts
        </a>
        <a href="<?= BASE_URL ?>/admin/student_enrollment.php" class="<?= $active==='student_enrollment' ? 'active' : '' ?>">
            <span class="icon">🎓</span> Student Enrollment
        </a>

        <div class="sidebar-section">Staff</div>
        <a href="<?= BASE_URL ?>/admin/staff_accounts.php" class="<?= $active==='staff_accounts' ? 'active' : '' ?>">
            <span class="icon">🔑</span> Staff Accounts
        </a>
        <a href="<?= BASE_URL ?>/admin/requests.php" class="<?= $active==='requests' ? 'active' : '' ?>">
            <span class="icon">📝</span> Change Requests
            <?php if ($pendingCount > 0): ?>
                <span style="margin-left:auto;background:#c0392b;color:#fff;border-radius:99px;padding:.1rem .55rem;font-size:.7rem;font-weight:700;">
                    <?= (int)$pendingCount ?>
                </span>
            <?php endif; ?>
        </a>

        <div class="sidebar-section">Site</div>
        <a href="<?= BASE_URL ?>/index.php" target="_blank">
            <span class="icon">🌐</span> View Student Site
        </a>
    </nav>
    <div class="sidebar-footer">
        <form method="POST" action="<?= BASE_URL ?>/admin/logout.php" style="margin:0;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <button type="submit">
                <span>⏻</span> Sign Out
            </button>
        </form>
    </div>
</aside>
<div class="admin-body">
<?php }

function adminTopbar($title) { ?>
<div class="admin-topbar">
    <h1><?= htmlspecialchars($title) ?></h1>
    <div class="admin-user">
        <div class="admin-avatar">A</div>
        <span>Administrator</span>
    </div>
</div>
<div class="admin-content">
<?php }

function adminFooter() { ?>
</div><!-- admin-content -->
</div><!-- admin-body -->
</div><!-- admin-wrap -->
</body>
</html>
<?php }
?>