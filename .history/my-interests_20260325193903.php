<?php
require_once 'includes/db.php';
$page_title = 'My Interests';

$interests = []; // default empty array

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['email'])) {
    $email = $conn->real_escape_string($_POST['email']);
    $sql = "SELECT ProgrammeID, StudentName, RegisteredAt FROM interestedstudents WHERE Email = '$email'";
    $result = $conn->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $interests[] = $row;
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<section class="section">
    <div class="section-inner" style="max-width:600px;margin:0 auto;text-align:center;">
        <h2>View Your Interests</h2>
        <form method="POST" action="">
            <label for="email">Enter your email:</label><br><br>
            <input type="email" name="email" id="email" placeholder="you@example.com" required style="padding:.5rem;width:80%;max-width:300px;"><br><br>
            <button type="submit" style="padding:.5rem 1rem;">View My Interests</button>
        </form>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <h3 style="margin-top:2rem;">Results:</h3>
            <?php if (count($interests) > 0): ?>
                <ul style="list-style:none;padding:0;">
                    <?php foreach ($interests as $i): ?>
                        <li style="margin-bottom:1rem;padding:1rem;border:1px solid #ccc;border-radius:8px;">
                            <strong>Programme ID:</strong> <?= htmlspecialchars($i['ProgrammeID']) ?><br>
                            <strong>Name:</strong> <?= htmlspecialchars($i['StudentName']) ?><br>
                            <strong>Registered At:</strong> <?= htmlspecialchars($i['RegisteredAt']) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No interests found for this email.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>