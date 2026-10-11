<?php
/**
 * FastBite - Reusable Main Footer Component
 * STEP 22: FastBite Contact Page Integration
 * Design Identity: Pink & White
 */
?>
<footer class="main-footer bg-white border-top pt-5 pb-4 mt-auto" aria-label="FastBite Footer">
    <div class="container">
        <div class="row g-4 pb-4 border-bottom">
            <!-- Brand & Description -->
            <div class="col-12 col-md-4 col-lg-4">
                <a class="navbar-brand d-inline-flex align-items-center gap-2 mb-3 text-decoration-none" href="index.php">
                    <span class="brand-icon d-flex align-items-center justify-content-center">
                        <i class="bi bi-fire text-white"></i>
                    </span>
                    <span class="brand-text fs-4 fw-bold">Fast<span class="text-primary-accent">Bite</span></span>
                </a>
                <p class="text-muted small mb-3" style="max-width: 320px;">
                    Serving freshly prepared fast food with premium ingredients, lightning-fast delivery, and unbeatable taste.
                </p>
                <div class="d-flex align-items-center gap-2">
                    <a href="#" class="btn btn-sm btn-icon-nav text-primary-accent border rounded-circle" aria-label="Facebook" title="Facebook"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="btn btn-sm btn-icon-nav text-primary-accent border rounded-circle" aria-label="Instagram" title="Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="btn btn-sm btn-icon-nav text-primary-accent border rounded-circle" aria-label="Twitter" title="Twitter"><i class="bi bi-twitter-x"></i></a>
                    <a href="#" class="btn btn-sm btn-icon-nav text-primary-accent border rounded-circle" aria-label="WhatsApp" title="WhatsApp"><i class="bi bi-whatsapp"></i></a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-6 col-md-2 col-lg-2">
                <h6 class="fw-bold text-dark text-uppercase extra-small mb-3" style="letter-spacing: 0.05em;">Explore</h6>
                <ul class="list-unstyled d-flex flex-column gap-2 small mb-0">
                    <li><a href="index.php" class="text-decoration-none text-muted hover-pink">Home</a></li>
                    <li><a href="menu.php" class="text-decoration-none text-muted hover-pink">Our Menu</a></li>
                    <li><a href="about.php" class="text-decoration-none text-muted hover-pink">About Us</a></li>
                    <li><a href="contact.php" class="text-decoration-none text-muted hover-pink">Contact Us</a></li>
                </ul>
            </div>

            <!-- Customer Service -->
            <div class="col-6 col-md-3 col-lg-3">
                <h6 class="fw-bold text-dark text-uppercase extra-small mb-3" style="letter-spacing: 0.05em;">Customer Hub</h6>
                <ul class="list-unstyled d-flex flex-column gap-2 small mb-0">
                    <li><a href="customer-dashboard.php" class="text-decoration-none text-muted hover-pink">My Account</a></li>
                    <li><a href="my-orders.php" class="text-decoration-none text-muted hover-pink">Track Orders</a></li>
                    <li><a href="addresses.php" class="text-decoration-none text-muted hover-pink">Saved Addresses</a></li>
                    <li><a href="notifications.php" class="text-decoration-none text-muted hover-pink">Notifications</a></li>
                </ul>
            </div>

            <!-- Contact & Hours -->
            <div class="col-12 col-md-3 col-lg-3">
                <h6 class="fw-bold text-dark text-uppercase extra-small mb-3" style="letter-spacing: 0.05em;">Get in Touch</h6>
                <ul class="list-unstyled d-flex flex-column gap-2 small mb-0">
                    <li class="d-flex align-items-start gap-2 text-muted">
                        <i class="bi bi-geo-alt text-primary-accent mt-1"></i>
                        <span>City Center, Hargeisa</span>
                    </li>
                    <li class="d-flex align-items-center gap-2">
                        <i class="bi bi-telephone text-primary-accent"></i>
                        <a href="tel:+252630000000" class="text-decoration-none text-muted hover-pink">+252 63 000 0000</a>
                    </li>
                    <li class="d-flex align-items-center gap-2">
                        <i class="bi bi-envelope text-primary-accent"></i>
                        <a href="mailto:support@fastbite.com" class="text-decoration-none text-muted hover-pink">support@fastbite.com</a>
                    </li>
                    <li class="d-flex align-items-center gap-2 text-muted">
                        <i class="bi bi-clock text-primary-accent"></i>
                        <span>10:00 AM — 11:00 PM Daily</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Copyright & Legal -->
        <div class="d-flex flex-column flex-md-row align-items-center justify-content-between pt-3 gap-2 extra-small text-muted">
            <div>
                &copy; <?= date('Y'); ?> FastBite Food Ordering. All rights reserved.
            </div>
            <div class="d-flex gap-3">
                <a href="#" class="text-decoration-none text-muted">Privacy Policy</a>
                <span>&bull;</span>
                <a href="#" class="text-decoration-none text-muted">Terms of Service</a>
                <span>&bull;</span>
                <a href="contact.php" class="text-decoration-none text-primary-accent fw-bold">Support</a>
            </div>
        </div>
    </div>
</footer>
