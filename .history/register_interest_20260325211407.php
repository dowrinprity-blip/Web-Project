<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

$prog_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Pre-load programme if ID passed
$programme = null;
if ($prog_id) {
    $s = $conn->prepare("SELECT ProgrammeID, ProgrammeName FROM Programmes WHERE ProgrammeID = ?");
    $s->bind_param('i', $prog_id);
    $s->execute();
    $programme = $s->get_result()->fetch_assoc();
}

// Load all programmes for dropdown
$all_progs = $conn->query("
    SELECT p.ProgrammeID, p.ProgrammeName, l.LevelName
    FROM Programmes p JOIN Levels l ON p.LevelID = l.LevelID
    ORDER BY l.LevelID, p.ProgrammeName
");

$success = false;
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $pid     = (int)($_POST['programme_id'] ?? 0);

    // Validation
    if (empty($name))         $errors[] = 'Please enter your full name.';
    elseif (strlen($name) > 100) $errors[] = 'Name must be 100 characters or fewer.';

    if (empty($email))        $errors[] = 'Please enter your email address.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    elseif (strlen($email) > 255) $errors[] = 'Email must be 255 characters or fewer.';

    if ($pid === 0)            $errors[] = 'Please select a programme.';

    // Check duplicate
    if (empty($errors)) {
        $dup = $conn->prepare("SELECT InterestID FROM InterestedStudents WHERE ProgrammeID = ? AND Email = ?");
        $dup->bind_param('is', $pid, $email);
        $dup->execute();
        if ($dup->get_result()->num_rows > 0) {
            $errors[] = 'This email address has already been registered for that programme.';
        }
    }

    if (empty($errors)) {
        $ins = $conn->prepare("INSERT INTO InterestedStudents (ProgrammeID, StudentName, Email) VALUES (?, ?, ?)");
        $ins->bind_param('iss', $pid, $name, $email);
        if ($ins->execute()) {
            $success  = true;
            $prog_id  = $pid;
            // Reload programme name
            $s2 = $conn->prepare("SELECT ProgrammeID, ProgrammeName FROM Programmes WHERE ProgrammeID = ?");
            $s2->bind_param('i', $pid);
            $s2->execute();
            $programme = $s2->get_result()->fetch_assoc();
        } else {
            $errors[] = 'Something went wrong. Please try again later.';
        }
    }
}

$page_title = 'Register Interest';
include 'includes/header.php';
?>

<div class="page-hero">
    <div class="page-hero-inner">
        <div class="breadcrumb">
            <a href="index.php">Home</a> /
            <?php if ($programme && !$success): ?>
            <a href="programme_detail.php?id=<?= $programme['ProgrammeID'] ?>"><?= htmlspecialchars($programme['ProgrammeName']) ?></a> /
            <?php endif; ?>
            Register Interest
        </div>
        <h1>Register Your Interest</h1>
        <p>Let us know you're interested and we'll keep you updated about applications and open days.</p>
    </div>
</div>

<div class="register-wrap">
    <?php if ($success): ?>
    <!-- SUCCESS STATE -->
    <div style="text-align:center;padding:3rem 0;">
        <div style="font-size:4rem;margin-bottom:1rem;">🎉</div>
        <h2 style="font-family:'Playfair Display',serif;color:var(--navy);margin-bottom:.75rem;">You're Registered!</h2>
        <p style="color:var(--text-muted);margin-bottom:2rem;">
            Thank you for your interest in <strong><?= htmlspecialchars($programme['ProgrammeName']) ?></strong>.<br>
            We'll be in touch with the latest news and application details.
        </p>
        <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
    
            <a href="<?= BASE_URL ?>/student/my-interests.php" class="btn btn-gold">View My Interests →</a>
    
        </div>
    </div>

    <?php else: ?>
    <!-- FORM -->
    <div class="form-card">
        <h2 style="font-size:1.4rem;color:var(--navy);margin-bottom:.25rem;">Tell Us About Yourself</h2>
        <p style="color:var(--text-muted);font-size:.88rem;margin-bottom:1.75rem;">
            All fields are required. We will never share your details with third parties.
        </p>

        <?php foreach ($errors as $err): ?>
        <div class="alert alert-error">⚠️ <?= htmlspecialchars($err) ?></div>
        <?php endforeach; ?>

        <form method="POST" action="register_interest.php<?= $prog_id ? '?id=' . $prog_id : '' ?>" novalidate>
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name"
                       placeholder="e.g. Jane Smith"
                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                       required maxlength="100">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email"
                       placeholder="e.g. jane@example.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       required maxlength="255">
                <p class="form-note">We'll use this to send you programme updates.</p>
            </div>

            <div class="form-group">
                <label for="programme_id">Programme of Interest</label>
                <select id="programme_id" name="programme_id" required>
                    <option value="0">— Select a Programme —</option>
                    <?php
                    $selected_pid = (int)($_POST['programme_id'] ?? $prog_id);
                    $optgroup = '';
                    while ($p = $all_progs->fetch_assoc()):
                        if ($p['LevelName'] !== $optgroup) {
                            if ($optgroup) echo '</optgroup>';
                            echo '<optgroup label="' . htmlspecialchars($p['LevelName']) . '">';
                            $optgroup = $p['LevelName'];
                        }
                    ?>
                    <option value="<?= $p['ProgrammeID'] ?>" <?= $selected_pid === (int)$p['ProgrammeID'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['ProgrammeName']) ?>
                    </option>
                    <?php endwhile; if ($optgroup) echo '</optgroup>'; ?>
                </select>
            </div>

            <button type="submit" class="submit-btn">Submit Registration →</button>
        </form>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
