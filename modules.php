<?php
require_once 'includes/db.php';
require_once 'includes/auth.php'; // For security headers and functions

// Set security headers
setSecurityHeaders();

$page_title = 'Modules';

// Sanitize and validate inputs
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$leader = isset($_GET['leader']) ? (int)$_GET['leader'] : 0;

// Validate leader ID
if ($leader < 0) {
    $leader = 0;
}

// Build secure query with prepared statements
$where = [];
$params = [];
$types = '';

if ($search !== '') {
    $where[] = '(m.ModuleName LIKE ? OR m.Description LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}

if ($leader > 0) {
    $where[] = 'm.ModuleLeaderID = ?';
    $params[] = $leader;
    $types .= 'i';
}

$sql = "SELECT m.*, s.Name AS LeaderName FROM Modules m LEFT JOIN Staff s ON m.ModuleLeaderID = s.StaffID";
if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY m.ModuleName';

// Prepare and execute main query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$modules = $stmt->get_result();
$stmt->close();

// Get all staff for filter dropdown using SECURE prepared statement
$all_staff = [];
$staffStmt = $conn->prepare("
    SELECT DISTINCT s.* 
    FROM Staff s 
    INNER JOIN Modules m ON m.ModuleLeaderID = s.StaffID 
    ORDER BY s.Name
");
$staffStmt->execute();
$staffResult = $staffStmt->get_result();
while ($row = $staffResult->fetch_assoc()) {
    $all_staff[] = $row;
}
$staffStmt->close();

$modulesCount = $modules->num_rows;

include 'includes/header.php';
?>

<div class="page-hero">
    <div class="page-hero-inner">
        <div class="breadcrumb">
            <a href="<?= htmlspecialchars(BASE_URL) ?>/index.php">Home</a> / Modules
        </div>
        <h1>Module Catalogue</h1>
        <p>Browse all teaching modules across every programme — click any module to explore its content and staff.</p>
    </div>
</div>

<!-- FILTER BAR -->
<div class="filter-bar">
    <form class="filter-inner" method="GET" action="<?= htmlspecialchars(BASE_URL) ?>/modules.php">
        <label>Filter:</label>
        <input type="text" name="search" placeholder="Search modules…" 
               value="<?= htmlspecialchars($search) ?>" 
               maxlength="100">
        
        <select name="leader">
            <option value="0">All Module Leaders</option>
            <?php foreach ($all_staff as $s): ?>
            <option value="<?= (int)$s['StaffID'] ?>" <?= $leader === (int)$s['StaffID'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($s['Name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        
        <button type="submit" class="btn btn-navy btn-sm">Search</button>
        
        <?php if ($search !== '' || $leader > 0): ?>
        <a href="<?= htmlspecialchars(BASE_URL) ?>/modules.php" class="btn btn-sm" style="background:var(--cream);color:var(--navy);border:1px solid var(--border);">✕ Clear</a>
        <?php endif; ?>
    </form>
</div>

<section class="section">
    <div class="section-inner">
        <?php if ($modulesCount === 0): ?>
        <div class="empty-state">
            <div class="icon">📭</div>
            <h3>No modules found</h3>
            <p>Try a different search term or clear your filters.</p>
            <a href="<?= htmlspecialchars(BASE_URL) ?>/modules.php" class="btn btn-navy" style="margin-top:1rem;">View All</a>
        </div>
        <?php else: ?>
        <p style="color:var(--text-muted);font-size:.88rem;margin-bottom:1.5rem;">
            <?= (int)$modulesCount ?> module<?= $modulesCount !== 1 ? 's' : '' ?> found
        </p>
        <div class="cards-grid">
            <?php while ($mod = $modules->fetch_assoc()): ?>
            <a href="<?= htmlspecialchars(BASE_URL) ?>/module_detail.php?id=<?= (int)$mod['ModuleID'] ?>" class="card">
                <div class="card-colour-bar" style="background:linear-gradient(90deg,var(--gold),var(--navy));"></div>
                <div class="card-body">
                    <span class="card-badge">Module</span>
                    <h3><?= htmlspecialchars($mod['ModuleName']) ?></h3>
                    <p><?= htmlspecialchars(substr($mod['Description'] ?? 'No description available.', 0, 110)) ?>…</p>
                </div>
                <div class="card-footer">
                    <span class="card-footer-info">👤 <?= htmlspecialchars($mod['LeaderName'] ?? 'TBC') ?></span>
                    <span class="card-arrow">→</span>
                </div>
            </a>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>