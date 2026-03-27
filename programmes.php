<?php
require_once 'includes/db.php';
require_once 'includes/auth.php'; // For security headers and functions

// Set security headers
setSecurityHeaders();

$page_title = 'Programmes';

// Validate and sanitize inputs
$level_filter = isset($_GET['level']) ? (int)$_GET['level'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Validate level filter (only 0, 1, or 2 are valid)
if ($level_filter < 0 || $level_filter > 2) {
    $level_filter = 0;
}

// Validate search length (prevent excessively long searches)
if (strlen($search) > 100) {
    $search = substr($search, 0, 100);
}

// Build secure query with prepared statements
$where = [];
$params = [];
$types = '';

if ($level_filter > 0) {
    $where[] = 'p.LevelID = ?';
    $params[] = $level_filter;
    $types .= 'i';
}

if ($search !== '') {
    $where[] = '(p.ProgrammeName LIKE ? OR p.Description LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}

// Fetch programmes with image and leader using SECURE prepared statement
$sql = "SELECT p.*, l.LevelName, s.Name AS LeaderName
        FROM Programmes p
        JOIN Levels l ON p.LevelID = l.LevelID
        LEFT JOIN Staff s ON p.ProgrammeLeaderID = s.StaffID";

if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY p.LevelID, p.ProgrammeName';

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$programmes = $stmt->get_result();
$stmt->close();

// Count per level for display using SECURE prepared statement
$counts = [];
$countStmt = $conn->prepare("
    SELECT l.LevelName, COUNT(*) as c 
    FROM Programmes p 
    JOIN Levels l ON p.LevelID = l.LevelID 
    GROUP BY l.LevelID
");
$countStmt->execute();
$countResult = $countStmt->get_result();
while ($row = $countResult->fetch_assoc()) {
    $counts[$row['LevelName']] = (int)$row['c'];
}
$countStmt->close();

$totalProgrammes = array_sum($counts);
$programmesCount = $programmes->num_rows;

include 'includes/header.php';
?>

<div class="page-hero">
    <div class="page-hero-inner">
        <div class="breadcrumb">
            <a href="<?= htmlspecialchars(BASE_URL) ?>/index.php">Home</a> / Programmes
        </div>
        <h1>Our Programmes</h1>
        <p>Choose from <?= (int)$totalProgrammes ?> degree programmes across undergraduate and postgraduate study.</p>
    </div>
</div>

<!-- FILTER BAR -->
<div class="filter-bar">
    <form class="filter-inner" method="GET" action="<?= htmlspecialchars(BASE_URL) ?>/programmes.php">
        <label for="level">Filter by:</label>
        <select name="level" id="level">
            <option value="0" <?= $level_filter === 0 ? 'selected' : '' ?>>All Levels</option>
            <option value="1" <?= $level_filter === 1 ? 'selected' : '' ?>>Undergraduate (<?= (int)($counts['Undergraduate'] ?? 0) ?>)</option>
            <option value="2" <?= $level_filter === 2 ? 'selected' : '' ?>>Postgraduate (<?= (int)($counts['Postgraduate'] ?? 0) ?>)</option>
        </select>
        <input type="text" name="search" placeholder="Search programmes…" 
               value="<?= htmlspecialchars($search) ?>" 
               maxlength="100">
        <button type="submit" class="btn btn-navy btn-sm">Search</button>
        <?php if ($level_filter > 0 || $search !== ''): ?>
            <a href="<?= htmlspecialchars(BASE_URL) ?>/programmes.php" class="btn btn-sm" style="background:var(--cream);color:var(--navy);border:1px solid var(--border);">✕ Clear</a>
        <?php endif; ?>
    </form>
</div>

<script>
// Auto-submit when level changes
document.getElementById('level')?.addEventListener('change', function() {
    this.form.submit();
});
</script>

<section class="section">
    <div class="section-inner">
        <?php if ($programmesCount === 0): ?>
        <div class="empty-state">
            <div class="icon">🔍</div>
            <h3>No programmes found</h3>
            <p>Try adjusting your search or filter.</p>
            <a href="<?= htmlspecialchars(BASE_URL) ?>/programmes.php" class="btn btn-navy" style="margin-top:1rem;">View All</a>
        </div>
        <?php else: ?>
        <p style="color:var(--text-muted);font-size:.88rem;margin-bottom:1.5rem;">
            <?= (int)$programmesCount ?> programme<?= $programmesCount !== 1 ? 's' : '' ?> found
        </p>
        <div class="cards-grid">
            <?php while ($prog = $programmes->fetch_assoc()): ?>
            <a href="<?= htmlspecialchars(BASE_URL) ?>/programme_detail.php?id=<?= (int)$prog['ProgrammeID'] ?>" class="card">
                <div class="card-colour-bar"></div>
                <?php if (!empty($prog['Image'])): ?>
                <div class="card-image">
                    <img src="<?= htmlspecialchars($prog['Image']) ?>" 
                         alt="<?= htmlspecialchars($prog['ProgrammeName']) ?>" 
                         style="width:100%;height:200px;object-fit:cover;"
                         loading="lazy">
                </div>
                <?php endif; ?>
                <div class="card-body">
                    <span class="card-badge <?= $prog['LevelName'] === 'Postgraduate' ? 'pg' : '' ?>">
                        <?= htmlspecialchars($prog['LevelName']) ?>
                    </span>
                    <h3><?= htmlspecialchars($prog['ProgrammeName']) ?></h3>
                    <p><?= htmlspecialchars(substr($prog['Description'] ?? '', 0, 120)) ?>…</p>
                </div>
                <div class="card-footer">
                    <span class="card-footer-info">👤 <?= htmlspecialchars($prog['LeaderName'] ?? 'TBC') ?></span>
                    <span class="card-arrow">→</span>
                </div>
            </a>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>