<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireStudent();

$csrf_token = generateCsrfToken();
setSecurityHeaders();

$student = getLoggedInStudent();
$studentId = $student['AccountID'];

// Get full student data including photo
$stmt = $conn->prepare("SELECT * FROM StudentAccounts WHERE AccountID = ?");
$stmt->bind_param('i', $studentId);
$stmt->execute();
$studentData = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Log access to grades page
logSecurityEvent("Student accessed grades page", "Student ID: $studentId, Name: " . ($studentData['FullName'] ?? 'unknown'));

// Get all grades
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

require_once 'layout.php';
studentHead('My Grades');
studentSidebar('grades', $studentData);
studentTopbar('My Grades', $studentData);
?>

<div class="panel">
    <h2>📊 Academic Summary</h2>
    <div style="display: flex; gap: 2rem; flex-wrap: wrap; margin-bottom: 1rem;">
        <div><strong>Total Credits Earned:</strong> <?= (int)$totalCredits ?></div>
        <div><strong>Current GPA:</strong> <?= number_format($gpa, 2) ?> / 4.0</div>
        <div><strong>Modules Completed:</strong> <?= count($grades) ?></div>
    </div>
</div>

<div class="panel">
    <h2>📚 All Grades</h2>
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
                    <th>Semester</th>
                  </thead>
            <tbody>
                <?php foreach ($grades as $g): ?>
                  <tr>
                    <td><?= htmlspecialchars($g['ModuleName']) ?></td>
                    <td><strong style="color: var(--gold);"><?= htmlspecialchars($g['Grade']) ?></strong></td>
                    <td><?= (int)$g['Credits'] ?></td>
                    <td><?= (int)$g['AcademicYear'] ?></td>
                    <td><?= htmlspecialchars($g['Semester'] ?? 'full') ?></td>
                  </tr>
                <?php endforeach; ?>
            </tbody>
          </table>
    <?php endif; ?>
</div>

<?php studentFooter(); ?>