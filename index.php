<?php
require_once 'includes/db.php';
require_once 'includes/auth.php'; // For security headers and functions

// Set security headers for the public page
setSecurityHeaders();

$page_title = 'Welcome';

// ==================== SECURE QUERIES ====================

// Fetch stats using prepared statements
$total_programmes = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as c FROM Programmes");
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $total_programmes = (int)$row['c'];
}
$stmt->close();

$total_modules = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as c FROM Modules");
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $total_modules = (int)$row['c'];
}
$stmt->close();

$total_staff = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as c FROM Staff");
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $total_staff = (int)$row['c'];
}
$stmt->close();

include 'includes/header.php';
?>

<!-- HERO -->
<section class="hero">
    <div class="hero-inner">
        <div class="hero-badge">🎓 Est. 1999 · London, UK</div>
        <h1>Where Brilliant Minds <em>Come to Life</em></h1>
        <p>Explore world-class undergraduate and postgraduate programmes in Computer Science, AI, Cybersecurity, and Data Science.</p>
        <div class="hero-actions">
            <a href="<?= htmlspecialchars(BASE_URL) ?>/programmes.php" class="btn btn-gold">Explore Programmes →</a>
        </div>
    </div>
</section>

<!-- STATS -->
<div class="stats-bar">
    <div class="stats-inner">
        <div class="stat">
            <div class="stat-num"><?= htmlspecialchars((string)$total_programmes) ?><span>+</span></div>
            <div class="stat-label">Programmes</div>
        </div>
        <div class="stat">
            <div class="stat-num"><?= htmlspecialchars((string)$total_modules) ?><span>+</span></div>
            <div class="stat-label">Modules</div>
        </div>
        <div class="stat">
            <div class="stat-num"><?= htmlspecialchars((string)$total_staff) ?><span>+</span></div>
            <div class="stat-label">Academic Staff</div>
        </div>
        <div class="stat">
            <div class="stat-num">1999</div>
            <div class="stat-label">Est. Year</div>
        </div>
    </div>
</div>

<!-- WHY CRESTFIELD -->
<section class="section section-alt">
    <div class="section-inner">
        <div class="section-header">
            <div class="eyebrow">Why Us</div>
            <h2>The Crestfield Difference</h2>
        </div>
        <div class="cards-grid" style="grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));">
            <?php
            $highlights = [
                ['🏛️', 'Academic Excellence', 'Consistently ranked among the UK\'s top technology-focused universities.'],
                ['🔬', 'Research-Led Teaching', 'Learn from active researchers shaping the future of computing and AI.'],
                ['🌍', 'Industry Connections', 'Strong ties with leading tech firms ensure you graduate career-ready.'],
                ['🤝', 'Inclusive Community', 'A diverse, supportive environment where every student can thrive.'],
            ];
            foreach ($highlights as [$icon, $title, $desc]):
            ?>
            <div class="card">
                <div class="card-body" style="text-align:center;align-items:center;">
                    <div style="font-size:2.2rem;margin-bottom:.75rem;"><?= htmlspecialchars($icon) ?></div>
                    <h3 style="margin-bottom:.5rem;"><?= htmlspecialchars($title) ?></h3>
                    <p style="font-size:.85rem;"><?= htmlspecialchars($desc) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ABOUT US -->
<section class="section" id="about">
    <div class="section-inner">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:4rem;align-items:center;">

            <!-- Text -->
            <div>
                <div class="eyebrow" style="font-size:.75rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--gold);margin-bottom:.75rem;">About Us</div>
                <h2 style="font-family:'Playfair Display',serif;font-size:2rem;color:var(--navy);margin-bottom:1.2rem;line-height:1.25;">A Legacy of Excellence Since 1999</h2>
                <p style="color:var(--text-muted);line-height:1.8;margin-bottom:1rem;">
                    Crestfield University was founded in 1999 in the heart of London with a singular mission: to cultivate the brightest minds and equip them with the knowledge, skills, and values to shape the world. Over 26 years, we have grown from a small institute of science into one of the UK's leading technology-focused universities.
                </p>
                <p style="color:var(--text-muted);line-height:1.8;margin-bottom:1rem;">
                    Today, Crestfield is home to over 12,000 students from more than 80 countries, a world-class faculty of researchers and practitioners, and state-of-the-art facilities dedicated to Computer Science, Artificial Intelligence, Cybersecurity, and Data Science.
                </p>
                <p style="color:var(--text-muted);line-height:1.8;margin-bottom:1.75rem;">
                    We believe that education is not just about acquiring knowledge — it is about learning to think, to create, and to lead. Our graduates go on to work at the world's most innovative companies, lead ground-breaking research, and build the technologies that define the future.
                </p>
                <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
                    <a href="<?= htmlspecialchars(BASE_URL) ?>/programmes.php" class="btn btn-navy">Our Programmes →</a>
                    <a href="<?= htmlspecialchars(BASE_URL) ?>/staff_directory.php" class="btn btn-outline" style="background:transparent;border:2px solid var(--navy);color:var(--navy);">Meet Our Staff</a>
                </div>
            </div>

            <!-- Fact boxes -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <?php
                $facts = [
                    'uploads/founded.jpg',
                    'uploads/students.jpg',
                    'uploads/global.jpg',
                    'uploads/ranking.jpg',
                    'uploads/careers.jpg',
                    'uploads/research.jpg',
                ];

                foreach ($facts as $img):
                ?>
                <div style="border-radius:var(--radius-lg);overflow:hidden;box-shadow:0 10px 25px rgba(0,0,0,0.15);transition:transform .3s ease;">
                    <img src="<?= htmlspecialchars($img) ?>" alt="" style="width:100%;height:200px;object-fit:cover;" loading="lazy">
                </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>
