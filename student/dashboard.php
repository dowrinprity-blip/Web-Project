<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireStudent();

// Add security headers and CSRF token
setSecurityHeaders();
$csrf_token = generateCsrfToken();

$student = getLoggedInStudent();
$studentId = $student['AccountID'];

// Log dashboard access
logSecurityEvent("Student dashboard accessed", "Student ID: $studentId, Email: " . ($student['Email'] ?? 'unknown'));

// Get full student data including photo
$stmt = $conn->prepare("SELECT * FROM StudentAccounts WHERE AccountID = ?");
$stmt->bind_param('i', $studentId);
$stmt->execute();
$studentData = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ==================== GET ENROLLMENT DETAILS ====================
$enrollment = null;
$stmt = $conn->prepare("
    SELECT e.*, p.ProgrammeName, p.Description as ProgrammeDesc, l.LevelName, l.LevelID
    FROM StudentEnrollment e
    JOIN Programmes p ON e.ProgrammeID = p.ProgrammeID
    JOIN Levels l ON p.LevelID = l.LevelID
    WHERE e.StudentID = ? AND e.EnrollmentStatus = 'active'
");
$stmt->bind_param('i', $studentId);
$stmt->execute();
$enrollment = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ==================== GET MODULES ====================
$modules = [];
if ($enrollment) {
    $stmt = $conn->prepare("
        SELECT m.*, m.ModuleLeaderID, s.Name AS LecturerName, s.Photo AS LecturerPhoto, s.Bio AS LecturerBio,
               pm.Year
        FROM ProgrammeModules pm
        JOIN Modules m ON pm.ModuleID = m.ModuleID
        LEFT JOIN Staff s ON m.ModuleLeaderID = s.StaffID
        WHERE pm.ProgrammeID = ?
        ORDER BY pm.Year, m.ModuleName
    ");
    $stmt->bind_param('i', $enrollment['ProgrammeID']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $modules[] = $row;
    }
    $stmt->close();
}

// ==================== GET GRADES ====================
$grades = [];
$stmt = $conn->prepare("
    SELECT g.*, m.ModuleName
    FROM StudentGrades g
    JOIN Modules m ON g.ModuleID = m.ModuleID
    WHERE g.StudentID = ?
    ORDER BY g.AcademicYear DESC, m.ModuleName
");
$stmt->bind_param('i', $studentId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $grades[] = $row;
}
$stmt->close();

// ==================== GET ATTENDANCE ====================
$attendance = [];
$stmt = $conn->prepare("
    SELECT a.*, m.ModuleName
    FROM StudentAttendance a
    JOIN Modules m ON a.ModuleID = m.ModuleID
    WHERE a.StudentID = ?
    ORDER BY a.AttendanceDate DESC
    LIMIT 20
");
$stmt->bind_param('i', $studentId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $attendance[] = $row;
}
$stmt->close();

// ==================== GET ATTENDANCE SUMMARY ====================
$attendanceSummary = [];
$stmt = $conn->prepare("
    SELECT Status, COUNT(*) as count
    FROM StudentAttendance
    WHERE StudentID = ? AND AttendanceDate >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY Status
");
$stmt->bind_param('i', $studentId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $attendanceSummary[$row['Status']] = $row['count'];
}
$stmt->close();

$totalAttendance = array_sum($attendanceSummary);
$presentRate = $totalAttendance > 0 ? round(($attendanceSummary['present'] ?? 0) / $totalAttendance * 100) : 0;

require_once 'layout.php';
studentHead('Dashboard');
studentSidebar('dashboard', $studentData);
studentTopbar('Dashboard', $studentData);
?>

<!-- STAT CARDS -->
<div class="stat-cards">
    <div class="stat-card">
        <div class="stat-card-num"><?= $enrollment ? htmlspecialchars($enrollment['ProgrammeName']) : 'Not Enrolled' ?></div>
        <div class="stat-card-label">Programme</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-num"><?= count($grades) ?></div>
        <div class="stat-card-label">Grades Recorded</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-num"><?= $presentRate ?>%</div>
        <div class="stat-card-label">Attendance Rate</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-num"><?= $enrollment ? (int)$enrollment['ExpectedGraduationYear'] : 'N/A' ?></div>
        <div class="stat-card-label">Expected Graduation</div>
    </div>
</div>

<!-- WELCOME PANEL -->
<div class="panel">
    <h2>Welcome, <?= htmlspecialchars(explode(' ', $studentData['FullName'])[0]) ?> 👋</h2>
    <?php if ($enrollment): ?>
        <p>You are enrolled in <strong><?= htmlspecialchars($enrollment['ProgrammeName']) ?></strong> (<?= htmlspecialchars($enrollment['LevelName']) ?>)</p>
        <p style="color: var(--text-muted); font-size: 0.85rem;"><?= htmlspecialchars($enrollment['ProgrammeDesc'] ?? '') ?></p>
    <?php else: ?>
        <p>You are not currently enrolled in any programme. Please contact the admissions office.</p>
    <?php endif; ?>
</div>

<!-- MY MODULES SECTION -->
<div class="panel">
    <h2>📚 My Modules (<?= count($modules) ?>)</h2>
    <?php if (empty($modules)): ?>
        <p>No modules assigned to your programme yet.</p>
    <?php else: ?>
        <?php 
        $currentYear = 1;
        foreach ($modules as $mod): 
            if ($mod['Year'] != $currentYear) {
                if ($currentYear != 1) echo '</div>';
                echo '<h3 style="margin: 1rem 0 0.5rem; color: var(--navy);">Year ' . (int)$mod['Year'] . '</h3>';
                echo '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem;">';
                $currentYear = $mod['Year'];
            }
        ?>
        <div class="module-card">
            <h3 style="margin-bottom: 0.5rem;"><?= htmlspecialchars($mod['ModuleName']) ?></h3>
            <p style="font-size: 0.85rem; color: var(--text-muted);"><?= htmlspecialchars($mod['Description'] ?? 'No description available.') ?></p>
            
            <?php if (!empty($mod['LecturerName'])): ?>
            <div class="lecturer-info">
                <div class="lecturer-avatar">
                    <?php if (!empty($mod['LecturerPhoto'])): ?>
                        <img src="<?= BASE_URL ?>/uploads/staff_photos/<?= htmlspecialchars($mod['LecturerPhoto']) ?>" alt="<?= htmlspecialchars($mod['LecturerName']) ?>">
                    <?php else: ?>
                        <?= strtoupper(substr($mod['LecturerName'], 0, 2)) ?>
                    <?php endif; ?>
                </div>
                <div>
                    <strong>Lecturer:</strong> 
                    <a href="<?= BASE_URL ?>/staff_profile.php?id=<?= (int)$mod['ModuleLeaderID'] ?>" 
                       style="color: var(--gold); text-decoration: none; font-weight: 500;"
                       target="_blank">
                        <?= htmlspecialchars($mod['LecturerName']) ?>
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- GRADES SECTION -->
<div class="panel">
    <h2>📊 My Grades</h2>
    <?php if (empty($grades)): ?>
        <p>No grades recorded yet.</p>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Module</th>
                    <th>Grade</th>
                    <th>Credits</th>
                    <th>Academic Year</th>
                 </thead>
            <tbody>
                <?php foreach ($grades as $g): ?>
                <tr>
                    <td><?= htmlspecialchars($g['ModuleName']) ?></td>
                    <td><strong style="color: var(--gold);"><?= htmlspecialchars($g['Grade']) ?></strong></td>
                    <td><?= (int)$g['Credits'] ?></td>
                    <td><?= (int)$g['AcademicYear'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php 
        // Calculate GPA
        $gradePoints = ['A+' => 4.3, 'A' => 4.0, 'A-' => 3.7, 'B+' => 3.3, 'B' => 3.0, 'B-' => 2.7, 'C+' => 2.3, 'C' => 2.0, 'C-' => 1.7, 'D' => 1.0, 'F' => 0.0];
        $totalPoints = 0;
        $totalCredits = 0;
        foreach ($grades as $g) {
            $points = $gradePoints[$g['Grade']] ?? 0;
            $totalPoints += $points * $g['Credits'];
            $totalCredits += $g['Credits'];
        }
        $gpa = $totalCredits > 0 ? round($totalPoints / $totalCredits, 2) : 0;
        ?>
        <div style="margin-top: 1rem; padding: 0.75rem; background: var(--cream); border-radius: var(--radius);">
            <strong>Current GPA:</strong> <?= number_format($gpa, 2) ?> / 4.0
            <span style="margin-left: 1rem;">Total Credits: <?= (int)$totalCredits ?></span>
        </div>
    <?php endif; ?>
</div>

<!-- ATTENDANCE SECTION -->
<div class="panel">
    <h2>✅ Recent Attendance (Last 30 Days)</h2>
    <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem;">
        <div style="text-align:center;padding:1rem;background:#e8f5e9;border-radius:8px;flex:1">
            <div style="font-size:2rem;font-weight:bold;color:#2e7d32;"><?= (int)($attendanceSummary['present'] ?? 0) ?></div>
            <div>Present</div>
        </div>
        <div style="text-align:center;padding:1rem;background:#fff3e0;border-radius:8px;flex:1">
            <div style="font-size:2rem;font-weight:bold;color:#f57c00;"><?= (int)($attendanceSummary['late'] ?? 0) ?></div>
            <div>Late</div>
        </div>
        <div style="text-align:center;padding:1rem;background:#ffebee;border-radius:8px;flex:1">
            <div style="font-size:2rem;font-weight:bold;color:#c62828;"><?= (int)($attendanceSummary['absent'] ?? 0) ?></div>
            <div>Absent</div>
        </div>
        <div style="text-align:center;padding:1rem;background:#e3f2fd;border-radius:8px;flex:1">
            <div style="font-size:2rem;font-weight:bold;color:#1976d2;"><?= (int)($attendanceSummary['excused'] ?? 0) ?></div>
            <div>Excused</div>
        </div>
    </div>
    
    <h3 style="margin: 1rem 0 0.5rem;">Attendance History</h3>
    <?php if (empty($attendance)): ?>
        <p>No attendance records found.</p>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Module</th>
                    <th>Status</th>
                 </thead>
            <tbody>
                <?php foreach ($attendance as $a): ?>
                 <tr>
                    <td><?= htmlspecialchars(date('d M Y', strtotime($a['AttendanceDate']))) ?></td>
                    <td><?= htmlspecialchars($a['ModuleName']) ?></td>
                    <td>
                        <span class="attendance-badge attendance-<?= $a['Status'] ?>">
                            <?= ucfirst($a['Status']) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php studentFooter(); ?>