<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

$csrf_token = generateCsrfToken();
setSecurityHeaders();

$msg = '';
$msgType = 'success';
$action = $_GET['action'] ?? 'list';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkPostCsrf();
    
    $pa = $_POST['action'] ?? '';
    
    // ==================== ENROLL STUDENT ====================
    if ($pa === 'enroll') {
        $studentID = (int)$_POST['student_id'];
        $programmeID = (int)$_POST['programme_id'];
        $enrollmentDate = $_POST['enrollment_date'] ?? date('Y-m-d');
        $startYear = (int)$_POST['start_year'] ?? date('Y');
        $expectedYear = (int)$_POST['expected_year'] ?? (date('Y') + 3);
        
        // Check if student exists
        $checkStudent = $conn->prepare("SELECT AccountID, FullName FROM StudentAccounts WHERE AccountID = ?");
        $checkStudent->bind_param('i', $studentID);
        $checkStudent->execute();
        $student = $checkStudent->get_result()->fetch_assoc();
        
        if (!$student) {
            $msg = 'Student not found.';
            $msgType = 'error';
        } else {
            // Check if already enrolled in this programme
            $check = $conn->prepare("SELECT EnrollmentID FROM StudentEnrollment WHERE StudentID = ? AND ProgrammeID = ? AND EnrollmentStatus = 'active'");
            $check->bind_param('ii', $studentID, $programmeID);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $msg = 'Student is already enrolled in this programme.';
                $msgType = 'error';
            } else {
                $stmt = $conn->prepare("INSERT INTO StudentEnrollment (StudentID, ProgrammeID, EnrollmentDate, StartYear, ExpectedGraduationYear, EnrollmentStatus) VALUES (?, ?, ?, ?, ?, 'active')");
                $stmt->bind_param('iisii', $studentID, $programmeID, $enrollmentDate, $startYear, $expectedYear);
                if ($stmt->execute()) {
                    // Update StudentAccounts status
                    $update = $conn->prepare("UPDATE StudentAccounts SET EnrollmentStatus = 'enrolled', EnrollmentDate = ? WHERE AccountID = ?");
                    $update->bind_param('si', $enrollmentDate, $studentID);
                    $update->execute();
                    
                    $msg = "Student '{$student['FullName']}' enrolled successfully!";
                    $msgType = 'success';
                    logSecurityEvent("Student enrolled", "Student ID: $studentID, Programme ID: $programmeID, Admin: " . ($_SESSION['admin_user'] ?? 'unknown'));
                    $action = 'list';
                } else {
                    $msg = 'Error: ' . $conn->error;
                    $msgType = 'error';
                }
                $stmt->close();
            }
            $check->close();
        }
        $checkStudent->close();
    }
    
    // ==================== ENTER GRADE ====================
    elseif ($pa === 'update_grade') {
        $studentID = (int)$_POST['student_id'];
        $moduleID = (int)$_POST['module_id'];
        $programmeID = (int)($_POST['programme_id'] ?? 0);
        $grade = $_POST['grade'];
        $credits = (int)$_POST['credits'];
        $academicYear = (int)$_POST['academic_year'];
        $semester = $_POST['semester'] ?? 'full';
        
        // Check if student is enrolled
        $checkEnroll = $conn->prepare("SELECT EnrollmentID FROM StudentEnrollment WHERE StudentID = ? AND EnrollmentStatus = 'active'");
        $checkEnroll->bind_param('i', $studentID);
        $checkEnroll->execute();
        if ($checkEnroll->get_result()->num_rows == 0) {
            $msg = 'Student is not enrolled in any programme.';
            $msgType = 'error';
        } else {
            $stmt = $conn->prepare("INSERT INTO StudentGrades (StudentID, ModuleID, ProgrammeID, Grade, Credits, AcademicYear, Semester, GradingDate) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param('iiisiss', $studentID, $moduleID, $programmeID, $grade, $credits, $academicYear, $semester);
            if ($stmt->execute()) {
                $msg = 'Grade added successfully!';
                $msgType = 'success';
                logSecurityEvent("Grade added", "Student ID: $studentID, Module ID: $moduleID, Grade: $grade, Admin: " . ($_SESSION['admin_user'] ?? 'unknown'));
            } else {
                $msg = 'Error: ' . $conn->error;
                $msgType = 'error';
            }
            $stmt->close();
        }
        $checkEnroll->close();
    }
    
    // ==================== MARK ATTENDANCE ====================
    elseif ($pa === 'mark_attendance') {
        $studentID = (int)$_POST['student_id'];
        $moduleID = (int)$_POST['module_id'];
        $status = $_POST['status'];
        $attendanceDate = $_POST['attendance_date'] ?? date('Y-m-d');
        
        // Check if student is enrolled
        $checkEnroll = $conn->prepare("SELECT EnrollmentID FROM StudentEnrollment WHERE StudentID = ? AND EnrollmentStatus = 'active'");
        $checkEnroll->bind_param('i', $studentID);
        $checkEnroll->execute();
        if ($checkEnroll->get_result()->num_rows == 0) {
            $msg = 'Student is not enrolled in any programme.';
            $msgType = 'error';
        } else {
            // Check if already marked for this date
            $checkDuplicate = $conn->prepare("SELECT AttendanceID FROM StudentAttendance WHERE StudentID = ? AND ModuleID = ? AND AttendanceDate = ?");
            $checkDuplicate->bind_param('iis', $studentID, $moduleID, $attendanceDate);
            $checkDuplicate->execute();
            if ($checkDuplicate->get_result()->num_rows > 0) {
                $msg = 'Attendance already marked for this student on this date.';
                $msgType = 'warning';
            } else {
                $stmt = $conn->prepare("INSERT INTO StudentAttendance (StudentID, ModuleID, AttendanceDate, Status) VALUES (?, ?, ?, ?)");
                $stmt->bind_param('iiss', $studentID, $moduleID, $attendanceDate, $status);
                if ($stmt->execute()) {
                    $msg = 'Attendance marked successfully!';
                    $msgType = 'success';
                    logSecurityEvent("Attendance marked", "Student ID: $studentID, Module ID: $moduleID, Status: $status");
                } else {
                    $msg = 'Error: ' . $conn->error;
                    $msgType = 'error';
                }
                $stmt->close();
            }
            $checkDuplicate->close();
        }
        $checkEnroll->close();
    }
}

