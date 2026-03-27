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

// Log access
logSecurityEvent("Student accessed attendance page", "Student ID: $studentId, Name: " . ($studentData['FullName'] ?? 'unknown'));

// Get attendance records
$attendance = [];
$stmt = $conn->prepare("
    SELECT a.*, m.ModuleName
    FROM StudentAttendance a
    JOIN Modules m ON a.ModuleID = m.ModuleID
    WHERE a.StudentID = ?
    ORDER BY a.AttendanceDate DESC
    LIMIT 50
");
$stmt->bind_param('i', $studentId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $attendance[] = $row;
}
$stmt->close();

// Get summary by module
$summary = [];
$stmt = $conn->prepare("
    SELECT m.ModuleName, 
           SUM(CASE WHEN a.Status = 'present' THEN 1 ELSE 0 END) as present,
           SUM(CASE WHEN a.Status = 'absent' THEN 1 ELSE 0 END) as absent,
           SUM(CASE WHEN a.Status = 'late' THEN 1 ELSE 0 END) as late,
           COUNT(*) as total
    FROM StudentAttendance a
    JOIN Modules m ON a.ModuleID = m.ModuleID
    WHERE a.StudentID = ?
    GROUP BY m.ModuleID
");
$stmt->bind_param('i', $studentId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $summary[] = $row;
}
$stmt->close();

require_once 'layout.php';
studentHead('Attendance');
studentSidebar('attendance', $studentData);
studentTopbar('Attendance', $studentData);
?>

<div class="panel">
    <h2>✅ Attendance Summary by Module</h2>
    <?php if (empty($summary)): ?>
        <p>No attendance records found.</p>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                 <tr>
                    <th>Module</th>
                    <th>Present</th>
                    <th>Late</th>
                    <th>Absent</th>
                    <th>Total</th>
                    <th>Rate</th>
                  </thead>
            <tbody>
                <?php foreach ($summary as $s): 
                    $rate = $s['total'] > 0 ? round(($s['present'] / $s['total']) * 100, 1) : 0;
                ?>
                  <tr>
                    <td><?= htmlspecialchars($s['ModuleName']) ?></td>
                    <td style="color:green;"><?= (int)$s['present'] ?></td>
                    <td style="color:orange;"><?= (int)$s['late'] ?></td>
                    <td style="color:red;"><?= (int)$s['absent'] ?></td>
                    <td><?= (int)$s['total'] ?></td>
                    <td><strong><?= $rate ?>%</strong></td>
                  </tr>
                <?php endforeach; ?>
            </tbody>
          </table>
    <?php endif; ?>
</div>

<div class="panel">
    <h2>📅 Recent Attendance Records</h2>
    <?php if (empty($attendance)): ?>
        <p>No attendance records found.</p>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                  <tr>
                    <th>Date</th>
                    <th>Module</th>
                    <th>Status</th>
                  </tr>
            </thead>
            <tbody>
                <?php foreach ($attendance as $a): ?>
                  <tr>
                    <td><?= htmlspecialchars(date('d M Y', strtotime($a['AttendanceDate']))) ?></td>
                    <td><?= htmlspecialchars($a['ModuleName']) ?></td>
                    <td>
                        <?php
                        $statusClass = ['present' => 'green', 'late' => 'orange', 'absent' => 'red', 'excused' => 'blue'];
                        echo '<span style="color:' . ($statusClass[$a['Status']] ?? 'gray') . '; font-weight:500;">' . ucfirst($a['Status']) . '</span>';
                        ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
            </tbody>
          </table>
    <?php endif; ?>
</div>

<?php studentFooter(); ?>