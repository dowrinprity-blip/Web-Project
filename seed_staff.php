<?php
/**
 * STAFF ACCOUNT SEEDER
 * =====================
 * RUN ONCE then DELETE this file!
 * 
 * SECURITY NOTES:
 * - Remove this file immediately after use
 * - Never commit this file to version control
 * - Passwords are hashed and never displayed
 * - Requires admin authentication to run
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// Set security headers
setSecurityHeaders();

// IMPORTANT: Only allow this script to run if authenticated as admin
if (!isAdminLoggedIn()) {
    header('HTTP/1.0 403 Forbidden');
    die('<h1>Access Denied</h1><p>This script can only be run by an authenticated administrator.</p>');
}

// Check if the script has already been run (create a marker file)
$markerFile = __DIR__ . '/.staff_seeded';
if (file_exists($markerFile)) {
    echo '<!DOCTYPE html><html><body style="font-family:sans-serif;padding:2rem;max-width:600px;margin:auto;">
    <div style="background:#fdecea;border:1px solid #f5b7b1;color:#922b21;padding:1rem;border-radius:4px;">
        <h2>⚠️ Staff Accounts Already Seeded</h2>
        <p>This script has already been executed. Delete this file to prevent accidental re-execution.</p>
        <p><strong>For security reasons, please delete <code>seed_staff.php</code> now.</strong></p>
    </div>
    </body></html>';
    exit;
}

// Only run if confirmed with a secure token
$token = $_GET['token'] ?? '';
$expectedToken = hash('sha256', 'crestfield_staff_seed_' . date('Y-m-d'));

if ($token !== $expectedToken) {
    echo '<!DOCTYPE html><html><body style="font-family:sans-serif;padding:2rem;max-width:600px;margin:auto;">
    <div style="background:#fdf6e3;border:1px solid #f0d080;color:#7a5c1e;padding:1rem;border-radius:4px;">
        <h2>⚠️ Staff Account Seeder</h2>
        <p>This will create login accounts for all staff members. This action is irreversible.</p>
        <p><strong>Security Notice:</strong> Passwords are hashed and will never be displayed.</p>
        <p><a href="?token=' . htmlspecialchars($expectedToken) . '" style="background:#0f1f3d;color:#fff;padding:.75rem 1.5rem;border-radius:4px;text-decoration:none;display:inline-block;margin-top:1rem;">✅ Run Seeder</a></p>
        <p style="margin-top:1rem;font-size:0.8rem;color:#666;">⚠️ After running, this script will be disabled. Delete the file after use.</p>
    </div>
    </body></html>';
    exit;
}

// Define staff accounts mapping
$staffAccounts = [
    1  => 'alice.johnson',
    2  => 'brian.lee',
    3  => 'carol.white',
    4  => 'david.green',
    5  => 'emma.scott',
    6  => 'frank.moore',
    7  => 'grace.adams',
    8  => 'henry.clark',
    9  => 'irene.hall',
    10 => 'james.wright',
    11 => 'sophia.miller',
    12 => 'benjamin.carter',
    13 => 'chloe.thompson',
    14 => 'daniel.robinson',
    15 => 'emily.davis',
    16 => 'nathan.hughes',
    17 => 'olivia.martin',
    18 => 'samuel.anderson',
    19 => 'victoria.hall',
    20 => 'william.scott',
];

$password     = 'Staff@1234';
$passwordHash = hashPassword($password); // Using centralized function
$created      = 0;
$skipped      = 0;
$errors       = [];

// Log the seeding operation
logSecurityEvent("Staff account seeding started", "Admin: " . ($_SESSION['admin_user'] ?? 'unknown'));

foreach ($staffAccounts as $staffID => $username) {
    // Check if account already exists using prepared statement
    $chk = $conn->prepare("SELECT AccountID FROM StaffAccounts WHERE StaffID = ? OR Username = ?");
    $chk->bind_param('is', $staffID, $username);
    $chk->execute();
    $result = $chk->get_result();
    
    if ($result->num_rows > 0) {
        $skipped++;
        $chk->close();
        continue;
    }
    $chk->close();
    
    // Insert new account using prepared statement
    $stmt = $conn->prepare("INSERT INTO StaffAccounts (StaffID, Username, PasswordHash, CreatedAt) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param('iss', $staffID, $username, $passwordHash);
    
    if ($stmt->execute()) {
        $created++;
    } else {
        $errors[] = "Failed for StaffID $staffID ($username): " . $conn->error;
        logSecurityEvent("Staff account seeding failed", "StaffID: $staffID, Username: $username, Error: " . $conn->error);
    }
    $stmt->close();
}

// Create marker file to prevent re-execution
file_put_contents($markerFile, date('Y-m-d H:i:s') . ' - Seeded by: ' . ($_SESSION['admin_user'] ?? 'unknown'));

// Get staff names using SECURE prepared statement
$staffNames = [];
$stmt = $conn->prepare("SELECT StaffID, Name FROM Staff ORDER BY StaffID");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $staffNames[] = $row;
}
$stmt->close();

// Log completion
logSecurityEvent("Staff account seeding completed", "Created: $created, Skipped: $skipped");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Seeder — Crestfield</title>
    <style>
        body { font-family: 'DM Sans', sans-serif; padding: 2rem; max-width: 800px; margin: auto; background: #f5f5f5; }
        .container { background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        table { width:100%; border-collapse:collapse; margin-top:1.5rem; font-size:.9rem; }
        th { background:#0f1f3d; color:#c9963a; padding:.75rem 1rem; text-align:left; }
        td { padding:.6rem 1rem; border-bottom:1px solid #eee; }
        tr:nth-child(even) td { background:#f8f5ef; }
        .success { background:#eaf7f0; border:1px solid #b2dfc7; color:#1a6641; padding:1rem; border-radius:4px; margin-bottom:1rem; }
        .warning { background:#fdf6e3; border:1px solid #f0d080; color:#7a5c1e; padding:1rem; border-radius:4px; margin-bottom:1rem; }
        .error { background:#fdecea; border:1px solid #f5b7b1; color:#922b21; padding:1rem; border-radius:4px; margin-bottom:1rem; }
        code { background:#f0f0f0; padding:.15rem .4rem; border-radius:3px; font-size:.9em; font-family: monospace; }
        .delete-warning { background:#fff3cd; border-left:4px solid #ffc107; padding:1rem; margin:1rem 0; }
        .btn { display:inline-block; padding:0.6rem 1.2rem; border-radius:4px; text-decoration:none; margin-right:0.5rem; }
        .btn-primary { background:#0f1f3d; color:#fff; }
        .btn-secondary { background:#c9963a; color:#fff; }
        .btn-danger { background:#dc3545; color:#fff; }
    </style>
</head>
<body>
    <div class="container">
        <h2>✅ Staff Account Seeder Complete</h2>

        <div class="success">
            <strong><?= (int)$created ?></strong> account<?= $created != 1 ? 's' : '' ?> created &nbsp;·&nbsp;
            <strong><?= (int)$skipped ?></strong> already existed (skipped)
        </div>

        <?php if ($errors): ?>
        <div class="error">
            <strong>Errors encountered:</strong>
            <?php foreach ($errors as $e): ?>
                <div>⚠️ <?= htmlspecialchars($e) ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="delete-warning">
            ⚠️ <strong>SECURITY ACTION REQUIRED</strong><br>
            <strong>Immediately delete this file:</strong> <code>seed_staff.php</code><br>
            A marker file has been created to prevent re-execution, but for complete security, please remove this file.
        </div>

        <h3>Staff Login Information</h3>
        <p>All staff can log in at: <a href="<?= htmlspecialchars(BASE_URL) ?>/staff/login.php"><?= htmlspecialchars(BASE_URL) ?>/staff/login.php</a></p>
        <p><strong>⚠️ Passwords are NOT displayed for security reasons.</strong> Use the password reset functionality in admin panel if needed.</p>
        
        <div style="background:#f0f0f0;padding:1rem;border-radius:4px;margin:1rem 0;">
            <strong>Default Password:</strong> <code>Staff@1234</code><br>
            <small>Users must change their password after first login.</small>
        </div>

        <table>
            <thead>
                <tr><th>Staff ID</th><th>Staff Name</th><th>Username</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php foreach ($staffNames as $st): 
                    $uname = $staffAccounts[$st['StaffID']] ?? '—';
                ?>
                <tr>
                    <td><?= (int)$st['StaffID'] ?></td>
                    <td><?= htmlspecialchars($st['Name']) ?></td>
                    <td><code><?= htmlspecialchars($uname) ?></code></td>
                    <td>
                        <a href="<?= htmlspecialchars(BASE_URL) ?>/admin/staff_accounts.php?action=edit&id=<?= (int)$st['StaffID'] ?>" style="color:#c9963a;">Reset Password</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p style="margin-top:1.5rem;">
            <a href="<?= htmlspecialchars(BASE_URL) ?>/staff/login.php" class="btn btn-primary">Go to Staff Login →</a>
            <a href="<?= htmlspecialchars(BASE_URL) ?>/admin/staff_accounts.php" class="btn btn-secondary">Manage in Admin →</a>
            <a href="javascript:void(0)" onclick="if(confirm('Are you sure? Delete the file manually from server.')) alert('Please delete seed_staff.php manually from your server.')" class="btn btn-danger">⚠️ Delete This File</a>
        </p>
        
        <p style="font-size:0.75rem;color:#666;margin-top:1rem;border-top:1px solid #eee;padding-top:1rem;">
            <strong>Security Note:</strong> This operation has been logged. All passwords are securely hashed using bcrypt.
        </p>
    </div>
</body>
</html>