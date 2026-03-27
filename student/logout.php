<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

if (isStudentLoggedIn()) {
    $student = getLoggedInStudent();
    logSecurityEvent("Student logout", "Email: " . ($student['Email'] ?? 'Unknown'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkPostCsrf();
    studentLogout();
    exit;
}

// For GET requests, show confirmation
$csrf_token = generateCsrfToken();
setSecurityHeaders();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Confirm Logout</title>
    <style>
        body { font-family: sans-serif; background: #0a2540; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .card { background: white; padding: 2rem; border-radius: 12px; text-align: center; max-width: 400px; }
        .btn { padding: 10px 20px; margin: 10px; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-logout { background: #c5a028; color: white; }
        .btn-cancel { background: #e0e0e0; color: #333; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Confirm Logout</h2>
        <p>Are you sure you want to logout?</p>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <button type="submit" class="btn btn-logout">Yes, Logout</button>
            <a href="dashboard.php" class="btn btn-cancel">Cancel</a>
        </form>
    </div>
</body>
</html>