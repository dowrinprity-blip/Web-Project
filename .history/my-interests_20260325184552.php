<?php
require_once 'includes/db.php';
$page_title = 'My Interests';

// Make sure student is logged in
session_start();
if (!isset($_SESSION['student_email'])) {
    header("Location: student/login.php");
    exit;
}

$email = $_SESSION['student_email'];

// Fetch programme IDs the student is interested in
$stmt = $conn->prepare("SELECT DISTINCT ProgrammeID FROM interestedstudents WHERE Email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

$programme_ids = [];
while ($row = $result->fetch_assoc()) {
    $programme_ids[] = $row['ProgrammeID'];
}

// Fetch programme details
$programmes = [];
if ($programme_ids) {
    $ids = implode(',', array_map('intval', $programme_ids));
    $sql = "SELECT * FROM Programmes WHERE ID IN ($ids)";
    $res = $conn->query($sql);
    while ($prog = $res->fetch_assoc()) {
        $programmes[] = $prog;
    }
}

include 'includes/header.php';
?>

<section class="section">
    <div class="section-inner">
        <div class="section-header">
            <div class="eyebrow">Student Portal</div>
            <h2>My Registered Interests</h2>
            <p>Here are the programmes you’ve expressed interest in. Click any programme to view details.</p>
        </div>

        <?php if ($programmes): ?>
        <div class="cards-grid" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap:1.5rem;">
            <?php foreach ($programmes as $prog): ?>
            <div class="card">
                <div class="card-body" style="text-align:center; align-items:center;">
                    <?php if (!empty($prog['Image'])): ?>
                    <img src="<?= htmlspecialchars($prog['Image']) ?>" alt="<?= htmlspecialchars($prog['Name']) ?>" style="width:100%;height:160px;object-fit:cover;border-radius:8px;margin-bottom:.8rem;">
                    <?php endif; ?>
                    <h3 style="margin-bottom:.5rem;"><?= htmlspecialchars($prog['Name']) ?></h3>
                    <p style="font-size:.85rem; color:var(--text-muted);"><?= htmlspecialchars($prog['ShortDescription'] ?? '') ?></p>
                    <a href="programme_details.php?id=<?= $prog['ID'] ?>" class="btn btn-navy" style="margin-top:.75rem;">View Details →</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p style="color:var(--text-muted);margin-top:2rem;">You haven't registered any interests yet. Browse our <a href="programmes.php">programmes</a> to explore and register your interests.</p>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>