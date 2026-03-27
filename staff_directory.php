<?php
require_once 'includes/db.php';
require_once 'includes/auth.php'; // For security headers and functions

// Set security headers
setSecurityHeaders();

$page_title = 'Our Staff';
$upload_url = BASE_URL . '/uploads/staff_photos/';

// ==================== SECURE QUERY ====================
// Fetch staff with their module and programme counts using prepared statements
$staff = [];
$stmt = $conn->prepare("
    SELECT s.*
    FROM Staff s
    ORDER BY s.Name
");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    // Get module names for this staff member
    $modStmt = $conn->prepare("
        SELECT GROUP_CONCAT(ModuleName ORDER BY ModuleName SEPARATOR '||') AS ModuleNames,
               COUNT(*) AS ModCount
        FROM Modules 
        WHERE ModuleLeaderID = ?
    ");
    $modStmt->bind_param('i', $row['StaffID']);
    $modStmt->execute();
    $modResult = $modStmt->get_result();
    $modData = $modResult->fetch_assoc();
    $modStmt->close();
    
    // Get programme count for this staff member
    $progStmt = $conn->prepare("
        SELECT COUNT(*) AS ProgCount
        FROM Programmes 
        WHERE ProgrammeLeaderID = ?
    ");
    $progStmt->bind_param('i', $row['StaffID']);
    $progStmt->execute();
    $progResult = $progStmt->get_result();
    $progData = $progResult->fetch_assoc();
    $progStmt->close();
    
    $row['ModuleNames'] = $modData['ModuleNames'] ?? '';
    $row['ModCount'] = (int)($modData['ModCount'] ?? 0);
    $row['ProgCount'] = (int)($progData['ProgCount'] ?? 0);
    
    $staff[] = $row;
}
$stmt->close();

// Helper function to get initials
function getInitials(string $name): string {
    $init = '';
    $words = explode(' ', $name);
    foreach ($words as $word) {
        if (!empty($word) && ctype_alpha($word[0])) {
            $init .= $word[0];
        }
    }
    return strtoupper(substr($init, 0, 2));
}

include 'includes/header.php';
?>

<style>
.staff-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-top: 1rem;
}

.staff-card {
    display: flex;
    background: #fff;
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: 0 2px 12px var(--shadow);
    transition: transform 0.2s, box-shadow 0.2s;
    text-decoration: none;
    color: inherit;
    border: 1px solid var(--border);
}

.staff-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}

.staff-card-photo {
    width: 100px;
    height: 100px;
    flex-shrink: 0;
    background: var(--navy);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.staff-card-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.staff-card-photo-initials {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--gold);
    color: var(--navy);
    font-family: 'Playfair Display', serif;
    font-size: 2rem;
    font-weight: 700;
}

.staff-card-body {
    flex: 1;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.staff-card-name {
    font-weight: 700;
    font-size: 1rem;
    color: var(--navy);
}

.staff-card-role {
    font-size: 0.75rem;
    color: var(--gold);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.staff-card-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    margin-top: 0.5rem;
}

.staff-card-tag {
    font-size: 0.7rem;
    padding: 0.2rem 0.6rem;
    background: var(--cream);
    border: 1px solid var(--border);
    border-radius: 99px;
    color: var(--navy);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 120px;
}

#staff-search {
    width: 100%;
    padding: 0.6rem 1rem;
    border: 1.5px solid var(--border);
    border-radius: var(--radius);
    font-size: 0.9rem;
    font-family: inherit;
    background: var(--cream);
    outline: none;
    transition: border-color 0.2s;
}

#staff-search:focus {
    border-color: var(--gold);
    background: #fff;
}
</style>

<div class="page-hero">
    <div class="page-hero-inner">
        <div class="breadcrumb">
            <a href="<?= htmlspecialchars(BASE_URL) ?>/index.php">Home</a> / Our Staff
        </div>
        <h1>Our Academic Staff</h1>
        <p>Meet the faculty leading our programmes and modules at Crestfield University.</p>
    </div>
</div>

