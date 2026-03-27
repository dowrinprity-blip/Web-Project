<?php
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $conn->real_escape_string($_POST['email']);

    $sql = "SELECT DISTINCT ProgrammeID 
            FROM interested
            WHERE Email = '$email'";

    $result = $conn->query($sql);

    $programmes = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $programmes[] = $row['ProgrammeID'];
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<section class="section">
    <div class="section-inner">
        <h2>Your Interests</h2>

        <?php if (empty($programmes)): ?>
            <div class="alert alert-error">No interests found for this email.</div>
        <?php else: ?>

            <div class="cards-grid">
                <?php
                $ids = implode(',', $programmes);

                $query = "SELECT * FROM Programmes WHERE ProgrammeID IN ($ids)";
                $res = $conn->query($query);

                while ($prog = $res->fetch_assoc()):
                ?>
                    <div class="card">
                        <div class="card-body">
                            <h3><?= htmlspecialchars($prog['ProgrammeName']) ?></h3>
                            <p><?= htmlspecialchars($prog['Description']) ?></p>
                        </div>
                        <div class="card-footer">
                            <a href="programme_detail.php?id=<?= $prog['ProgrammeID'] ?>" class="btn btn-sm btn-navy">
                                View Programme →
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>