// ==================== FETCH DATA ====================

// Get all students
$students = [];
$stmt = $conn->prepare("SELECT AccountID, FullName, Email FROM StudentAccounts ORDER BY FullName");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}
$stmt->close();

// Get all programmes
$programmes = [];
$stmt = $conn->prepare("SELECT ProgrammeID, ProgrammeName, LevelName FROM Programmes p JOIN Levels l ON p.LevelID = l.LevelID ORDER BY ProgrammeName");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $programmes[] = $row;
}
$stmt->close();

// Get modules for grade entry
$modules = [];
$stmt = $conn->prepare("SELECT ModuleID, ModuleName FROM Modules ORDER BY ModuleName");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $modules[] = $row;
}
$stmt->close();

// Get enrolled students with details
$enrolledStudents = [];
$stmt = $conn->prepare("
    SELECT e.*, sa.FullName, sa.Email, p.ProgrammeName, l.LevelName
    FROM StudentEnrollment e
    JOIN StudentAccounts sa ON e.StudentID = sa.AccountID
    JOIN Programmes p ON e.ProgrammeID = p.ProgrammeID
    JOIN Levels l ON p.LevelID = l.LevelID
    ORDER BY e.EnrollmentDate DESC
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $enrolledStudents[] = $row;
}
$stmt->close();

// Get recent grades
$recentGrades = [];
$stmt = $conn->prepare("
    SELECT g.*, sa.FullName, m.ModuleName
    FROM StudentGrades g
    JOIN StudentAccounts sa ON g.StudentID = sa.AccountID
    JOIN Modules m ON g.ModuleID = m.ModuleID
    ORDER BY g.GradingDate DESC
    LIMIT 10
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recentGrades[] = $row;
}
$stmt->close();

require_once 'layout.php';
adminHead('Student Enrollment');
adminSidebar('student_enrollment');
adminTopbar('Student Enrollment');
?>

<?php if ($msg): ?>
    <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<!-- TABS -->
