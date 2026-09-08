</main>

<!-- Footer (Public pages only) -->
<?php if (!isset($sidebarRole) && !isset($isDashboard)): ?>
<footer class="footer bg-dark text-light py-5 mt-auto">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <a href="<?= baseUrl() ?>" class="text-decoration-none text-light">
                    <h5 class="fw-bold mb-3"><span class="brand-icon d-inline-flex me-2" style="width:28px;height:28px;font-size:0.7rem;"><i class="fas fa-bolt"></i></span><?= e(APP_NAME) ?></h5>
                </a>
                <p class="text-muted"><?= e(APP_TAGLINE) ?>. India's trusted platform connecting businesses with top freelancers across Bharat.</p>
                <div class="social-links">
                    <a href="https://facebook.com/nexawork" target="_blank" rel="noopener noreferrer" class="text-light me-3" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://twitter.com/nexawork" target="_blank" rel="noopener noreferrer" class="text-light me-3" title="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="https://linkedin.com/company/nexawork" target="_blank" rel="noopener noreferrer" class="text-light me-3" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    <a href="https://instagram.com/nexawork" target="_blank" rel="noopener noreferrer" class="text-light" title="Instagram"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            <div class="col-lg-2 col-md-4">
                <h6 class="fw-bold mb-3">For Clients</h6>
                <ul class="list-unstyled footer-links">
                    <li><a href="<?= baseUrl('register.php?role=client') ?>">Post a Project</a></li>
                    <li><a href="<?= baseUrl('freelancers.php') ?>">Find Freelancers</a></li>
                    <li><a href="<?= baseUrl('categories.php') ?>">Browse Categories</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-md-4">
                <h6 class="fw-bold mb-3">For Freelancers</h6>
                <ul class="list-unstyled footer-links">
                    <li><a href="<?= baseUrl('register.php?role=freelancer') ?>">Find Work</a></li>
                    <li><a href="<?= baseUrl('projects.php') ?>">Browse Projects</a></li>
                    <li><a href="<?= baseUrl(isLoggedIn() && currentUserRole() === 'freelancer' ? 'freelancer/skill-tests.php' : 'register.php?role=freelancer') ?>">Skill Tests</a></li>
                    <li><a href="<?= baseUrl('leaderboard.php') ?>">Leaderboard</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-md-4">
                <h6 class="fw-bold mb-3">Company</h6>
                <ul class="list-unstyled footer-links">
                    <li><a href="<?= baseUrl('about.php') ?>">About Us</a></li>
                    <li><a href="<?= baseUrl('contact.php') ?>">Contact</a></li>
                    <li><a href="<?= baseUrl('privacy.php') ?>">Privacy Policy</a></li>
                    <li><a href="<?= baseUrl('terms.php') ?>">Terms of Service</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-md-4">
                <h6 class="fw-bold mb-3">Support</h6>
                <ul class="list-unstyled footer-links">
                    <li><a href="<?= baseUrl('help.php') ?>">Help Center</a></li>
                    <li><a href="<?= baseUrl('faq.php') ?>">FAQ</a></li>
                    <li><a href="<?= baseUrl('community.php') ?>">Community</a></li>
                </ul>
            </div>
        </div>
        <hr class="my-4 border-secondary">
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="text-muted mb-0">&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. All rights reserved.</p>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="text-muted mb-0">Version <?= e(APP_VERSION) ?></p>
            </div>
        </div>
    </div>
</footer>
<?php endif; ?>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    const APP_URL = '<?= baseUrl() ?>';
    const CSRF_TOKEN = '<?= generateCsrfToken() ?>';
</script>
<script src="<?= baseUrl('assets/js/app.js') ?>"></script>
<script src="<?= baseUrl('assets/js/ai-helper.js') ?>"></script>
<?php if (isset($extraJs)): ?>
    <?= $extraJs ?>
<?php endif; ?>
</body>
</html>
