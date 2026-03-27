<?php
function studentHead($title) { ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> — Crestfield Student</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <style>
        .student-wrap { display: flex; min-height: 100vh; }
        .student-sidebar {
            width: 250px;
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
            border-right: 3px solid var(--gold);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        .sidebar-brand {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,.1);
            display: flex;
            align-items: center;
            gap: .6rem;
        }
        .sidebar-brand .crest { font-size: 1.6rem; color: var(--gold); }
        .sidebar-brand-text { color: #fff; }
        .sidebar-brand-text strong { font-family: 'Playfair Display', serif; font-size: 1rem; display: block; }
        .sidebar-brand-text small { font-size: .72rem; color: rgba(255,255,255,.45); letter-spacing: .06em; text-transform: uppercase; }

        .sidebar-profile {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,.1);
            display: flex;
            align-items: center;
            gap: .75rem;
        }
        .sidebar-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: var(--gold);
            color: var(--navy);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            overflow: hidden;
        }
        .sidebar-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .sidebar-nav { flex: 1; padding: 1rem 0; }
        .sidebar-section {
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: rgba(255,255,255,.3);
            padding: 1rem 1.5rem .4rem;
        }
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .65rem 1.5rem;
            color: rgba(255,255,255,.7);
            font-size: .875rem;
            font-weight: 500;
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

        .student-body { margin-left: 250px; flex: 1; display: flex; flex-direction: column; }

        .student-topbar {
            background: #fff;
            border-bottom: 1px solid var(--border);
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .student-topbar h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            color: var(--navy);
        }
        .student-user {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--gold);
            color: var(--navy);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            font-weight: bold;
        }
        .student-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .edit-profile-btn {
            background: var(--navy);
            color: #fff;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.8rem;
            transition: background 0.3s;
        }
        .edit-profile-btn:hover {
            background: var(--gold);
        }

        .student-content { padding: 2rem; flex: 1; }

        .stat-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: #fff;
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            border-left: 4px solid var(--gold);
            box-shadow: 0 2px 12px var(--shadow);
        }
        .stat-card-num {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--navy);
        }
        .stat-card-label {
            font-size: .75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: .08em;
        }
        .panel {
            background: #fff;
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 12px var(--shadow);
        }
        .panel h2 {
            font-size: 1.1rem;
            color: var(--navy);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--border);
        }
        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }
        .admin-table th, .admin-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        .admin-table th {
            background: var(--cream);
            font-weight: 600;
            color: var(--navy);
        }
        @media (max-width: 768px) {
            .student-sidebar { width: 200px; }
            .student-body { margin-left: 200px; }
            .stat-cards { grid-template-columns: 1fr; }
        }
        /* Student Module Cards */
.module-card {
    background: #fff;
    border-radius: var(--radius-lg);
    padding: 1rem;
    margin-bottom: 1rem;
    border: 1px solid var(--border);
    transition: all 0.3s;
}

.module-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

/* Lecturer Info */
.lecturer-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid var(--border);
}

.lecturer-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--gold);
    color: var(--navy);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1rem;
    overflow: hidden;
    flex-shrink: 0;
}

.lecturer-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Attendance Badges */
.attendance-badge {
    display: inline-block;
    padding: 0.2rem 0.6rem;
    border-radius: 99px;
    font-size: 0.7rem;
    font-weight: 600;
}

.attendance-present {
    background: #e8f5e9;
    color: #2e7d32;
}

.attendance-absent {
    background: #ffebee;
    color: #c62828;
}

.attendance-late {
    background: #fff3e0;
    color: #f57c00;
}

.attendance-excused {
    background: #e3f2fd;
    color: #1976d2;
}

/* Module Cards Grid */
.modules-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
}
    </style>
</head>
<body>
<?php }