<section class="section">
    <div class="section-inner">

        <!-- Search bar -->
        <div style="max-width:400px;margin-bottom:2rem;">
            <label for="staff-search" style="display:block;font-size:.82rem;font-weight:600;color:var(--navy);margin-bottom:.4rem;">
                Search Staff
            </label>
            <input type="search" id="staff-search" placeholder="e.g. Dr. Alice Johnson…"
                   oninput="filterStaff(this.value)"
                   aria-label="Search staff by name">
        </div>

        <div class="staff-cards-grid" id="staff-grid">
            <?php foreach ($staff as $s):
                $hasPhoto = !empty($s['Photo']);
                $photoSrc = $upload_url . rawurlencode($s['Photo'] ?? '');
                $initials = getInitials($s['Name']);
                $modules = !empty($s['ModuleNames']) ? explode('||', $s['ModuleNames']) : [];
            ?>
            <a class="staff-card" href="<?= htmlspecialchars(BASE_URL) ?>/staff_portal.php?staff_id=<?= (int)$s['StaffID'] ?>" 
               data-name="<?= htmlspecialchars(strtolower($s['Name'])) ?>">

                <!-- Photo -->
                <div class="staff-card-photo">
                    <?php if ($hasPhoto): ?>
                        <img src="<?= htmlspecialchars($photoSrc) ?>"
                             alt="Photo of <?= htmlspecialchars($s['Name']) ?>"
                             loading="lazy"
                             onerror="this.parentNode.innerHTML='<div class=\'staff-card-photo-initials\'><?= htmlspecialchars($initials) ?></div>'">
                    <?php else: ?>
                        <div class="staff-card-photo-initials" aria-hidden="true">
                            <?= htmlspecialchars($initials) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Info -->
                <div class="staff-card-body">
                    <div class="staff-card-name"><?= htmlspecialchars($s['Name']) ?></div>
                    <div class="staff-card-role">
                        <?php
                        $roles = [];
                        if ((int)$s['ProgCount'] > 0) $roles[] = 'Programme Leader';
                        if ((int)$s['ModCount'] > 0) $roles[] = 'Module Leader';
                        echo $roles ? htmlspecialchars(implode(' · ', $roles)) : 'Academic Staff';
                        ?>
                    </div>

                    <?php if (!empty($s['Bio'])): ?>
                    <p style="font-size:.78rem;color:var(--text-muted);margin-top:.6rem;
                               line-height:1.55;padding:0 .25rem;">
                        <?= htmlspecialchars(mb_substr($s['Bio'], 0, 90)) ?>…
                    </p>
                    <?php endif; ?>

                    <!-- Module tags (first 3) -->
                    <?php if (!empty($modules)): ?>
                    <div class="staff-card-tags">
                        <?php 
                        $displayModules = array_slice($modules, 0, 3);
                        foreach ($displayModules as $mname): 
                        ?>
                        <span class="staff-card-tag" title="<?= htmlspecialchars($mname) ?>">
                            <?= htmlspecialchars(mb_substr($mname, 0, 20)) ?>
                        </span>
                        <?php endforeach; ?>
                        <?php if (count($modules) > 3): ?>
                        <span class="staff-card-tag" style="background:var(--navy);color:#fff;border-color:var(--navy);">
                            +<?= count($modules) - 3 ?> more
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

            </a>
            <?php endforeach; ?>
        </div>

        <!-- No results message -->
        <div id="no-staff" style="display:none;text-align:center;padding:3rem;color:var(--text-muted);">
            <div style="font-size:3rem;margin-bottom:1rem;">🔍</div>
            <h3 style="font-family:'Playfair Display',serif;color:var(--navy);">No staff found</h3>
            <p>Try a different name.</p>
        </div>

    </div>
</section>

<script>
function filterStaff(query) {
    const q = query.toLowerCase().trim();
    const cards = document.querySelectorAll('.staff-card');
    let shown = 0;
    cards.forEach(card => {
        const match = !q || (card.dataset.name && card.dataset.name.includes(q));
        card.style.display = match ? '' : 'none';
        if (match) shown++;
    });
    const noStaffEl = document.getElementById('no-staff');
    if (noStaffEl) {
        noStaffEl.style.display = shown === 0 ? 'block' : 'none';
    }
}
</script>

<?php include 'includes/footer.php'; ?>