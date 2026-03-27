<?php
require_once 'includes/db.php';
$page_title = 'My Interests';

$interests = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['email'])) {
    $email = $conn->real_escape_string($_POST['email']);

    $sql = "SELECT 
                p.ProgrammeID, 
                p.ProgrammemmeName, 
                p.Description, 
                p.Image,
                i.StudentName, 
                i.RegisteredAt 
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
    <div class="section-inner" style="max-width:1000px;margin:0 auto;text-align:center;">

        <h2 style="margin-bottom:1rem;">View Your Interests</h2>

        <!-- FORM -->
        <form method="POST" action="" style="margin-bottom:2rem;">
            <input 
                type="email" 
                name="email" 
                placeholder="Enter your email" 
                required
                style="padding:.6rem;width:260px;border:1px solid #ccc;border-radius:6px;"
            >
            <button 
                type="submit"
                style="padding:.6rem 1rem;margin-left:.5rem;background:#1a73e8;color:#fff;border:none;border-radius:6px;cursor:pointer;"
            >
                View My Interests
            </button>
        </form>

        <!-- RESULTS -->
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>

            <?php if (count($interests) > 0): ?>

                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1.2rem;">

                    <?php foreach ($interests as $i): ?>

                        <div style="border:1px solid #ddd;border-radius:12px;overflow:hidden;
                                    box-shadow:0 8px 20px rgba(0,0,0,0.08);
                                    transition:transform .2s;">

                            <!-- IMAGE -->
                            <img 
                                src="uploads/<?= htmlspecialchars($i['Image']) ?>" 
                                alt=""
                                style="width:100%;height:170px;object-fit:cover;"
                            >

                            <!-- CONTENT -->
                            <div style="padding:1rem;text-align:left;">

                                <h3 style="margin:0 0 .5rem 0;font-size:1.1rem;">
                                    <?= htmlspecialchars($i['ProgrammemmeName']) ?>
                                </h3>

                                <p style="font-size:.85rem;color:#555;line-height:1.5;">
                                    <?= htmlspecialchars($i['Description']) ?>
                                </p>

                                <p style="font-size:.75rem;color:#888;margin-top:.6rem;">
                                    Registered: <?= htmlspecialchars($i['RegisteredAt']) ?><br>
                                    Name: <?= htmlspecialchars($i['StudentName']) ?>
                                </p>

                                <!-- LINK -->
                                <a 
                                    href="programme_details.php?id=<?= $i['ProgrammeID'] ?>"
                                    style="display:inline-block;margin-top:.7rem;
                                           padding:.45rem .9rem;
                                           background:#1a73e8;color:#fff;
                                           border-radius:6px;
                                           text-decoration:none;
                                           font-size:.8rem;">
                                    Go to Programme →
                                </a>

                            </div>
                        </div>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>
                <p style="margin-top:1rem;color:#666;">
                    No interests found for this email.
                </p>
            <?php endif; ?>

        <?php endif; ?>

    </div>
</section>

<?php include 'includes/footer.php'; ?>