function studentSidebar($active, $studentData) {
    $initials = strtoupper(substr($studentData['FullName'] ?? 'S', 0, 2));
    $studentPhoto = !empty($studentData['Photo']) ? $studentData['Photo'] : '';
    $photoUrl = $studentPhoto ? BASE_URL . '/uploads/student_photos/' . rawurlencode($studentPhoto) : '';
    $hasPhoto = $studentPhoto && file_exists($_SERVER['DOCUMENT_ROOT'] . '/University/uploads/student_photos/' . $studentPhoto);
    ?>
<div class="student-wrap">
<aside class="student-sidebar">
    <div class="sidebar-brand">
        <span class="crest">⬡</span>
        <div class="sidebar-brand-text">
            <strong>Crestfield</strong>
            <small>Student Portal</small>
        </div>
    </div>
    <div class="sidebar-profile">
        <div class="sidebar-avatar">
            <?php if ($hasPhoto): ?>
                <img src="<?= htmlspecialchars($photoUrl) ?>" alt="<?= htmlspecialchars($studentData['FullName']) ?>">
            <?php else: ?>
                <?= htmlspecialchars($initials) ?>
            <?php endif; ?>
        </div>
        <div>
            <div style="color:#fff; font-size:.82rem;"><?= htmlspecialchars($studentData['FullName']) ?></div>
            <div style="color:rgba(255,255,255,.45); font-size:.72rem;">Student</div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="sidebar-section">Menu</div>
        <a href="<?= BASE_URL ?>/student/dashboard.php" class="<?= $active === 'dashboard' ? 'active' : '' ?>">
            <span class="icon">📊</span> Dashboard
        </a>
        <a href="<?= BASE_URL ?>/student/grades.php" class="<?= $active === 'grades' ? 'active' : '' ?>">
            <span class="icon">📚</span> My Grades
        </a>
        <a href="<?= BASE_URL ?>/student/attendance.php" class="<?= $active === 'attendance' ? 'active' : '' ?>">
            <span class="icon">✅</span> Attendance
        </a>
        <a href="<?= BASE_URL ?>/student/profile.php" class="<?= $active === 'profile' ? 'active' : '' ?>">
            <span class="icon">👤</span> My Profile
        </a>
        <div class="sidebar-section">Site</div>
        <a href="<?= BASE_URL ?>/index.php" target="_blank">
            <span class="icon">🌐</span> View Public Site
        </a>
    </nav>
    <div class="sidebar-footer">
        <form method="POST" action="<?= BASE_URL ?>/student/logout.php">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <button type="submit">⏻ Sign Out</button>
        </form>
    </div>
</aside>
<div class="student-body">
<?php }

function studentTopbar($title, $studentData) {
    $initials = strtoupper(substr($studentData['FullName'] ?? 'S', 0, 2));
    $studentPhoto = !empty($studentData['Photo']) ? $studentData['Photo'] : '';
    $photoUrl = $studentPhoto ? BASE_URL . '/uploads/student_photos/' . rawurlencode($studentPhoto) : '';
    $hasPhoto = $studentPhoto && file_exists($_SERVER['DOCUMENT_ROOT'] . '/University/uploads/student_photos/' . $studentPhoto);
    ?>
    <div class="student-topbar">
        <h1><?= htmlspecialchars($title) ?></h1>
        <div class="student-user">
            <div class="student-avatar">
                <?php if ($hasPhoto): ?>
                    <img src="<?= htmlspecialchars($photoUrl) ?>" alt="<?= htmlspecialchars($studentData['FullName']) ?>">
                <?php else: ?>
                    <?= htmlspecialchars($initials) ?>
                <?php endif; ?>
            </div>
            <span><?= htmlspecialchars($studentData['FullName']) ?></span>
            <a href="<?= BASE_URL ?>/student/profile.php" class="edit-profile-btn">Edit Profile</a>
        </div>
    </div>
    <div class="student-content">
    <?php
}

function studentFooter() { ?>
    </div>
</div>
</div>
</body>
</html>
<?php }
?>