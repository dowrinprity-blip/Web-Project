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
$filter = $_GET['filter'] ?? 'pending';

// Validate filter - only allow valid values
$allowedFilters = ['pending', 'approved', 'rejected', 'all'];
if (!in_array($filter, $allowedFilters)) {
    $filter = 'pending';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    checkPostCsrf();
    
    $pa        = $_POST['action'] ?? '';
    $reqID     = (int)($_POST['request_id'] ?? 0);
    $adminNote = sanitize($_POST['admin_note'] ?? '');
    
    // Fetch the request using prepared statement
    $req = null;
    $stmt = $conn->prepare("SELECT * FROM StaffChangeRequests WHERE RequestID = ?");
    $stmt->bind_param('i', $reqID);
    $stmt->execute();
    $req = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$req) {
        $msg = 'Request not found.';
        $msgType = 'error';
    } elseif ($pa === 'approve') {
        // Log the approval attempt
        logSecurityEvent("Request approval", "Request ID: $reqID, Type: {$req['RequestType']}, Admin: " . ($_SESSION['admin_user'] ?? 'unknown'));
        
        // Apply the change with prepared statements
        if ($req['RequestType'] === 'profile') {
            // Update Staff name if provided
            if (!empty($req['NewName'])) {
                $stmt = $conn->prepare("UPDATE Staff SET Name = ? WHERE StaffID = ?");
                $stmt->bind_param('si', $req['NewName'], $req['StaffID']);
                $stmt->execute();
                $stmt->close();
            }
            
            // Update StaffAccounts bio and photo
            $stmt = $conn->prepare("UPDATE StaffAccounts SET Bio = ?, PhotoPath = ? WHERE StaffID = ?");
            $stmt->bind_param('ssi', $req['NewBio'], $req['NewPhotoPath'], $req['StaffID']);
            $stmt->execute();
            $stmt->close();
            
        } elseif ($req['RequestType'] === 'module') {
            // Update module name if provided
            if (!empty($req['NewModuleName'])) {
                $stmt = $conn->prepare("UPDATE Modules SET ModuleName = ? WHERE ModuleID = ?");
                $stmt->bind_param('si', $req['NewModuleName'], $req['ModuleID']);
                $stmt->execute();
                $stmt->close();
            }
            
            // Update module description if provided
            if (!empty($req['NewModuleDesc'])) {
                $stmt = $conn->prepare("UPDATE Modules SET Description = ? WHERE ModuleID = ?");
                $stmt->bind_param('si', $req['NewModuleDesc'], $req['ModuleID']);
                $stmt->execute();
                $stmt->close();
            }
        }
        
        // Mark approved
        $stmt = $conn->prepare("UPDATE StaffChangeRequests SET Status = 'approved', AdminNote = ?, ReviewedAt = NOW() WHERE RequestID = ?");
        $stmt->bind_param('si', $adminNote, $reqID);
        $stmt->execute();
        $stmt->close();
        
        $msg = '✅ Request approved and changes applied to the live site.';
        logSecurityEvent("Request approved", "Request ID: $reqID, Admin: " . ($_SESSION['admin_user'] ?? 'unknown'));
        
    } elseif ($pa === 'reject') {
        // Log the rejection
        logSecurityEvent("Request rejected", "Request ID: $reqID, Admin: " . ($_SESSION['admin_user'] ?? 'unknown'));
        
        $stmt = $conn->prepare("UPDATE StaffChangeRequests SET Status = 'rejected', AdminNote = ?, ReviewedAt = NOW() WHERE RequestID = ?");
        $stmt->bind_param('si', $adminNote, $reqID);
        $stmt->execute();
        $stmt->close();
        
        $msg = '❌ Request rejected.';
    }
}

// ==================== SECURE QUERIES ====================

// Fetch requests using prepared statement with parameter binding
$requests = [];
$sql = "SELECT r.*, st.Name AS StaffName, m.ModuleName
        FROM StaffChangeRequests r
        JOIN Staff st ON r.StaffID = st.StaffID
        LEFT JOIN Modules m ON r.ModuleID = m.ModuleID";

if ($filter !== 'all') {
    $sql .= " WHERE r.Status = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $filter);
} else {
    $stmt = $conn->prepare($sql);
}

if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
    $stmt->close();
}

// Get pending count using prepared statement
$pendingCount = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as c FROM StaffChangeRequests WHERE Status = 'pending'");
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $pendingCount = (int)$row['c'];
}
$stmt->close();

require_once 'layout.php';
adminHead('Change Requests');
adminSidebar('requests');
adminTopbar('Staff Change Requests');
?>

<?php if ($msg): ?>
    <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<?php if ($pendingCount > 0 && $filter !== 'pending'): ?>
    <div class="alert alert-warning">
        ⚠️ There are <strong><?= (int)$pendingCount ?></strong> pending request<?= $pendingCount != 1 ? 's' : '' ?> awaiting your review.
    </div>
<?php endif; ?>

