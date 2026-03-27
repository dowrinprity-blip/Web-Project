<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireStudent();

// Add security headers and CSRF token
setSecurityHeaders();
$csrf_token = generateCsrfToken();

$student = getLoggedInStudent();
$studentId = $student['AccountID'];

$msg = '';
$msgType = '';

// Get full student data
$stmt = $conn->prepare("SELECT * FROM StudentAccounts WHERE AccountID = ?");
$stmt->bind_param('i', $studentId);
$stmt->execute();
$studentData = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle photo upload with CSRF protection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_photo') {
    // CSRF check already in checkPostCsrf()
    checkPostCsrf();
    
    if (!empty($_FILES['profile_photo']['name'])) {
        $file = $_FILES['profile_photo'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            $msg = 'Photo must be JPG, PNG, GIF or WEBP.';
            $msgType = 'error';
            logSecurityEvent("Student photo upload failed", "Invalid file type: $mimeType, Student ID: $studentId");
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $msg = 'Photo must be under 2MB.';
            $msgType = 'error';
            logSecurityEvent("Student photo upload failed", "File too large: {$file['size']} bytes, Student ID: $studentId");
        } else {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = 'student_' . $studentId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $uploadDir = __DIR__ . '/../uploads/student_photos/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                // Delete old photo if exists
                if (!empty($studentData['Photo'])) {
                    $oldPath = $uploadDir . $studentData['Photo'];
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }
                
                $updateStmt = $conn->prepare("UPDATE StudentAccounts SET Photo = ? WHERE AccountID = ?");
                $updateStmt->bind_param('si', $filename, $studentId);
                if ($updateStmt->execute()) {
                    $msg = 'Profile photo updated successfully!';
                    $msgType = 'success';
                    logSecurityEvent("Student photo updated", "Student ID: $studentId");
                    // Refresh student data
                    $stmt2 = $conn->prepare("SELECT * FROM StudentAccounts WHERE AccountID = ?");
                    $stmt2->bind_param('i', $studentId);
                    $stmt2->execute();
                    $studentData = $stmt2->get_result()->fetch_assoc();
                    $stmt2->close();
                } else {
                    $msg = 'Error updating photo.';
                    $msgType = 'error';
                }
                $updateStmt->close();
            } else {
                $msg = 'Upload failed.';
                $msgType = 'error';
                logSecurityEvent("Student photo upload failed", "Move upload failed, Student ID: $studentId");
            }
        }
    }
}

// Handle profile update with CSRF protection and validation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    checkPostCsrf();
    
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $dob = $_POST['dob'] ?? '';
    
    // Validate phone number if provided
    if (!empty($phone) && !validatePhone($phone)) {
        $msg = 'Please enter a valid phone number (10-20 digits, +, -, spaces, parentheses).';
        $msgType = 'error';
    }
    // Validate date format if provided
    elseif (!empty($dob) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
        $msg = 'Please enter a valid date (YYYY-MM-DD).';
        $msgType = 'error';
    }
    // Validate address length
    elseif (strlen($address) > 500) {
        $msg = 'Address is too long (max 500 characters).';
        $msgType = 'error';
    }
    else {
        $stmt = $conn->prepare("UPDATE StudentAccounts SET Phone = ?, Address = ?, DateOfBirth = ? WHERE AccountID = ?");
        $stmt->bind_param('sssi', $phone, $address, $dob, $studentId);
        if ($stmt->execute()) {
            $msg = 'Profile updated successfully!';
            $msgType = 'success';
            logSecurityEvent("Student profile updated", "Student ID: $studentId");
            // Refresh student data
            $stmt2 = $conn->prepare("SELECT * FROM StudentAccounts WHERE AccountID = ?");
            $stmt2->bind_param('i', $studentId);
            $stmt2->execute();
            $studentData = $stmt2->get_result()->fetch_assoc();
            $stmt2->close();
        } else {
            $msg = 'Error updating profile.';
            $msgType = 'error';
        }
        $stmt->close();
    }
}

$initials = strtoupper(substr($studentData['FullName'], 0, 2));
$studentPhoto = !empty($studentData['Photo']) ? $studentData['Photo'] : '';
$studentPhotoUrl = $studentPhoto ? BASE_URL . '/uploads/student_photos/' . rawurlencode($studentPhoto) : '';
$hasPhoto = $studentPhoto && file_exists($_SERVER['DOCUMENT_ROOT'] . '/University/uploads/student_photos/' . $studentPhoto);

require_once 'layout.php';
studentHead('My Profile');
studentSidebar('profile', $studentData);
studentTopbar('My Profile', $studentData);
?>

<style>
    .profile-photo-container {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        margin: 1rem auto;
        background: var(--gold);
        color: var(--navy);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        font-weight: bold;
        overflow: hidden;
        border: 3px solid var(--gold);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .profile-photo-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
</style>

<?php if ($msg): ?>
    <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 280px 1fr; gap: 1.5rem;">
    <!-- PHOTO SECTION -->
    <div class="panel" style="text-align: center;">
        <h2>Profile Photo</h2>
        <div class="profile-photo-container">
            <?php if ($hasPhoto): ?>
                <img src="<?= htmlspecialchars($studentPhotoUrl) ?>" alt="Profile Photo">
            <?php else: ?>
                <?= htmlspecialchars($initials) ?>
            <?php endif; ?>
        </div>
        
        <form method="POST" enctype="multipart/form-data" style="margin-top: 1rem;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="action" value="upload_photo">
            <div class="form-group">
                <input type="file" name="profile_photo" accept="image/*" style="font-size: 0.8rem;">
                <small style="color: var(--text-muted); display: block; margin-top: 0.25rem;">
                    JPG, PNG, GIF, WEBP (Max 2MB)
                </small>
            </div>
            <button type="submit" class="btn btn-sm btn-add">Upload Photo</button>
        </form>
    </div>
    
    <!-- PROFILE FORM -->
    <div class="panel">
        <h2>👤 Personal Information</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="action" value="update_profile">
            
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" value="<?= htmlspecialchars($studentData['FullName']) ?>" disabled style="background: #f0f0f0;">
            </div>
            
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" value="<?= htmlspecialchars($studentData['Email']) ?>" disabled style="background: #f0f0f0;">
            </div>
            
            <div class="form-group">
                <label>Student ID</label>
                <input type="text" value="<?= htmlspecialchars($studentData['StudentID'] ?? 'Not assigned') ?>" disabled style="background: #f0f0f0;">
            </div>
            
            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" name="phone" value="<?= htmlspecialchars($studentData['Phone'] ?? '') ?>" placeholder="e.g., +44 1234 567890" maxlength="20">
            </div>
            
            <div class="form-group">
                <label>Address</label>
                <textarea name="address" rows="3" maxlength="500" placeholder="Your address"><?= htmlspecialchars($studentData['Address'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Date of Birth</label>
                <input type="date" name="dob" value="<?= htmlspecialchars($studentData['DateOfBirth'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label>Student Type</label>
                <input type="text" value="<?= ucfirst(htmlspecialchars($studentData['StudentType'])) ?>" disabled style="background: #f0f0f0;">
            </div>
            
            <button type="submit" class="btn btn-add">Update Profile →</button>
        </form>
    </div>
</div>

<?php studentFooter(); ?>