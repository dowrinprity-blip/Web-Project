<?php
require_once 'includes/db.php';
require_once 'includes/auth.php'; // For security headers and functions

// Set security headers
setSecurityHeaders();

$page_title = 'My Interests';

// Generate CSRF token for forms
$csrf_token = generateCsrfToken();

// Initialize variables
$email = '';
$interests = [];
$error = '';
$success = '';

// Handle form submission (view interests) - POST only
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'view') {
    // Verify CSRF token
    checkPostCsrf();
    
    $email = sanitize(trim($_POST['email'] ?? ''));
    
    // Validate email format
    if (!validateEmail($email)) {
        $error = 'Please enter a valid email address.';
        $email = '';
    }
}

// Handle removal of interest - POST only (SECURE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove') {
    // Verify CSRF token
    checkPostCsrf();
    
    $removeID = (int)($_POST['remove_id'] ?? 0);
    $email = sanitize(trim($_POST['email'] ?? ''));
    
    // Validate email format
    if (!validateEmail($email)) {
        $error = 'Invalid email address.';
    } elseif ($removeID <= 0) {
        $error = 'Invalid programme selection.';
    } else {
        // Verify this interest belongs to this email before deleting
        $checkStmt = $conn->prepare("SELECT ProgrammeID FROM interestedstudents WHERE ProgrammeID = ? AND Email = ?");
        $checkStmt->bind_param("is", $removeID, $email);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows === 0) {
            $error = 'Interest not found or already removed.';
        } else {
            // Secure deletion
            $stmt = $conn->prepare("DELETE FROM interestedstudents WHERE ProgrammeID = ? AND Email = ?");
            $stmt->bind_param("is", $removeID, $email);
            
            if ($stmt->execute()) {
                $success = 'Interest removed successfully.';
                logSecurityEvent("Interest removed", "Email: $email, Programme ID: $removeID");
            } else {
                $error = 'Failed to remove interest. Please try again.';
            }
            $stmt->close();
        }
        $checkStmt->close();
    }
}

// Fetch interests if email is valid
if (!empty($email) && validateEmail($email)) {
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
    $stmt->close();
}

include 'includes/header.php';
?>

<section class="section">
    <div class="section-inner" style="max-width:1000px;margin:0 auto;">

        <div class="section-header" style="display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;margin-bottom:2rem;">
            <h2 style="margin:0;">View Your Interests</h2>
            <p style="margin-top:.4rem;color:var(--text-muted);font-size:.95rem;">
                Enter your email to see your programmes.
            </p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error" style="background:#fdecea;border:1px solid #f5b7b1;color:#922b21;padding:.9rem 1.2rem;border-radius:var(--radius);margin-bottom:1.2rem;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success" style="background:#eaf7f0;border:1px solid #b2dfc7;color:#1a6641;padding:.9rem 1.2rem;border-radius:var(--radius);margin-bottom:1.2rem;">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <!-- SECURE FORM with CSRF Protection -->
        <form method="POST" action="" 
              style="display:flex;justify-content:center;align-items:center;gap:.5rem;margin-bottom:2rem;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="action" value="view">
            
            <input type="email" name="email"
                   placeholder="Enter your email"
                   value="<?= htmlspecialchars($email) ?>"
                   required
                   maxlength="100"
                   style="padding:.6rem .9rem;font-size:1rem;width:260px;border:1px solid var(--border);border-radius:var(--radius);">

            <button type="submit" class="btn btn-navy">
                View
            </button>
        </form>

        <?php if (!empty($email) && validateEmail($email)): ?>

            <?php if (!empty($interests)): ?>

                <div class="cards-grid">

                    <?php foreach ($interests as $i): ?>

                        <?php 
                        $img = !empty($i['Image']) ? htmlspecialchars($i['Image']) : 'uploads/placeholder.jpg';
                        $programmeName = htmlspecialchars($i['ProgrammeName']);
                        $description = htmlspecialchars(substr($i['Description'] ?? '', 0, 100));
                        $registeredDate = date('d M Y', strtotime($i['RegisteredAt']));
                        ?>

                        <div class="card">
                            <div class="card-colour-bar"></div>
                            <img src="<?= $img ?>" alt="<?= $programmeName ?>">
                            
                            <div class="card-body">
                                <h3><?= $programmeName ?></h3>
                                <p><?= $description ?>…</p>
                            </div>

                            <!-- SECURE FOOTER with POST forms for destructive actions -->
                            <div class="card-footer">
                                <span><?= htmlspecialchars($registeredDate) ?></span>
                                
                                <div style="display:flex;gap:.4rem;align-items:center;">
                                    <!-- View Programme (GET is safe - non-destructive) -->
                                    <a href="<?= htmlspecialchars(BASE_URL) ?>/programme_detail.php?id=<?= (int)$i['ProgrammeID'] ?>" 
                                       title="View" 
                                       class="btn-go"
                                       style="padding:.3rem .7rem;background:var(--gold);color:#fff;border-radius:var(--radius);text-decoration:none;">
                                        →
                                    </a>

                                    <!-- SECURE Remove Button (POST with CSRF) -->
                                    <form method="POST" action="" style="display:inline;" 
                                          onsubmit="return confirm('Remove this interest? This action cannot be undone.')">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="remove_id" value="<?= (int)$i['ProgrammeID'] ?>">
                                        <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                                        <button type="submit" 
                                                class="btn-danger" 
                                                style="background:#c0392b;color:#fff;border:none;padding:.3rem .7rem;border-radius:var(--radius);cursor:pointer;">
                                            ✕
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <p style="text-align:center;color:var(--text-muted);font-weight:500;">
                    No interests found for <strong><?= htmlspecialchars($email) ?></strong>
                </p>
                <p style="text-align:center;font-size:.85rem;margin-top:.5rem;">
                    <a href="<?= htmlspecialchars(BASE_URL) ?>/programmes.php" style="color:var(--gold);">Browse programmes</a> to register your interest.
                </p>

            <?php endif; ?>

        <?php endif; ?>

    </div>
</section>

<?php include 'includes/footer.php'; ?>