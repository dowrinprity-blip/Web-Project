<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Only admins can access
requireAdmin();

// Generate CSRF token for forms
$csrf_token = generateCsrfToken();

// Set security headers
setSecurityHeaders();

$msg = '';
$msgType = 'success';
$uploadDir = __DIR__ . '/../uploads/staff_photos/';
$uploadUrl = BASE_URL . '/uploads/staff_photos/';

// Ensure upload directory exists with proper permissions
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    checkPostCsrf();
    
    $pa   = $_POST['action']   ?? '';
    $name = sanitize($_POST['name'] ?? '');
    $bio  = sanitize($_POST['bio'] ?? '');
    $sid  = (int)($_POST['staff_id'] ?? 0);
    
    // Validate name
    if (empty($name)) {
        $msg = 'Name is required.';
        $msgType = 'error';
    }
    // Validate bio length
    elseif (strlen($bio) > 500) {
        $msg = 'Bio cannot exceed 500 characters.';
        $msgType = 'error';
    }
    else {
        // Handle photo upload securely
        $newPhoto = null;
        $oldPhoto = null;
        
        // Get old photo if updating
        if ($pa === 'update' && $sid > 0) {
            $stmt = $conn->prepare("SELECT Photo FROM Staff WHERE StaffID = ?");
            $stmt->bind_param('i', $sid);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $oldPhoto = $row['Photo'];
            }
            $stmt->close();
        }
        
        if (!empty($_FILES['photo']['name'])) {
            // SECURE FILE UPLOAD VALIDATION
            $file = $_FILES['photo'];
            
            // Check upload error
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $msg = 'File upload failed. Error code: ' . $file['error'];
                $msgType = 'error';
            }
            else {
                // Validate file type by actual content (not just extension)
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                
                $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                if (!in_array($mimeType, $allowedTypes)) {
                    $msg = 'Photo must be JPG, PNG, WebP or GIF.';
                    $msgType = 'error';
                }
                // Validate file size
                elseif ($file['size'] > 3 * 1024 * 1024) {
                    $msg = 'Photo must be under 3MB.';
                    $msgType = 'error';
                }
                // Validate file extension matches mime type
                else {
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                    
                    if (!in_array($ext, $allowedExts)) {
                        $msg = 'Invalid file extension.';
                        $msgType = 'error';
                    }
                    else {
                        // Generate secure filename
                        $filename = 'staff_' . ($sid > 0 ? $sid : time()) . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                        $targetPath = $uploadDir . $filename;
                        
                        // Move uploaded file
                        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                            $newPhoto = $filename;
                            
                            // Delete old photo if it exists
                            if ($oldPhoto && file_exists($uploadDir . $oldPhoto)) {
                                unlink($uploadDir . $oldPhoto);
                            }
                            
                            logSecurityEvent("Staff photo uploaded", "Staff ID: $sid, File: $filename, Admin: " . ($_SESSION['admin_user'] ?? 'unknown'));
                        } else {
                            $msg = 'Upload failed — check folder permissions on uploads/staff_photos/';
                            $msgType = 'error';
                        }
                    }
                }
            }
        }
        
        // Process form if no upload errors
        if (empty($msg)) {
            if ($pa === 'create') {
                $stmt = $conn->prepare("INSERT INTO Staff (Name, Bio, Photo) VALUES (?, ?, ?)");
                $stmt->bind_param('sss', $name, $bio, $newPhoto);
                
                if ($stmt->execute()) {
                    $newId = $conn->insert_id;
                    $msg = "Staff member '$name' added.";
                    $msgType = 'success';
                    logSecurityEvent("Staff created", "Name: $name, ID: $newId, Admin: " . ($_SESSION['admin_user'] ?? 'unknown'));
                } else {
                    $msg = 'Error: ' . $conn->error;
                    $msgType = 'error';
                }
                $stmt->close();
                
            } elseif ($pa === 'update') {
                if ($newPhoto) {
                    $stmt = $conn->prepare("UPDATE Staff SET Name = ?, Bio = ?, Photo = ? WHERE StaffID = ?");
                    $stmt->bind_param('sssi', $name, $bio, $newPhoto, $sid);
                } else {
                    $stmt = $conn->prepare("UPDATE Staff SET Name = ?, Bio = ? WHERE StaffID = ?");
                    $stmt->bind_param('ssi', $name, $bio, $sid);
                }
                
                if ($stmt->execute()) {
                    $msg = 'Staff member updated successfully.';
                    $msgType = 'success';
                    logSecurityEvent("Staff updated", "Staff ID: $sid, Admin: " . ($_SESSION['admin_user'] ?? 'unknown'));
                } else {
                    $msg = 'Error: ' . $conn->error;
                    $msgType = 'error';
                }
                $stmt->close();
                
            } elseif ($pa === 'delete') {
                // Get photo before deletion
                $stmt = $conn->prepare("SELECT Photo FROM Staff WHERE StaffID = ?");
                $stmt->bind_param('i', $sid);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $oldPhoto = $row['Photo'];
                }
                $stmt->close();
                
                // Delete from database
                $stmt = $conn->prepare("DELETE FROM Staff WHERE StaffID = ?");
                $stmt->bind_param('i', $sid);
                
                if ($stmt->execute()) {
                    // Delete associated photo
                    if ($oldPhoto && file_exists($uploadDir . $oldPhoto)) {
                        unlink($uploadDir . $oldPhoto);
                    }
                    $msg = 'Staff member removed.';
                    $msgType = 'success';
                    logSecurityEvent("Staff deleted", "Staff ID: $sid, Admin: " . ($_SESSION['admin_user'] ?? 'unknown'));
                } else {
                    $msg = 'Error: ' . $conn->error;
                    $msgType = 'error';
                }
                $stmt->close();
            }
        }
    }
}