<div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; border-bottom: 2px solid var(--border); flex-wrap: wrap;">
    <a href="?action=list" style="padding: 0.6rem 1.2rem; text-decoration: none; <?= $action === 'list' ? 'background: var(--navy); color: #fff; border-radius: var(--radius) var(--radius) 0 0;' : 'color: var(--navy);' ?>">
        📋 Enrolled Students (<?= count($enrolledStudents) ?>)
    </a>
    <a href="?action=enroll" style="padding: 0.6rem 1.2rem; text-decoration: none; <?= $action === 'enroll' ? 'background: var(--navy); color: #fff; border-radius: var(--radius) var(--radius) 0 0;' : 'color: var(--navy);' ?>">
        ➕ Enroll Student
    </a>
    <a href="?action=grades" style="padding: 0.6rem 1.2rem; text-decoration: none; <?= $action === 'grades' ? 'background: var(--navy); color: #fff; border-radius: var(--radius) var(--radius) 0 0;' : 'color: var(--navy);' ?>">
        📝 Enter Grades
    </a>
    <a href="?action=attendance" style="padding: 0.6rem 1.2rem; text-decoration: none; <?= $action === 'attendance' ? 'background: var(--navy); color: #fff; border-radius: var(--radius) var(--radius) 0 0;' : 'color: var(--navy);' ?>">
        ✅ Mark Attendance
    </a>
    <a href="?action=recent" style="padding: 0.6rem 1.2rem; text-decoration: none; <?= $action === 'recent' ? 'background: var(--navy); color: #fff; border-radius: var(--radius) var(--radius) 0 0;' : 'color: var(--navy);' ?>">
        📊 Recent Grades
    </a>
</div>

