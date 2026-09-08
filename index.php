<?php
/**
 * NexaWork - Landing Page
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Home';

$featuredFreelancers = db()->query('
    SELECT fp.*, u.first_name, u.last_name, u.avatar, u.is_verified
    FROM freelancer_profiles fp
    JOIN users u ON fp.user_id = u.id
    WHERE u.is_active = 1
    ORDER BY fp.avg_rating DESC, fp.completed_projects DESC
    LIMIT 4
')->fetchAll();

foreach ($featuredFreelancers as &$fl) {
    $fl['trust_score'] = calculateTrustScore($fl);
}
unset($fl);

$featuredProjects = db()->query('
    SELECT p.*, u.first_name, u.last_name,
        (SELECT COUNT(*) FROM bids WHERE project_id = p.id) as bid_count
    FROM projects p
    JOIN users u ON p.client_id = u.id
    WHERE p.status = "open"
    ORDER BY p.created_at DESC
    LIMIT 6
')->fetchAll();

$stats = [
    'users'       => (int) db()->query('SELECT COUNT(*) FROM users WHERE role != "admin"')->fetchColumn(),
    'freelancers' => (int) db()->query('SELECT COUNT(*) FROM users WHERE role = "freelancer"')->fetchColumn(),
    'projects'    => (int) db()->query('SELECT COUNT(*) FROM projects')->fetchColumn(),
    'completed'   => (int) db()->query('SELECT COUNT(*) FROM projects WHERE status = "completed"')->fetchColumn(),
];

$categories = getCategoryStats(6);
$liveActivity = getLiveActivity(8);

require_once __DIR__ . '/includes/header.php';
?>

<!-- Live Activity Ticker -->
<div class="activity-ticker">
    <div class="container">
        <div class="ticker-wrap">
            <span class="ticker-label"><i class="fas fa-circle text-success me-2" style="font-size:0.5rem;"></i>Live</span>
            <div class="ticker-content" id="activityTicker">
                <?php foreach ($liveActivity as $act): ?>
                <span class="ticker-item"><i class="fas <?= $act['icon'] ?> me-2"></i><?= $act['text'] ?> <small class="opacity-75">· <?= timeAgo($act['time']) ?></small></span>
                <?php endforeach; ?>
                <?php if (empty($liveActivity)): ?>
                <span class="ticker-item">Welcome to <?= e(APP_NAME) ?> — post a project or start bidding today!</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-particles"></div>
    <div class="container position-relative">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <span class="hero-badge fade-in-up"><i class="fas fa-sparkles me-2"></i>AI-Powered Matching Engine</span>
                <h1 class="mb-4 fade-in-up">Hire Elite Talent.<br>Ship Faster. Scale Smarter.</h1>
                <p class="mb-4 fade-in-up hero-subtitle">India's premier freelance marketplace — connect with verified talent from Bangalore to Mumbai using smart match scoring, UPI-ready escrow, and milestone payments in ₹.</p>
                <div class="hero-search position-relative fade-in-up">
                    <input type="text" class="form-control" placeholder="Search projects, skills, or freelancers..." id="heroSearch" autocomplete="off">
                    <button class="btn btn-primary rounded-pill px-4" id="heroSearchBtn">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <div class="search-suggestions d-none" id="searchSuggestions"></div>
                </div>
                <div class="d-flex gap-3 flex-wrap fade-in-up mt-4">
                    <a href="<?= baseUrl('register.php?role=client') ?>" class="btn btn-accent btn-lg px-4">
                        <i class="fas fa-rocket me-2"></i>Post a Project
                    </a>
                    <a href="<?= baseUrl('register.php?role=freelancer') ?>" class="btn btn-outline-hero btn-lg px-4">
                        <i class="fas fa-bolt me-2"></i>Join as Talent
                    </a>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-block">
                <div class="hero-visual fade-in-up">
                    <div class="hero-card hero-card-1 glass-card">
                        <i class="fas fa-bolt text-warning"></i>
                        <div>
                            <strong>94% Match</strong>
                            <small class="d-block text-muted">Skill alignment score</small>
                        </div>
                    </div>
                    <div class="hero-card hero-card-2 glass-card">
                        <i class="fas fa-shield-alt text-success"></i>
                        <div>
                            <strong>Escrow Protected</strong>
                            <small class="d-block text-muted">Milestone payments</small>
                        </div>
                    </div>
                    <div class="hero-card hero-card-3 glass-card">
                        <i class="fas fa-chart-line text-info"></i>
                        <div>
                            <strong>Live Analytics</strong>
                            <small class="d-block text-muted">Track every project</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row hero-stats mt-5 pt-4">
            <div class="col-6 col-md-3 stat-item">
                <div class="stat-number" data-count="<?= $stats['users'] ?>">0</div>
                <div class="stat-label">Active Users</div>
            </div>
            <div class="col-6 col-md-3 stat-item">
                <div class="stat-number" data-count="<?= $stats['freelancers'] ?>">0</div>
                <div class="stat-label">Freelancers</div>
            </div>
            <div class="col-6 col-md-3 stat-item">
                <div class="stat-number" data-count="<?= $stats['projects'] ?>">0</div>
                <div class="stat-label">Projects Posted</div>
            </div>
            <div class="col-6 col-md-3 stat-item">
                <div class="stat-number" data-count="<?= $stats['completed'] ?>">0</div>
                <div class="stat-label">Completed</div>
            </div>
        </div>
    </div>
</section>

<!-- Platform Features -->
<section class="section-padding bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Why NexaWork?</h2>
            <p class="section-subtitle">Advanced tools that set us apart from ordinary freelance platforms</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-card h-100">
                    <div class="feature-icon bg-teal"><i class="fas fa-brain"></i></div>
                    <h5>Smart Match Engine</h5>
                    <p class="text-muted mb-0">Multi-factor AI scoring matches freelancers to projects by skills, ratings, experience, and budget fit.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card h-100">
                    <div class="feature-icon bg-coral"><i class="fas fa-layer-group"></i></div>
                    <h5>Milestone Escrow</h5>
                    <p class="text-muted mb-0">Break projects into milestones. Release payments only when deliverables are approved.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card h-100">
                    <div class="feature-icon bg-purple"><i class="fas fa-shield-alt"></i></div>
                    <h5>Trust Score System</h5>
                    <p class="text-muted mb-0">Verified badges and trust scores help you hire with confidence every time.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Categories -->
<section class="section-padding">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Browse by Category</h2>
            <p class="section-subtitle">Find the right talent for any type of project</p>
        </div>
        <div class="row g-4">
            <?php if (empty($categories)): ?>
                <div class="col-12 text-center text-muted">No open projects yet. <a href="<?= baseUrl('register.php?role=client') ?>">Post the first one!</a></div>
            <?php else: ?>
            <?php foreach ($categories as $cat): ?>
            <div class="col-lg-2 col-md-4 col-6">
                <a href="<?= baseUrl('projects.php?category=' . urlencode($cat['category'])) ?>" class="text-decoration-none">
                    <div class="card category-card h-100">
                        <div class="category-icon" style="background:<?= $cat['color'] ?>">
                            <i class="fas <?= $cat['icon'] ?>"></i>
                        </div>
                        <h6 class="fw-bold"><?= e($cat['category']) ?></h6>
                        <small class="text-muted"><?= $cat['count'] ?> projects</small>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Featured Projects -->
<section class="section-padding bg-white">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="section-title mb-0">Latest Projects</h2>
                <p class="text-muted mb-0">Find your next opportunity</p>
            </div>
            <a href="<?= baseUrl('projects.php') ?>" class="btn btn-outline-primary">View All <i class="fas fa-arrow-right ms-1"></i></a>
        </div>
        <div class="row g-4">
            <?php foreach ($featuredProjects as $project): ?>
            <div class="col-lg-4 col-md-6">
                <div class="card project-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge bg-light text-dark"><?= e($project['category']) ?></span>
                            <?= getStatusBadge($project['status']) ?>
                        </div>
                        <h5 class="card-title">
                            <a href="<?= baseUrl('project-detail.php?id=' . $project['id']) ?>" class="text-decoration-none text-dark">
                                <?= e($project['title']) ?>
                            </a>
                        </h5>
                        <p class="card-text text-muted small"><?= e(substr($project['description'], 0, 120)) ?>...</p>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="budget"><?= formatMoney((float)$project['budget']) ?></span>
                            <small class="text-muted"><i class="fas fa-gavel me-1"></i><?= $project['bid_count'] ?> bids</small>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <small class="text-muted">
                            <i class="fas fa-clock me-1"></i>Deadline: <?= formatDate($project['deadline']) ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Freelancers -->
<section class="section-padding">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="section-title mb-0">Top Freelancers</h2>
                <p class="text-muted mb-0">Highly rated professionals ready to work</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= baseUrl('leaderboard.php') ?>" class="btn btn-outline-primary"><i class="fas fa-trophy me-1"></i>Leaderboard</a>
                <a href="<?= baseUrl('freelancers.php') ?>" class="btn btn-outline-primary">View All</a>
            </div>
        </div>
        <div class="row g-4">
            <?php foreach ($featuredFreelancers as $fl): ?>
            <div class="col-lg-3 col-md-6">
                <div class="card freelancer-card h-100 p-4">
                    <img src="<?= getAvatarUrl($fl['avatar']) ?>" alt="<?= e($fl['first_name']) ?>" class="avatar">
                    <h5 class="fw-bold mb-1"><?= e($fl['first_name'] . ' ' . $fl['last_name']) ?></h5>
                    <p class="text-muted small mb-2"><?= e($fl['title'] ?? 'Freelancer') ?></p>
                    <?= renderTrustBadge($fl['trust_score']) ?>
                    <?= renderStars((float)$fl['avg_rating']) ?>
                    <p class="small text-muted mt-2 mb-3">
                        <i class="fas fa-map-marker-alt me-1"></i><?= e($fl['location'] ?? 'Remote') ?>
                    </p>
                    <div class="d-flex justify-content-between small text-muted mb-3">
                        <span><?= $fl['completed_projects'] ?> projects</span>
                        <span><?= formatMoney((float)$fl['hourly_rate']) ?>/hr</span>
                    </div>
                    <a href="<?= baseUrl('freelancer-profile.php?id=' . $fl['user_id']) ?>" class="btn btn-outline-primary btn-sm w-100">View Profile</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- How It Works -->
<section class="section-padding bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">How It Works</h2>
            <p class="section-subtitle">From idea to delivery in four smart steps</p>
        </div>
        <div class="row g-4 text-center">
            <div class="col-md-3">
                <div class="step-card h-100">
                    <div class="step-number">1</div>
                    <h5>Post & Match</h5>
                    <p class="text-muted small">Describe your project. Our engine instantly surfaces the best-matched freelancers.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="step-card h-100">
                    <div class="step-number">2</div>
                    <h5>Review Bids</h5>
                    <p class="text-muted small">Compare proposals, trust scores, portfolios, and skill certifications side by side.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="step-card h-100">
                    <div class="step-number">3</div>
                    <h5>Milestone Work</h5>
                    <p class="text-muted small">Collaborate via messaging. Track progress through milestone-based deliverables.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="step-card h-100">
                    <div class="step-number">4</div>
                    <h5>Secure Pay</h5>
                    <p class="text-muted small">Funds stay in escrow until you approve each milestone. Then leave a review.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Trusted Companies Banner -->
<section class="py-4 bg-light border-top border-bottom">
    <div class="container text-center">
        <small class="text-muted text-uppercase fw-bold tracking-wider d-block mb-3">Trusted by leading startups and enterprises across Bharat</small>
        <div class="d-flex justify-content-center align-items-center flex-wrap gap-4 opacity-75 grayscale-icons">
            <span class="fw-bold fs-5 text-secondary"><i class="fab fa-google me-2"></i>Google Cloud</span>
            <span class="fw-bold fs-5 text-secondary"><i class="fab fa-microsoft me-2"></i>Microsoft</span>
            <span class="fw-bold fs-5 text-secondary"><i class="fab fa-amazon me-2"></i>AWS India</span>
            <span class="fw-bold fs-5 text-secondary"><i class="fas fa-building me-2"></i>Infosys</span>
            <span class="fw-bold fs-5 text-secondary"><i class="fas fa-microchip me-2"></i>TCS</span>
            <span class="fw-bold fs-5 text-secondary"><i class="fas fa-globe me-2"></i>Wipro</span>
        </div>
    </div>
</section>

<!-- Top Trending Skills -->
<section class="section-padding bg-white">
    <div class="container text-center">
        <h2 class="section-title">Trending Skills & Technologies</h2>
        <p class="section-subtitle mb-4">Explore top talent across in-demand technical & creative domains</p>
        <div class="d-flex flex-wrap justify-content-center gap-2 max-w-800 mx-auto">
            <?php
            $topSkillsList = ['React.js', 'Node.js', 'Python AI/ML', 'Flutter', 'UI/UX Figma', 'Laravel PHP', 'PostgreSQL', 'Full Stack MERN', 'DevOps AWS', 'SEO Marketing', 'Graphic Design', 'Copywriting'];
            foreach ($topSkillsList as $sk):
            ?>
            <a href="<?= baseUrl('projects.php?q=' . urlencode($sk)) ?>" class="btn btn-outline-primary rounded-pill px-3 py-2 fs-7 shadow-sm">
                <i class="fas fa-bolt me-1 text-warning"></i><?= e($sk) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Testimonials -->
<section class="section-padding">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">What Our Users Say</h2>
            <p class="section-subtitle">Trusted by startups and enterprises across India</p>
        </div>
        <div class="row g-4">
            <?php
            $testimonials = [
                ['name' => 'Rajesh Kumar', 'role' => 'CEO, TechVista Solutions — Bangalore', 'text' => 'NexaWork helped our Bangalore startup hire talented developers across India. The smart match scores saved us hours of screening and we found the perfect fit within our budget.', 'rating' => 5],
                ['name' => 'Priya Patel', 'role' => 'Marketing Director, Digital Creations India — Mumbai', 'text' => 'Milestone escrow changed how we work with freelancers. We release payment only when deliverables meet our standards. Perfect for Indian SMEs and agencies.', 'rating' => 5],
                ['name' => 'Vikram Desai', 'role' => 'Founder, Pune Commerce Hub — Pune', 'text' => 'As a client on NexaWork, I have hired designers and developers from Hyderabad, Delhi, and Chennai. The trust score system makes hiring remote Indian talent effortless.', 'rating' => 5],
            ];
            foreach ($testimonials as $t):
            ?>
            <div class="col-md-4">
                <div class="card testimonial-card h-100 p-4 shadow-sm border-0">
                    <i class="fas fa-quote-left quote-icon"></i>
                    <p class="mb-3 text-secondary"><?= e($t['text']) ?></p>
                    <?= renderStars((float)$t['rating']) ?>
                    <div class="mt-3">
                        <strong class="text-dark"><?= e($t['name']) ?></strong>
                        <small class="d-block text-muted"><?= e($t['role']) ?></small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- FAQ Accordion Section -->
<section class="section-padding bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Frequently Asked Questions</h2>
            <p class="section-subtitle">Everything you need to know about hiring and earning on NexaWork</p>
        </div>
        <div class="accordion max-w-800 mx-auto shadow-sm rounded-3 overflow-hidden" id="faqAccordion">
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                        How does NexaWork Escrow protection work?
                    </button>
                </h2>
                <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-muted">
                        Clients deposit project milestone funds into NexaWork Escrow upon contract initiation. Funds remain safely locked until the client verifies and approves the completed deliverable.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                        How does the NexaAI Smart Match score work?
                    </button>
                </h2>
                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-muted">
                        NexaAI analyzes freelancer skills, ratings, completed projects, hourly rates, and proposal text against client project requirements to compute an instant percentage compatibility score.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                        What payment methods are supported in India?
                    </button>
                </h2>
                <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-muted">
                        NexaWork supports UPI, Credit/Debit Cards, Net Banking, Razorpay checkout, and direct NEFT/IMPS bank withdrawals formatted in Indian Rupees (₹).
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Newsletter Subscription -->
<section class="py-5 bg-gradient text-white" style="background: linear-gradient(135deg, #4F7CFF 0%, #0B1020 100%);">
    <div class="container text-center max-w-600">
        <h3 class="fw-bold mb-2">Subscribe to NexaWork Insights</h3>
        <p class="text-white-50 mb-4">Get weekly AI matching updates, top freelance projects, and talent spot-lights.</p>
        <form class="d-flex gap-2" onsubmit="event.preventDefault(); alert('Thank you for subscribing to NexaWork updates!'); this.reset();">
            <input type="email" class="form-control form-control-lg" placeholder="Enter your email address..." required>
            <button type="submit" class="btn btn-warning btn-lg fw-bold px-4">Subscribe</button>
        </form>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section section-padding">
    <div class="container text-center text-white">
        <h2 class="mb-3">Ready to Build Something Great?</h2>
        <p class="mb-4 opacity-90">Join NexaWork — where AI matching meets human talent.</p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="<?= baseUrl('register.php') ?>" class="btn btn-light btn-lg px-5 fw-bold">Get Started Free</a>
            <a href="<?= baseUrl('leaderboard.php') ?>" class="btn btn-outline-light btn-lg px-5 fw-bold"><i class="fas fa-trophy me-2"></i>See Top Talent</a>
        </div>
    </div>
</section>

<?php
$extraJs = '<script>$(function(){ initHeroSearch(); initCounterAnimation(); });</script>';
require_once __DIR__ . '/includes/footer.php';
?>