// ==================== SECURE QUERIES ====================

// Get staff list with counts using prepared statements
$staff = [];
$stmt = $conn->prepare("
    SELECT s.*,
    (SELECT COUNT(*) FROM Modules m WHERE m.ModuleLeaderID = s.StaffID) AS ModsLed,
    (SELECT COUNT(*) FROM Programmes p WHERE p.ProgrammeLeaderID = s.StaffID) AS ProgsLed
    FROM Staff s ORDER BY s.Name
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $staff[] = $row;
}
$stmt->close();

require_once 'layout.php';
adminHead('Staff');
adminSidebar('staff');
adminTopbar('Staff Members');

function staffInitials(string $name): string {
    $init = '';
    $words = explode(' ', $name);
    foreach ($words as $w) {
        if (!empty($w) && ctype_alpha($w[0])) {
            $init .= $w[0];
        }
    }
    return strtoupper(substr($init, 0, 2));
}
?>

<style>
.staff-avatar {
    width: 48px; height: 48px; border-radius: 50%;
    background: var(--navy); color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-family: 'Playfair Display', serif; font-size: 1rem; font-weight: 700;
    overflow: hidden; flex-shrink: 0;
    border: 2px solid var(--gold);
    box-shadow: 0 2px 8px rgba(11,29,58,.15);
}
.staff-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
.preview-circle {
    width: 72px; height: 72px; border-radius: 50%;
    background: var(--navy); color: #fff; overflow: hidden;
    display: flex; align-items: center; justify-content: center;
    font-family: 'Playfair Display', serif; font-weight: 700; font-size: 1.4rem;
    border: 3px solid var(--gold); flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(11,29,58,.2);
}
.preview-circle img { width: 100%; height: 100%; object-fit: cover; }
.photo-row { display: flex; align-items: center; gap: 1rem; margin-top: .4rem; }
</style>

<?php if ($msg): ?>
<div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div style="display:grid; grid-template-columns:1fr 340px; gap:1.75rem; align-items:start;">

    <!-- STAFF TABLE -->
    <div class="admin-table-wrap">
        <table class="admin-table" aria-label="Staff members list">
            <thead>
                <tr>
                    <th style="width:52px;padding-right:0">Photo</th>
                    <th>Name</th>
                    <th style="text-align:center">Modules</th>
                    <th style="text-align:center">Programmes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($staff as $s):
                $hasPhoto = !empty($s['Photo']);
                $photoSrc = $uploadUrl . rawurlencode($s['Photo'] ?? '');
                $inits    = staffInitials($s['Name']);
            ?>
            <tr>
                <td style="padding-right:0">
                    <div class="staff-avatar">
                        <?php if ($hasPhoto): ?>
                            <img src="<?= htmlspecialchars($photoSrc) ?>"
                                 alt="<?= htmlspecialchars($s['Name']) ?>"
                                 onerror="this.parentNode.innerHTML='<?= htmlspecialchars($inits) ?>'">
                        <?php else: ?>
                            <?= htmlspecialchars($inits) ?>
                        <?php endif; ?>
                    </div>
                </td>
                <td>
                    <strong style="font-size:.9rem"><?= htmlspecialchars($s['Name']) ?></strong>
                    <?php if (!empty($s['Bio'])): ?>
                        <div style="font-size:.76rem;color:var(--text-muted);margin-top:.15rem;max-width:280px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                            <?= htmlspecialchars($s['Bio']) ?>
                        </div>
                    <?php endif; ?>
                </td>
                <td style="text-align:center;color:var(--text-muted)"><?= (int)$s['ModsLed'] ?></td>
                <td style="text-align:center;color:var(--text-muted)"><?= (int)$s['ProgsLed'] ?></td>
                <td>
                    <div style="display:flex;gap:.4rem;flex-wrap:wrap;">
                        <button class="btn btn-sm btn-edit"
                            onclick='editStaff(
                                <?= (int)$s["StaffID"] ?>,
                                <?= json_encode(htmlspecialchars($s["Name"])) ?>,
                                <?= json_encode(htmlspecialchars($s["Bio"] ?? "")) ?>,
                                <?= json_encode($hasPhoto ? $photoSrc : "") ?>,
                                <?= json_encode($inits) ?>
                            )'>Edit</button>
                        <form method="POST" style="display:inline;" 
                              onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($s['Name'])) ?>? This action cannot be undone.')">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            <input type="hidden" name="action"   value="delete">
                            <input type="hidden" name="staff_id" value="<?= (int)$s['StaffID'] ?>">
                            <button type="submit" class="btn btn-sm btn-del">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- ADD / EDIT FORM -->
    <div class="admin-form-wrap" id="staff-form-card">
        <h2 id="form-title">+ Add Staff Member</h2>
        <form method="POST" id="staff-form" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="action"   value="create" id="form-action">
            <input type="hidden" name="staff_id" value=""       id="form-staff-id">

            <div class="form-group">
                <label for="staff-name">Full Name *</label>
                <input id="staff-name" type="text" name="name" required 
                       placeholder="e.g. Dr. Jane Smith"
                       maxlength="100">
            </div>

            <div class="form-group">
                <label for="staff-bio">Short Bio (max 500 characters)</label>
                <textarea id="staff-bio" name="bio" rows="3" maxlength="500"
                    placeholder="Brief professional bio…" style="resize:vertical;font-family:inherit;"></textarea>
                <small style="color:var(--text-muted);font-size:.7rem;"><span id="bio-counter">0</span>/500 characters</small>
            </div>

            <div class="form-group">
                <label>Profile Photo</label>
                <div class="photo-row">
                    <div class="preview-circle" id="preview-circle">
                        <img id="preview-img" src="" alt="Preview" style="display:none;">
                        <span id="preview-initials">?</span>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <input type="file" id="staff-photo" name="photo"
                               accept="image/jpeg,image/png,image/webp,image/gif"
                               onchange="previewPhoto(this)"
                               style="width:100%;font-size:.82rem;">
                        <p style="font-size:.74rem;color:var(--text-muted);margin-top:.35rem;line-height:1.5;">
                            JPG, PNG, WebP or GIF &middot; Max 3MB<br>
                            Leave blank to keep existing photo.
                        </p>
                    </div>
                </div>
            </div>

            <div style="display:flex;gap:.75rem;margin-top:.25rem;">
                <button type="submit" id="form-btn" class="btn btn-sm btn-add">Add Member</button>
                <button type="button" id="cancel-btn" onclick="resetForm()"
                    class="btn btn-sm"
                    style="background:var(--cream);color:var(--navy);border:1px solid var(--border);display:none;">
                    Cancel
                </button>
            </div>
        </form>
    </div>

