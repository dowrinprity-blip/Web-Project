<?php
require_once 'includes/db.php';
$page_title = 'My Interests';

// ✅ Auto-detect email (GET or POST)
$email = $_GET['email'] ?? ($_POST['email'] ?? '');

// ✅ REMOVE INTEREST (must be BEFORE any output)
if (isset($_GET['remove']) && isset($_GET['email'])) {
    $removeID = (int) $_GET['remove'];
    $email    = $_GET['email'];

    $stmt = $conn->prepare("DELETE FROM interestedstudents WHERE ProgrammeID = ? AND Email = ?");
    $stmt->bind_param("is", $removeID, $email);
    $stmt->execute();

    header("Location: my-interests.php?email=" . urlencode($email));
    exit;
}

include 'includes/header.php';

// Initialize array
$interests = [];

// ✅ FETCH DATA (works for both GET + POST)
if (!empty($email)) {

    $stmt = $conn->prepare("
        SELECT 
            p.ProgrammeID, 
            p.ProgrammeName, 
            p.Description, 
            p.Image, 
            i.StudentName, 
            i.RegisteredAt 
        FROM interestedstudents i
        JOIN Programmes p ON i.ProgrammeID = p.ProgrammeID
        WHERE i.Email = ?
        ORDER BY i.RegisteredAt DESC
    ");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $interests[] = $row;
    }
}
?>

<section class="section">
    <div class="section-inner" style="max-width:1000px;margin:0 auto;">

        

        <!-- FORM -->
        <form method="POST" action="" style="text-align:center;margin-bottom:2rem;">
            <input type="email" name="email"
                   placeholder="Enter your email"
                   value="<?= htmlspecialchars($email) ?>"
                   required
                   style="padding:.6rem .9rem;font-size:1rem;width:260px;border:1px solid var(--border);border-radius:var(--radius);">
            <button type="submit" class="btn btn-navy" style="margin-left:.5rem;">View My Interests</button>
        </form>

        <?php if (!empty($email)): ?>

            <?php if (!empty($interests)): ?>

                <div class="cards-grid">

                    <?php foreach ($interests as $i): ?>

                        <?php $img = !empty($i['Image']) ? $i['Image'] : 'uploads/placeholder.jpg'; ?>

                        <div class="card">

                            <div class="card-colour-bar"></div>

                            <img src="<?= htmlspecialchars($img) ?>"
                                 alt="<?= htmlspecialchars($i['ProgrammeName']) ?>">

                            <div class="card-body">
                                <h3><?= htmlspecialchars($i['ProgrammeName']) ?></h3>

                                <p>
                                    <?= htmlspecialchars(substr($i['Description'], 0, 100)) ?>…
                                </p>
                            </div>

                            <!-- ✅ NEW FOOTER -->
                            <div class="card-footer">

                                <span>
                                    <?= date('d M Y', strtotime($i['RegisteredAt'])) ?>
                                </span>

                                <div style="display:flex;gap:.4rem;align-items:center;">

                                    <!-- Go -->
                                    <a href="programme_detail.php?id=<?= $i['ProgrammeID'] ?>" title="View">
                                        →
                                    </a>

                                    <!-- Remove -->
                                    <a href="my-interests.php?remove=<?= $i['ProgrammeID'] ?>&email=<?= urlencode($email) ?>"
                                       class="btn-danger"
                                       onclick="return confirm('Remove this interest?')">
                                       ✕
                                    </a>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <p style="text-align:center;color:red;font-weight:600;">
                    No interests found for <strong><?= htmlspecialchars($email) ?></strong>
                </p>

            <?php endif; ?>

        <?php endif; ?>

    </div>
</section>

<?php include 'includes/footer.php'; ?>