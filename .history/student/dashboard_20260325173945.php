<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireStudent();
$student = getLoggedInStudent();
$aid     = $student['AccountID'];
$email   = $student['Email'];
$isEnrolled = $student['StudentType'] === 'enrolled';
$welcome = isset($_GET['welcome']);

// Count interests linked to this email
$interestCount = $conn->query("SELECT COUNT(*) c FROM InterestedStudents WHERE Email='".mysqli_real_escape_string($conn,$email)."'")->fetch_assoc()['c'];

// Recent interests
$recentInterests = $conn->query("
    SELECT i.*, p.ProgrammeName, l.LevelName
    FROM InterestedStudents i
    JOIN Programmes p ON i.ProgrammeID = p.ProgrammeID
    JOIN Levels l ON p.LevelID = l.LevelID
    WHERE i.Email = '".mysqli_real_escape_string($conn,$email)."'
    ORDER BY i.RegisteredAt DESC LIMIT 4
")->fetch_all(MYSQLI_ASSOC);

require_once 'layout.php';
studentHead('Dashboard');
studentSidebar('dashboard', $student);
studentTopbar('Dashboard');
?>

<?php if ($welcome): ?>
<div class="alert alert-success">
    🎉 Welcome to your student portal, <?= htmlspecialchars(explode(' ',$student['FullName'])[0]) ?>! Your account has been created successfully.
</div>
<?php endif; ?>

<!-- STATS -->
<div class="stat-cards">
    <div class="stat-card">
        <div class="stat-card-num"><?= $interestCount ?></div>
        <div class="stat-card-label">Programmes Interested</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-num"><?= $isEnrolled ? '✓' : '–' ?></div>
        <div class="stat-card-label">Enrolled Student</div>
    </div>
</div>

<!-- WELCOME PANEL -->
<div class="panel">
    <h2>Welcome back, <?= htmlspecialchars(explode(' ',$student['FullName'])[0]) ?>! 👋</h2>
    <p style="color:var(--text-muted);font-size:.9rem;line-height:1.7;margin-bottom:1.2rem;">
        <?php if ($isEnrolled): ?>
            You're logged in as a <strong>current Crestfield student</strong><?= $student['CourseInfo'] ? ' — ' . htmlspecialchars($student['CourseInfo']) : '' ?>.
            Browse programmes and modules, and manage your interest registrations below.
        <?php else: ?>
            You're logged in as a <strong>prospective student</strong>. Explore our programmes, register your interest, and we'll keep you updated on open days and application deadlines.
        <?php endif; ?>
    </p>
    <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
        <a href="<?= BASE_URL ?>/programmes.php" class="btn btn-sm btn-navy">Browse Programmes →</a>
        <a href="<?= BASE_URL ?>/student/my_interests.php" class="btn btn-sm" style="background:var(--gold);color:#fff;">My Interests</a>
    </div>
</div>

<!-- RECENT INTERESTS -->
<div class="panel">
    <h2>❤️ Recently Registered Interests</h2>
    <?php if (empty($recentInterests)): ?>
        <p style="color:var(--text-muted);font-size:.88rem;">
            You haven't registered interest in any programmes yet.
            <a href="<?= BASE_URL ?>/programmes.php" style="color:var(--gold);">Explore programmes →</a>
        </p>
    <?php else: ?>
        <?php foreach ($recentInterests as $i): ?>
        <div class="interest-row">
            <div>
                <div class="interest-row-name"><?= htmlspecialchars($i['ProgrammeName']) ?></div>
                <div class="interest-row-meta">
                    <span class="card-badge <?= $i['LevelName']==='Postgraduate'?'pg':'' ?>" style="font-size:.68rem;padding:.15rem .5rem;">
                        <?= htmlspecialchars($i['LevelName']) ?>
                    </span>
                    &nbsp;Registered <?= date('d M Y', strtotime($i['RegisteredAt'])) ?>
                </div>
            </div>
            <div style="display:flex;gap:.5rem;">
                <a href="<?= BASE_URL ?>/programme_detail.php?id=<?= $i['ProgrammeID'] ?>" class="btn btn-sm btn-navy">View →</a>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if ($interestCount > 4): ?>
        <a href="<?= BASE_URL ?>/student/my_interests.php" style="font-size:.85rem;color:var(--gold);margin-top:.5rem;display:inline-block;">View all <?= $interestCount ?> interests →</a>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php studentFooter(); ?>
