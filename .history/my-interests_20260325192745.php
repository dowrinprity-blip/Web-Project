

<?php



require_once 'includes/db.php';
include 'includes/header.php';

$interests = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $conn->real_escape_string($_POST['email']);

    $sql = "SELECT i.*, p.ProgrammeName, p.Description
            FROM interestedstudents i
            JOIN Programmes p ON i.ProgrammeID = p.ProgrammeID
            WHERE i.Email = '$email'
            ORDER BY i.RegisteredAt DESC";

    $result = $conn->query($sql);
$result = $conn->query($sql);

if(!$result) {
    die("Query failed: " . $conn->error);
}

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $interests[] = $row;
        }
    } else {
        $error = "No interests found for this email.";
    }
}
?>

<section class="section">
    <div class="section-inner">

        <div class="section-header">
            <div class="eyebrow">Student Access</div>
            <h2>View Your Interests</h2>
            <p>Enter your email to see programmes you registered interest in.</p>
        </div>

        <!-- FORM -->
        <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>" style="max-width:400px;margin:0 auto 2rem;">
    <input type="email" name="email" placeholder="Enter your email" required
           value="<?= htmlspecialchars($email) ?>"
           style="width:100%;padding:.6rem;margin-bottom:.6rem;border:1px solid var(--border);border-radius:var(--radius);">
    <button type="submit" class="btn btn-gold" style="width:100%;">View My Interests</button>
</form>

        <?php if(isset($error)): ?>
            <div class="alert alert-error" style="text-align:center;">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <!-- RESULTS -->
        <?php if(!empty($interests)): ?>
            <div class="cards-grid" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap:1.5rem;">

                <?php foreach($interests as $item): ?>
                    <div class="card">
                        <div class="card-body" style="text-align:center;align-items:center;">
                            
                            <h3><?= htmlspecialchars($item['ProgrammeName']) ?></h3>

                            <p style="font-size:.85rem;color:var(--text-muted);">
                                <?= htmlspecialchars(substr($item['Description'], 0, 120)) ?>...
                            </p>

                            <p style="font-size:.75rem;color:var(--text-muted);margin-top:.5rem;">
                                Registered on: <?= $item['RegisteredAt'] ?>
                            </p>

                            <a href="programme_detail.php?id=<?= $item['ProgrammeID'] ?>"
                               class="btn btn-navy"
                               style="margin-top:.75rem;">
                                View Programme →
                            </a>

                        </div>
                    </div>
                <?php endforeach; ?>

            </div>
        <?php endif; ?>

    </div>
</section>

<?php include 'includes/footer.php'; ?>