<!-- FILTER TABS -->
<div style="display:flex;gap:.5rem;margin-bottom:1.5rem;">
    <?php 
    $filterLabels = [
        'pending' => '⏳ Pending',
        'approved' => '✅ Approved',
        'rejected' => '❌ Rejected',
        'all' => '📋 All'
    ];
    foreach ($filterLabels as $val => $label): 
    ?>
        <a href="?filter=<?= urlencode($val) ?>" 
           style="padding:.5rem 1.1rem;font-size:.85rem;font-weight:600;border-radius:var(--radius);text-decoration:none;<?= $filter === $val ? 'background:var(--navy);color:#fff;' : 'background:var(--cream);color:var(--navy);border:1px solid var(--border);' ?>">
            <?= htmlspecialchars($label) ?>
        </a>
    <?php endforeach; ?>
</div>

<?php if (empty($requests)): ?>
    <div class="panel" style="text-align:center;padding:3rem;color:var(--text-muted);">
        <div style="font-size:3rem;margin-bottom:1rem;">✅</div>
        <h3 style="font-family:'Playfair Display',serif;color:var(--navy);">No <?= ucfirst($filter) ?> Requests</h3>
        <p>Nothing to review here.</p>
    </div>
<?php else: ?>

<?php foreach ($requests as $r): ?>
<div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius-lg);padding:1.5rem;margin-bottom:1rem;box-shadow:0 2px 8px var(--shadow);">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;">
        <div style="flex:1;">
            <!-- Header -->
            <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.75rem;flex-wrap:wrap;">
                <span style="font-family:'Playfair Display',serif;font-size:1rem;font-weight:700;color:var(--navy);">
                    <?= $r['RequestType'] === 'profile' ? '👤 Profile Update' : '📚 Module Change' ?>
                </span>
                <span class="badge badge-<?= htmlspecialchars($r['Status']) ?>">
                    <?= ucfirst(htmlspecialchars($r['Status'])) ?>
                </span>
                <span style="font-size:.78rem;color:var(--text-muted);">
                    by <strong><?= htmlspecialchars($r['StaffName']) ?></strong> · 
                    <?= htmlspecialchars(date('d M Y, H:i', strtotime($r['SubmittedAt']))) ?>
                </span>
            </div>

            <!-- Request details -->
            <div style="background:var(--cream);border-radius:var(--radius);padding:1rem;margin-bottom:.75rem;font-size:.87rem;">
                <?php if ($r['RequestType'] === 'profile'): ?>
                    <?php if (!empty($r['NewName'])): ?>
                        <div><strong>New Name:</strong> <?= htmlspecialchars($r['NewName']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($r['NewBio'])): ?>
                        <div style="margin-top:.4rem;"><strong>New Bio:</strong> <?= htmlspecialchars($r['NewBio']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($r['NewPhotoPath'])): ?>
                        <div style="margin-top:.5rem;">
                            <strong>New Photo:</strong><br>
                            <img src="<?= htmlspecialchars($r['NewPhotoPath']) ?>" alt="Photo"
                                 style="width:60px;height:60px;object-fit:cover;border-radius:50%;margin-top:.35rem;border:2px solid var(--gold);">
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div><strong>Module:</strong> <?= htmlspecialchars($r['ModuleName'] ?? 'Unknown') ?></div>
                    <?php if (!empty($r['NewModuleName'])): ?>
                        <div style="margin-top:.4rem;"><strong>New Name:</strong> <?= htmlspecialchars($r['NewModuleName']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($r['NewModuleDesc'])): ?>
                        <div style="margin-top:.4rem;"><strong>New Description:</strong> <?= htmlspecialchars($r['NewModuleDesc']) ?></div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <?php if (!empty($r['AdminNote'])): ?>
                <div style="font-size:.82rem;color:#7a5c1e;background:#fdf6e3;border:1px solid #f0d080;border-radius:var(--radius);padding:.6rem .9rem;">
                    💬 Admin note: <?= htmlspecialchars($r['AdminNote']) ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- APPROVE / REJECT (only for pending) -->
        <?php if ($r['Status'] === 'pending'): ?>
        <div style="min-width:220px;">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <input type="hidden" name="request_id" value="<?= (int)$r['RequestID'] ?>">
                
                <div class="form-group" style="margin-bottom:.75rem;">
                    <label style="font-size:.78rem;font-weight:600;color:var(--navy);display:block;margin-bottom:.3rem;">Note to staff (optional)</label>
                    <textarea name="admin_note" rows="2" 
                              style="width:100%;padding:.5rem .75rem;border:1.5px solid var(--border);border-radius:var(--radius);font-size:.82rem;font-family:'DM Sans',sans-serif;resize:vertical;" 
                              placeholder="Reason for rejection, or approval note..."></textarea>
                </div>
                
                <div style="display:flex;gap:.5rem;">
                    <button name="action" value="approve" type="submit" class="btn btn-sm btn-success"
                            onclick="return confirm('Approve this request? Changes will go live immediately.')">
                        ✅ Approve
                    </button>
                    <button name="action" value="reject" type="submit" class="btn btn-sm btn-del"
                            onclick="return confirm('Reject this request?')">
                        ❌ Reject
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php adminFooter(); ?>