<?php
var_dump($_POST);
exit;
require_once 'includes/db.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$email = '';
$interests = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (!empty($email)) {
        // Query the DB
        $sql = "SELECT ProgrammeID, StudentName, RegisteredAt 
                FROM interestedstudents 
                WHERE Email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $interests = $result->fetch_all(MYSQLI_ASSOC);
        } else {
            $interests = null; // no interests found
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Interests</title>
</head>
<body>
<h2>Check Your Interests</h2>

<form method="POST" action="">
    <input type="email" name="email" placeholder="Enter your email" value="<?= htmlspecialchars($email) ?>" required>
    <button type="submit">View My Interests</button>
</form>

<?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
    <?php if ($interests === null): ?>
        <p>No interests found for <?= htmlspecialchars($email) ?>.</p>
    <?php else: ?>
        <h3>Interests for <?= htmlspecialchars($email) ?>:</h3>
        <ul>
            <?php foreach ($interests as $i): ?>
                <li>
                    Programme ID: <?= htmlspecialchars($i['ProgrammeID']) ?>,
                    Student Name: <?= htmlspecialchars($i['StudentName']) ?>,
                    Registered At: <?= htmlspecialchars($i['RegisteredAt']) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
<?php endif; ?>

</body>
</html>