</section>

<!-- FAQ -->
<section class="section section-alt" id="faq">
    <div class="section-inner">
        <div class="section-header" style="text-align:center;">
            <div class="eyebrow">FAQ</div>
            <h2>Frequently Asked Questions</h2>
            <p style="max-width:580px;margin:0 auto;">Everything you need to know about studying at Crestfield University. Can't find an answer? Contact our admissions team.</p>
        </div>

        <?php
        $faqs = [
            [
                'What programmes does Crestfield University offer?',
                'We offer a wide range of undergraduate (BSc) and postgraduate (MSc) programmes in Computer Science, Software Engineering, Artificial Intelligence, Cyber Security, Data Science, and more. Visit our Programmes page to explore all available degrees and their full module listings.'
            ],
            [
                'How do I apply to Crestfield University?',
                'Undergraduate applications are submitted through UCAS. Postgraduate applicants apply directly through our online admissions portal. Our admissions team is available year-round to guide you through the process and answer any questions about entry requirements or supporting documents.'
            ],
            [
                'What are the entry requirements?',
                'Entry requirements vary by programme and level. Undergraduate programmes typically require A-levels or equivalent qualifications. Postgraduate programmes require a relevant undergraduate degree, usually at 2:1 or above. International students must also demonstrate English language proficiency via IELTS or equivalent.'
            ],
            [
                'Is financial support or scholarship available?',
                'Yes. Crestfield University offers a range of scholarships, bursaries, and funding options for both home and international students. These include academic merit scholarships, diversity bursaries, and postgraduate research funding. Visit the Student Finance section of our website for full details.'
            ],
            [
                'What is campus life like at Crestfield?',
                'Our London campus is vibrant and diverse, with over 80 student societies, a modern sports centre, dedicated study spaces, and a range of on-campus accommodation options. We pride ourselves on creating an inclusive community where every student feels at home.'
            ],
            [
                'Can I visit the campus before applying?',
                'Absolutely. We host regular Open Days throughout the year where you can tour the campus, meet current students and staff, attend taster lectures, and get a real feel for life at Crestfield. Check our events page to book your place at the next Open Day.'
            ],
            [
                'What career support is available to students?',
                'Our dedicated Careers & Employability Service provides one-to-one guidance, CV workshops, interview preparation, and a jobs board featuring roles from our industry partners. With a 95% graduate employment rate, we are committed to helping you launch your career from day one.'
            ],
            [
                'How do staff members manage their profiles on this system?',
                'Academic staff have access to a dedicated Staff Portal where they can view their assigned modules and programmes, update their profile and bio, upload a profile photo, and submit change requests for admin approval. Log in at the Staff Login link in the navigation.'
            ],
        ];
        ?>

        <div style="max-width:780px;margin:0 auto;" id="faq-list">
            <?php foreach ($faqs as $i => [$q, $a]): ?>
            <div class="faq-item" style="border:1px solid var(--border);border-radius:var(--radius-lg);background:#fff;margin-bottom:.75rem;overflow:hidden;">
                <button class="faq-q"
                    onclick="toggleFaq(this)"
                    aria-expanded="false"
                    style="width:100%;text-align:left;padding:1.1rem 1.4rem;background:none;border:none;
                           font-family:'DM Sans',sans-serif;font-size:.95rem;font-weight:600;
                           color:var(--navy);cursor:pointer;display:flex;justify-content:space-between;
                           align-items:center;gap:1rem;line-height:1.4;">
                    <span><?= htmlspecialchars($q) ?></span>
                    <span class="faq-icon" style="font-size:1.2rem;color:var(--gold);flex-shrink:0;transition:transform .25s;">+</span>
                </button>
                <div class="faq-a" style="display:none;padding:0 1.4rem 1.2rem;color:var(--text-muted);font-size:.9rem;line-height:1.75;border-top:1px solid var(--border);">
                    <div style="padding-top:1rem;"><?= htmlspecialchars($a) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div style="text-align:center;margin-top:2.5rem;">
            <p style="color:var(--text-muted);font-size:.9rem;margin-bottom:1rem;">Still have questions?</p>
            <a href="mailto:admissions@crestfield.ac.uk" class="btn btn-navy">Contact Admissions →</a>
        </div>
    </div>
</section>

<script>
function toggleFaq(btn) {
    const answer   = btn.nextElementSibling;
    const icon     = btn.querySelector('.faq-icon');
    const isOpen   = btn.getAttribute('aria-expanded') === 'true';

    // Close all others
    document.querySelectorAll('.faq-q').forEach(b => {
        b.setAttribute('aria-expanded', 'false');
        const answerDiv = b.nextElementSibling;
        const iconSpan = b.querySelector('.faq-icon');
        if (answerDiv) answerDiv.style.display = 'none';
        if (iconSpan) {
            iconSpan.textContent = '+';
            iconSpan.style.transform = 'rotate(0deg)';
        }
    });

    // Open this one if it was closed
    if (!isOpen) {
        btn.setAttribute('aria-expanded', 'true');
        answer.style.display = 'block';
        icon.textContent = '×';
        icon.style.transform = 'rotate(0deg)';
    }
}
</script>

<?php include 'includes/footer.php'; ?>