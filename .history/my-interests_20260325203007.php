<?php
require_once 'includes/db.php';
$page_title = 'My Interests';
include 'includes/header.php';

// Initialize empty array for results
$interests = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['email'])) {
    $email = $conn->real_escape_string($_POST['email']);

    // Fetch all programmes the email is interested in
    $sql = "
        SELECT 
            p.ProgrammeID, 
            p.ProgrammeName, 
            p.Description, 
            p.Image, 
            i.StudentName, 
            i.RegisteredAt 
        FROM interestedstudents i
        JOIN Programmes p ON i.ProgrammeID = p.ProgrammeID
        WHERE i.Email = '$email'
        ORDER BY i.RegisteredAt DESC
    ";

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $interests[] = $row;
        }
    }
}
?>

<section class="section">
    <div class="section-inner" style="max-width:900px;margin:0 auto;">
        <div class="section-header" style="text-align:center;">
            <h2>View Your Interests</h2>
            <p>Enter the email you registered with to see your interested programmes.</p>
        </div>

        <form method="POST" action="" style="text-align:center;margin-bottom:2rem;">
            <input type="email" name="email" placeholder="Enter your email" required style="padding:.5rem .75rem;font-size:1rem;width:250px;">
            <button type="submit" style="padding:.5rem 1rem;font-size:1rem;margin-left:.5rem;">View My Interests</button>
        </form>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <?php if (!empty($interests)): ?>
                <div class="cards-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:1.5rem;">
                    <?php foreach ($interests as $i): ?>
                        <div class="card" style="border-radius:var(--radius-lg);overflow:hidden;box-shadow:0 8px 20px rgba(0,0,0,0.1);transition:transform .3s ease;">
                            <img src="<?= htmlspecialchars($i['Image']) ?>" alt="<?= htmlspecialchars($i['ProgrammeName']) ?>" style="width:100%;height:180px;object-fit:cover;">
                            <div class="card-body" style="padding:1rem;">
                                <h3 style="margin:0 0 .5rem 0;font-size:1.1rem;"><?= htmlspecialchars($i['ProgrammeName']) ?></h3>
                                <p style="font-size:.85rem;color:var(--text-muted);height:3.5rem;overflow:hidden;"><?= htmlspecialchars($i['Description']) ?></p>
                                <p style="font-size:.75rem;color:var(--text-muted);margin-top:.5rem;">Registered on: <?= date('d M Y', strtotime($i['RegisteredAt'])) ?></p>
                                <a href="programme_detail.php?id=<?= $i['ProgrammeID'] ?>" class="btn btn-gold" style="display:inline-block;margin-top:.75rem;">Go to Programme →</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="text-align:center;color:red;font-weight:600;">No interests found for this email.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>