<?php if ($action === 'list'): ?>
    <!-- ENROLLED STUDENTS LIST -->
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Email</th>
                    <th>Programme</th>
                    <th>Level</th>
                    <th>Enrollment Date</th>
                    <th>Start Year</th>
                    <th>Expected Graduation</th>
                    <th>Status</th>
                </thead>
            <tbody>
                <?php foreach ($enrolledStudents as $e): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($e['FullName']) ?></strong></td>
                    <td><?= htmlspecialchars($e['Email']) ?></td>
                    <td><?= htmlspecialchars($e['ProgrammeName']) ?></td>
                    <td><?= htmlspecialchars($e['LevelName']) ?></td>
                    <td><?= htmlspecialchars(date('d M Y', strtotime($e['EnrollmentDate']))) ?></td>
                    <td><?= (int)$e['StartYear'] ?></td>
                    <td><?= (int)$e['ExpectedGraduationYear'] ?></td>
                    <td><span class="badge badge-approved"><?= ucfirst($e['EnrollmentStatus']) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php elseif ($action === 'enroll'): ?>
    <!-- ENROLL NEW STUDENT FORM -->
    <div class="admin-form-wrap">
        <h2>➕ Enroll Student in Programme</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="action" value="enroll">
            
            <div class="form-group">
                <label>Select Student *</label>
                <select name="student_id" required>
                    <option value="">-- Select Student --</option>
                    <?php foreach ($students as $s): ?>
                        <option value="<?= (int)$s['AccountID'] ?>"><?= htmlspecialchars($s['FullName']) ?> (<?= htmlspecialchars($s['Email']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Select Programme *</label>
                <select name="programme_id" required>
                    <option value="">-- Select Programme --</option>
                    <?php foreach ($programmes as $p): ?>
                        <option value="<?= (int)$p['ProgrammeID'] ?>"><?= htmlspecialchars($p['ProgrammeName']) ?> (<?= htmlspecialchars($p['LevelName']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Enrollment Date</label>
                    <input type="date" name="enrollment_date" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label>Start Year</label>
                    <input type="number" name="start_year" value="<?= date('Y') ?>" min="2000" max="2030">
                </div>
                <div class="form-group">
                    <label>Expected Graduation Year</label>
                    <input type="number" name="expected_year" value="<?= date('Y') + 3 ?>" min="2000" max="2035">
                </div>
            </div>
            
            <button type="submit" class="btn btn-add">Enroll Student →</button>
        </form>
    </div>

<?php elseif ($action === 'grades'): ?>
    <!-- ENTER GRADES FORM -->
    <div class="admin-form-wrap">
        <h2>📝 Enter Student Grades</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="action" value="update_grade">
            
            <div class="form-group">
                <label>Student *</label>
                <select name="student_id" required>
                    <option value="">-- Select Student --</option>
                    <?php foreach ($students as $s): ?>
                        <option value="<?= (int)$s['AccountID'] ?>"><?= htmlspecialchars($s['FullName']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Module *</label>
                <select name="module_id" required>
                    <option value="">-- Select Module --</option>
                    <?php foreach ($modules as $m): ?>
                        <option value="<?= (int)$m['ModuleID'] ?>"><?= htmlspecialchars($m['ModuleName']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Programme (Optional)</label>
                <select name="programme_id">
                    <option value="0">-- Not Specified --</option>
                    <?php foreach ($programmes as $p): ?>
                        <option value="<?= (int)$p['ProgrammeID'] ?>"><?= htmlspecialchars($p['ProgrammeName']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Grade *</label>
                    <select name="grade" required>
                        <option value="">-- Select Grade --</option>
                        <option value="A+">A+</option><option value="A">A</option><option value="A-">A-</option>
                        <option value="B+">B+</option><option value="B">B</option><option value="B-">B-</option>
                        <option value="C+">C+</option><option value="C">C</option><option value="C-">C-</option>
                        <option value="D">D</option><option value="F">F</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Credits</label>
                    <input type="number" name="credits" value="20" min="0" max="60">
                </div>
                <div class="form-group">
                    <label>Academic Year</label>
                    <input type="number" name="academic_year" value="<?= date('Y') ?>" min="2000" max="2030">
                </div>
            </div>
            
            <div class="form-group">
                <label>Semester</label>
                <select name="semester">
                    <option value="full">Full Year</option>
                    <option value="1">Semester 1</option>
                    <option value="2">Semester 2</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-add">Save Grade →</button>
        </form>
    </div>

<?php elseif ($action === 'attendance'): ?>
    <!-- MARK ATTENDANCE FORM -->
    <div class="admin-form-wrap">
        <h2>✅ Mark Student Attendance</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="action" value="mark_attendance">
            
            <div class="form-group">
                <label>Student *</label>
                <select name="student_id" required>
                    <option value="">-- Select Student --</option>
                    <?php foreach ($students as $s): ?>
                        <option value="<?= (int)$s['AccountID'] ?>"><?= htmlspecialchars($s['FullName']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Module *</label>
                <select name="module_id" required>
                    <option value="">-- Select Module --</option>
                    <?php foreach ($modules as $m): ?>
                        <option value="<?= (int)$m['ModuleID'] ?>"><?= htmlspecialchars($m['ModuleName']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Attendance Date</label>
                    <input type="date" name="attendance_date" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label>Status *</label>
                    <select name="status" required>
                        <option value="">-- Select Status --</option>
                        <option value="present">✅ Present</option>
                        <option value="absent">❌ Absent</option>
                        <option value="late">⏰ Late</option>
                        <option value="excused">📝 Excused</option>
                    </select>
                </div>
            </div>
            
            <button type="submit" class="btn btn-add">Mark Attendance →</button>
        </form>
    </div>

<?php elseif ($action === 'recent'): ?>
    <!-- RECENT GRADES -->
    <div class="admin-table-wrap">
        <h2 style="padding: 1rem 1.2rem; margin: 0; background: var(--cream);">📊 Recently Entered Grades</h2>
        <?php if (empty($recentGrades)): ?>
            <div style="padding: 2rem; text-align: center; color: var(--text-muted);">No grades recorded yet.</div>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Module</th>
                        <th>Grade</th>
                        <th>Credits</th>
                        <th>Year</th>
                        <th>Semester</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentGrades as $g): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($g['FullName']) ?></strong></td>
                        <td><?= htmlspecialchars($g['ModuleName']) ?></td>
                        <td><span style="font-weight: bold; color: var(--gold);"><?= htmlspecialchars($g['Grade']) ?></span></td>
                        <td><?= (int)$g['Credits'] ?></td>
                        <td><?= (int)$g['AcademicYear'] ?></td>
                        <td><?= ucfirst(htmlspecialchars($g['Semester'] ?? 'full')) ?></td>
                        <td><?= htmlspecialchars(date('d M Y', strtotime($g['GradingDate']))) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php adminFooter(); ?>