</div><!-- /grid -->

<script>
// Bio character counter
const bioTextarea = document.getElementById('staff-bio');
const bioCounter = document.getElementById('bio-counter');

if (bioTextarea && bioCounter) {
    bioTextarea.addEventListener('input', function() {
        bioCounter.textContent = this.value.length;
    });
}

function previewPhoto(input) {
    if (!input.files || !input.files[0]) return;
    
    // Validate file type client-side
    const file = input.files[0];
    const allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!allowedTypes.includes(file.type)) {
        alert('Invalid file type. Please upload JPG, PNG, WebP, or GIF.');
        input.value = '';
        return;
    }
    
    // Validate file size
    if (file.size > 3 * 1024 * 1024) {
        alert('File size must be under 3MB.');
        input.value = '';
        return;
    }
    
    const reader = new FileReader();
    reader.onload = e => {
        const img   = document.getElementById('preview-img');
        const inits = document.getElementById('preview-initials');
        img.src           = e.target.result;
        img.style.display = 'block';
        inits.style.display = 'none';
    };
    reader.readAsDataURL(file);
}

function editStaff(id, name, bio, photoSrc, initials) {
    document.getElementById('form-title').textContent    = '✏️ Edit Staff Member';
    document.getElementById('form-action').value         = 'update';
    document.getElementById('form-staff-id').value       = id;
    document.getElementById('staff-name').value          = name;
    document.getElementById('staff-bio').value           = bio;
    if (bioCounter) bioCounter.textContent = bio.length;
    document.getElementById('form-btn').textContent      = 'Save Changes';
    document.getElementById('cancel-btn').style.display  = 'inline-flex';

    const img   = document.getElementById('preview-img');
    const inits = document.getElementById('preview-initials');
    if (photoSrc) {
        img.src             = photoSrc;
        img.style.display   = 'block';
        inits.style.display = 'none';
    } else {
        img.style.display     = 'none';
        inits.textContent     = initials;
        inits.style.display   = 'block';
    }

    document.getElementById('staff-name').focus();
    document.getElementById('staff-form-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function resetForm() {
    document.getElementById('form-title').textContent     = '+ Add Staff Member';
    document.getElementById('form-action').value          = 'create';
    document.getElementById('form-staff-id').value        = '';
    document.getElementById('staff-name').value           = '';
    document.getElementById('staff-bio').value            = '';
    if (bioCounter) bioCounter.textContent = '0';
    document.getElementById('form-btn').textContent       = 'Add Member';
    document.getElementById('cancel-btn').style.display   = 'none';
    document.getElementById('staff-photo').value          = '';
    document.getElementById('preview-img').style.display  = 'none';
    document.getElementById('preview-initials').textContent = '?';
    document.getElementById('preview-initials').style.display = 'block';
}
</script>

<?php adminFooter(); ?>