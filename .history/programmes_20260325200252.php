<?php
require_once 'includes/db.php';
$page_title = 'Programmes';

$level_filter = isset($_GET['level']) ? (int)$_GET['level'] : 0;
$search       = isset($_GET['search']) ? trim($_GET['search']) : '';

$where  = [];
$params = [];
$types  = '';

if ($level_filter > 0) {
    $where[]  = 'p.LevelID = ?';
    $params[] = $level_filter;
    $types   .= 'i';
}
if ($search !== '') {
    $where[]  = '(p.ProgrammeName LIKE ? OR p.Description LIKE ?)';
    $like      = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $types   .= 'ss';
}

$sql = "SELECT p.*, l.LevelName, s.Name AS LeaderName
        FROM Programmes p
        JOIN Levels l ON p.LevelID = l.LevelID
        LEFT JOIN Staff s ON p.ProgrammeLeaderID = s.StaffID";
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY p.LevelID, p.ProgrammeName';

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$programmes = $stmt->get_result();

// Count per level for display
$counts = [];
$res = $conn->query("SELECT l.LevelName, COUNT(*) as c FROM Programmes p JOIN Levels l ON p.LevelID = l.LevelID GROUP BY l.LevelID");
while ($r = $res->fetch_assoc()) $counts[$r['LevelName']] = $r['c'];

include 'includes/header.php';
?>

<div class="page-hero">
    <div class="page-hero-inner">
        <div class="breadcrumb"><a href="index.php">Home</a> / Programmes</div>
        <h1>Our Programmes</h1>
        <p>Choose from <?= array_sum($counts) ?> degree programmes across undergraduate and postgraduate study.</p>
    </div>
</div>

<!-- FILTER BAR -->
<div class="filter-bar">
    <form class="filter-inner" method="GET" action="programmes.php">
        <label for="level">Filter by:</label>
        <select name="level" id="level" onchange="this.form.submit()">
            <option value="0" <?= $level_filter === 0 ? 'selected' : '' ?>>All Levels</option>
            <option value="1" <?= $level_filter === 1 ? 'selected' : '' ?>>Undergraduate (<?= $counts['Undergraduate'] ?? 0 ?>)</option>
            <option value="2" <?= $level_filter === 2 ? 'selected' : '' ?>>Postgraduate (<?= $counts['Postgraduate'] ?? 0 ?>)</option>
        </select>
        <input type="text" name="search" placeholder="Search programmes…" value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-navy btn-sm">Search</button>
        <?php if ($level_filter || $search): ?>
            <a href="programmes.php" class="btn btn-sm" style="background:var(--cream);color:var(--navy);border:1px solid var(--border);">✕ Clear</a>
        <?php endif; ?>
    </form>
</div>

<section class="section">
    <div class="section-inner">
        <?php if ($programmes->num_rows === 0): ?>
        <div class="empty-state">
            <div class="icon">🔍</div>
            <h3>No programmes found</h3>
            <p>Try adjusting your search or filter.</p>
            <a href="programmes.php" class="btn btn-navy" style="margin-top:1rem;">View All</a>
        </div>
        <?php else: ?>
        <p style="color:var(--text-muted);font-size:.88rem;margin-bottom:1.5rem;"><?= $programmes->num_rows ?> programme<?= $programmes->num_rows !== 1 ? 's' : '' ?> found</p>
        
        <div class="cards-grid">
    <?php while ($prog = $programmes->fetch_assoc()): ?>
    <a href="programme_detail.php?id=<?= $prog['ProgrammeID'] ?>" class="card">
        <div class="card-colour-bar"></div>
        <div class="card-body">
            <span class="card-badge <?= $prog['LevelName'] === 'Postgraduate' ? 'pg' : '' ?>">
                <?= htmlspecialchars($prog['LevelName']) ?>
            </span>
            <h3><?= htmlspecialchars($prog['ProgrammeName']) ?></h3>
            <p><?= htmlspecialchars(substr($prog['Description'], 0, 115)) ?>…</p>
        </div>
        <div class="card-footer">
            <span class="card-footer-info">👤 <?= htmlspecialchars($prog['LeaderName']) ?></span>
            <span class="card-arrow">→</span>
        </div>
    </a>
    <?php endwhile; ?>
</div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
