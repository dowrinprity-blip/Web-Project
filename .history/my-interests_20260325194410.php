<?php
require_once 'includes/db.php';
$page_title = 'My Interests';

$interests = []; // default empty array

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['email'])) {
    $email = $conn->real_escape_string($_POST['email']);

    // Get the programme names and details for this email, sorted by registration date descending
    $sql = "SELECT p.ProgrammeID, p.ProgrammeName, p.ShortDesc, i.StudentName, i.RegisteredAt 
            FROM interestedstudents i
            JOIN Programmes p ON i.ProgrammeID = p.ProgrammeID
            WHERE i.Email = '$email'
            ORDER BY i.RegisteredAt DESC";
    $result = $conn->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $interests[] = $row;
        }
    }
}

include 'includes/header.php';
?>

<section class="section">
    <div class="section-inner" style="max-width:800px;margin:0 auto;text-align:center;">
        <h2>View Your Interests</h2>
        <form method="POST" action="" style="margin-bottom:2rem;">
            <input type="email" name="email" id="email" placeholder="you@example.com" required
                   style="padding:.5rem;width:80%;max-width:300px;margin-bottom:.5rem;">
            <br>
            <button type="submit" style="padding:.5rem 1rem;">View My Interests</button>
        </form>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <?php if (count($interests) > 0): ?>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:1rem;">
                    <?php foreach ($interests as $i): ?>
                        <div style="border:1px solid #ccc;border-radius:8px;overflow:hidden;box-shadow:0 5px 15px rgba(0,0,0,0.1);">
                            <div style="padding:1rem;text-align:left;">
                                <h3 style="margin-top:0;font-size:1.2rem;"><?= htmlspecialchars($i['ProgrammeName']) ?></h3>
                                <p style="font-size:.9rem;color:#555;"><?= htmlspecialchars($i['ShortDesc']) ?></p>
                                <p style="font-size:.8rem;color:#888;margin-top:.5rem;">
                                    Registered At: <?= htmlspecialchars($i['RegisteredAt']) ?><br>
                                    Name: <?= htmlspecialchars($i['StudentName']) ?>
                                </p>
                                <a href="programmes.php?id=<?= urlencode($i['ProgrammeID']) ?>" 
                                   style="display:inline-block;margin-top:.5rem;padding:.4rem .8rem;background:#1a73e8;color:#fff;border-radius:4px;text-decoration:none;font-size:.85rem;">
                                   Go to Programme
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No interests found for this email.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>