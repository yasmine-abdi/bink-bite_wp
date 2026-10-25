<?php
/**
 * FastBite - Customer Contact Page
 * STEP 22: FastBite Contact Page
 * 
 * Features:
 * - Contact information cards (Support, Call, Email, Location)
 * - Interactive client-validated contact form with demo toast
 * - Existing order support section
 * - Visual location card
 * - Frequently Asked Questions (FAQ) accordion
 * - Pink + White Design Identity
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/image-helper.php';

$currentPage = 'contact';
$projectName = 'FastBite';
$bannerImage = function_exists('getBannerImage') ? getBannerImage('contact-banner.jpg') : 'assets/images/placeholders/banner-placeholder.jpg';

$contactSuccess = false;
$contactError = '';
$restaurantPhone = function_exists('dbGetSetting') ? dbGetSetting('restaurant_phone', '+252 90 761 9928') : '+252 90 761 9928';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && (!empty($_POST['fullName']) || !empty($_POST['name']))) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $contactError = 'Security validation failed. Please refresh the page and try again.';
    } else {
        $name = trim($_POST['fullName'] ?? $_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        if (empty($subject)) {
            $subject = 'General Inquiry';
        }
        $message = trim($_POST['message'] ?? '');

        if (empty($name) || empty($email) || empty($message)) {
            $contactError = 'Please fill in all required fields (Name, Email, and Message).';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $contactError = 'Please enter a valid email address.';
        } else {
            $saved = dbSaveContactMessage([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'subject' => $subject,
                'message' => $message
            ]);
            if ($saved) {
                $contactSuccess = true;
            } else {
                $contactError = 'Unable to send message right now. Please try again.';
            }
        }
    }

    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
              || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $contactSuccess,
            'message' => $contactSuccess ? 'Thank you! Your message has been received.' : $contactError
        ]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | FastBite</title>
    <meta name="description" content="Contact FastBite for questions, order support, feedback, and customer assistance.">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom CSS (Pink + White Design System) -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

    <!-- Main Navbar Component -->
    <?php include_once __DIR__ . '/includes/navbar.php'; ?>

    <main id="main-content" class="flex-grow-1">

        <!-- 1. Contact Hero / Header Section -->
        <header class="contact-hero-section py-5 text-center bg-white border-bottom position-relative overflow-hidden">
            <div class="container py-2 py-lg-4 position-relative" style="z-index: 2;">
                <span class="menu-eyebrow d-inline-block text-uppercase fw-bold text-primary-accent mb-2">CONTACT US</span>
                <h1 class="display-5 fw-bold text-dark mb-3">We'd Love to Hear From You</h1>
                <p class="lead text-muted mx-auto mb-0" style="max-width: 620px; font-size: 1.05rem;">
                    Have a question, feedback, or need help with your order? Our team is here to help.
                </p>
            </div>
        </header>

        <!-- 2. Main Two-Column Contact Section -->
        <section class="py-5" aria-label="Contact Information and Form">
            <div class="container">
                <div class="row g-4 g-lg-5">

                    <!-- LEFT COLUMN: Contact Cards, Order Support & Location -->
                    <div class="col-12 col-lg-5">
                        <div class="d-flex flex-column gap-4">

                            <!-- Contact Information Cards (4 items) -->
                            <div class="row g-3">
                                <!-- Card 1: Customer Support -->
                                <div class="col-12 col-sm-6 col-lg-12">
                                    <div class="card h-100 border-0 shadow-sm p-3 p-md-4 contact-card">
                                        <div class="d-flex align-items-start gap-3">
                                            <div class="contact-icon-box bg-pink-soft text-primary-accent rounded-circle p-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 50px; height: 50px;">
                                                <i class="bi bi-headset fs-4"></i>
                                            </div>
                                            <div>
                                                <h2 class="h6 fw-bold text-dark mb-1">Customer Support</h2>
                                                <p class="text-muted small mb-0">Need help with an order? Our support team is ready to assist.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card 2: Call Us -->
                                <div class="col-12 col-sm-6 col-lg-12">
                                    <div class="card h-100 border-0 shadow-sm p-3 p-md-4 contact-card">
                                        <div class="d-flex align-items-start gap-3">
                                            <div class="contact-icon-box bg-pink-soft text-primary-accent rounded-circle p-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 50px; height: 50px;">
                                                <i class="bi bi-telephone fs-4"></i>
                                            </div>
                                            <div>
                                                <h2 class="h6 fw-bold text-dark mb-1">Call Us</h2>
                                                <a href="tel:+252907619928" class="d-block fw-bold text-primary-accent text-decoration-none mb-1">
                                                    +252 90 761 9928
                                                </a>
                                                <span class="text-muted extra-small">Available 10:00 AM — 11:00 PM Daily</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                 <!-- Card 3: Email Us -->
                                <div class="col-12 col-sm-6 col-lg-12">
                                    <div class="card h-100 border-0 shadow-sm p-3 p-md-4 contact-card">
                                        <div class="d-flex align-items-start gap-3">
                                            <div class="contact-icon-box bg-pink-soft text-primary-accent rounded-circle p-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 50px; height: 50px;">
                                                <i class="bi bi-envelope fs-4"></i>
                                            </div>
                                            <div>
                                                <h2 class="h6 fw-bold text-dark mb-1">Email Us</h2>
                                                <a href="mailto:yaasmiin378@gmail.com" class="d-block fw-bold text-primary-accent text-decoration-none mb-1">
                                                    yaasmiin378@gmail.com
                                                </a>
                                                <span class="text-muted extra-small">Expect a response within 24 hours</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card 4: Visit Us -->
                                <div class="col-12 col-sm-6 col-lg-12">
                                    <div class="card h-100 border-0 shadow-sm p-3 p-md-4 contact-card">
                                        <div class="d-flex align-items-start gap-3">
                                            <div class="contact-icon-box bg-pink-soft text-primary-accent rounded-circle p-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 50px; height: 50px;">
                                                <i class="bi bi-geo-alt fs-4"></i>
                                            </div>
                                            <div>
                                                <h2 class="h6 fw-bold text-dark mb-1">Visit Us</h2>
                                                <p class="fw-semibold text-dark mb-0">City Center, Bosaso</p>
                                                <span class="text-muted extra-small">Dine-in &bull; Takeaway &bull; Drive-thru</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Order Support Card -->
                            <div class="card border-0 shadow-sm p-4 bg-pink-soft border-pink-subtle">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="bi bi-bag-check-fill text-primary-accent fs-5"></i>
                                    <h2 class="h6 fw-bold text-dark mb-0">Need Help With an Existing Order?</h2>
                                </div>
                                <p class="text-muted small mb-3">
                                    Have a question about your order? Check your order history or contact our support team.
                                </p>
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="my-orders.php" class="btn btn-sm btn-auth-primary d-inline-flex align-items-center gap-1 shadow-sm">
                                        <i class="bi bi-bag"></i>
                                        <span>View My Orders</span>
                                    </a>
                                    <a href="#contact-form" class="btn btn-sm btn-outline-pink d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-chat-dots"></i>
                                        <span>Contact Support</span>
                                    </a>
                                </div>
                            </div>

                            <!-- Map / Visual Location Card -->
                            <div class="card border-0 shadow-sm overflow-hidden" id="location-section">
                                <div class="p-3 bg-white border-bottom">
                                    <h2 class="h6 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                        <i class="bi bi-map text-primary-accent"></i>
                                        <span>Find Us</span>
                                    </h2>
                                </div>
                                <div class="location-map-placeholder p-4 text-center bg-light d-flex flex-column align-items-center justify-content-center" style="min-height: 180px;">
                                    <div class="rounded-circle bg-pink-soft text-primary-accent p-3 mb-2 shadow-sm">
                                        <i class="bi bi-geo-alt-fill fs-2"></i>
                                    </div>
                                    <h3 class="h6 fw-bold text-dark mb-1">FastBite — City Center, Bosaso</h3>
                                    <p class="text-muted extra-small mb-2">Main Street, City Center, Bosaso</p>
                                    <span class="badge bg-white text-primary-accent border border-pink-subtle extra-small">
                                        <i class="bi bi-clock me-1"></i>Open Daily 10:00 AM — 11:00 PM
                                    </span>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- RIGHT COLUMN: Contact Form -->
                    <div class="col-12 col-lg-7" id="contact-form">
                        <div class="card border-0 shadow-sm p-4 p-md-5 h-100 bg-white">
                            <div class="mb-4 pb-2 border-bottom">
                                <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle extra-small mb-2">DIRECT INQUIRY</span>
                                <h2 class="h4 fw-bold text-dark mb-1">Send Us a Message</h2>
                                <p class="text-muted small mb-0">Fill in the details below and our customer care team will get back to you promptly.</p>
                            </div>

                            <!-- Contact Success Feedback Alert -->
                            <?php if ($contactSuccess): ?>
                                <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm mb-4" role="alert">
                                    <i class="bi bi-check-circle-fill fs-5 text-success"></i>
                                    <div>
                                        <strong>Thank you!</strong> Your message has been received. Our team will contact you shortly.
                                    </div>
                                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($contactError)): ?>
                                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm mb-4" role="alert">
                                    <i class="bi bi-exclamation-triangle-fill fs-5 text-danger"></i>
                                    <div><?= htmlspecialchars($contactError); ?></div>
                                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            <div class="alert alert-success alert-dismissible fade show d-none align-items-center gap-2 shadow-sm mb-4" id="contactSuccessAlert" role="alert">
                                <i class="bi bi-check-circle-fill fs-5 text-success"></i>
                                <div>
                                    <strong>Thank you!</strong> Your message has been received. Our team will contact you shortly.
                                </div>
                                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <div class="alert alert-danger alert-dismissible fade show d-none align-items-center gap-2 shadow-sm mb-4" id="contactErrorAlert" role="alert">
                                <i class="bi bi-exclamation-triangle-fill fs-5 text-danger"></i>
                                <div id="contactErrorAlertText">
                                    An error occurred while sending your message.
                                </div>
                                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>

                            <!-- Form -->
                            <form method="POST" action="contact.php" id="customerContactForm" novalidate>
                                <?= csrfField(); ?>
                                <div class="row g-3">

                                    <!-- Full Name (Required) -->
                                    <div class="col-12 col-md-6">
                                        <label for="contactFullName" class="form-label small fw-bold text-dark">
                                            Full Name <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
                                            <input type="text" class="form-control border-start-0 ps-0" id="contactFullName" name="fullName" placeholder="e.g. Mohamed Ali" required>
                                        </div>
                                        <div class="invalid-feedback" id="nameFeedback">Please enter your name.</div>
                                    </div>

                                    <!-- Email Address (Required) -->
                                    <div class="col-12 col-md-6">
                                        <label for="contactEmail" class="form-label small fw-bold text-dark">
                                            Email Address <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                                            <input type="email" class="form-control border-start-0 ps-0" id="contactEmail" name="email" placeholder="e.g. name@example.com" required>
                                        </div>
                                        <div class="invalid-feedback" id="emailFeedback">Please enter a valid email address.</div>
                                    </div>

                                    <!-- Phone Number (Optional) -->
                                    <div class="col-12 col-md-6">
                                        <label for="contactPhone" class="form-label small fw-bold text-dark">
                                            Phone Number <span class="text-muted fw-normal">(Optional)</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-telephone text-muted"></i></span>
                                            <input type="tel" class="form-control border-start-0 ps-0" id="contactPhone" name="phone" placeholder="e.g. +252 90 761 9928">
                                        </div>
                                    </div>

                                    <!-- Subject (Optional) -->
                                    <div class="col-12 col-md-6">
                                        <label for="contactSubject" class="form-label small fw-bold text-dark">
                                            Subject <span class="text-muted fw-normal">(Optional)</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-chat-left-text text-muted"></i></span>
                                            <input type="text" class="form-control border-start-0 ps-0" id="contactSubject" name="subject" placeholder="What is this about? (Optional)">
                                        </div>
                                        <div class="invalid-feedback" id="subjectFeedback">Please enter a subject.</div>
                                    </div>

                                    <!-- Message (Required) -->
                                    <div class="col-12">
                                        <label for="contactMessage" class="form-label small fw-bold text-dark">
                                            Message <span class="text-danger">*</span>
                                        </label>
                                        <textarea class="form-control" id="contactMessage" name="message" rows="5" placeholder="How can we help you today? Please provide any details..." required></textarea>
                                        <div class="invalid-feedback" id="messageFeedback">Please enter your message.</div>
                                    </div>

                                    <!-- Submit Button -->
                                    <div class="col-12 pt-2">
                                        <button type="submit" class="btn btn-auth-primary w-100 py-2 d-inline-flex align-items-center justify-content-center gap-2 shadow-sm" id="btnContactSubmit">
                                            <i class="bi bi-send-fill"></i>
                                            <span>Send Message</span>
                                        </button>
                                    </div>

                                </div>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <!-- 3. Frequently Asked Questions (FAQ) Section -->
        <section class="py-5 bg-white border-top border-bottom" aria-label="Frequently Asked Questions">
            <div class="container" style="max-width: 900px;">
                <div class="text-center mb-4 pb-2">
                    <span class="menu-eyebrow d-inline-block text-uppercase fw-bold text-primary-accent mb-2">QUICK ANSWERS</span>
                    <h2 class="h3 fw-bold text-dark mb-2">Frequently Asked Questions</h2>
                    <p class="text-muted small mb-0">Find immediate answers to common questions about ordering, deliveries, and payment methods.</p>
                </div>

                <!-- Accordion -->
                <div class="accordion custom-faq-accordion" id="contactFaqAccordion">

                    <!-- Question 1 -->
                    <div class="accordion-item border-0 mb-3 rounded shadow-sm overflow-hidden">
                        <h3 class="accordion-header" id="faqHeadingOne">
                            <button class="accordion-button fw-bold text-dark collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseOne" aria-expanded="false" aria-controls="faqCollapseOne">
                                1. How can I place an order?
                            </button>
                        </h3>
                        <div id="faqCollapseOne" class="accordion-collapse collapse" aria-labelledby="faqHeadingOne" data-bs-parent="#contactFaqAccordion">
                            <div class="accordion-body text-muted small pt-0 pb-3">
                                Browse our menu, select your favorite dishes, customize options if desired, add them to your cart, and proceed through our simple checkout.
                            </div>
                        </div>
                    </div>

                    <!-- Question 2 -->
                    <div class="accordion-item border-0 mb-3 rounded shadow-sm overflow-hidden">
                        <h3 class="accordion-header" id="faqHeadingTwo">
                            <button class="accordion-button fw-bold text-dark collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseTwo" aria-expanded="false" aria-controls="faqCollapseTwo">
                                2. How can I track my order?
                            </button>
                        </h3>
                        <div id="faqCollapseTwo" class="accordion-collapse collapse" aria-labelledby="faqHeadingTwo" data-bs-parent="#contactFaqAccordion">
                            <div class="accordion-body text-muted small pt-0 pb-3">
                                You can view real-time updates on your current orders by visiting the <a href="my-orders.php" class="text-primary-accent fw-bold text-decoration-none">My Orders</a> page or checking the Order Confirmation screen.
                            </div>
                        </div>
                    </div>

                    <!-- Question 3 -->
                    <div class="accordion-item border-0 mb-3 rounded shadow-sm overflow-hidden">
                        <h3 class="accordion-header" id="faqHeadingThree">
                            <button class="accordion-button fw-bold text-dark collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseThree" aria-expanded="false" aria-controls="faqCollapseThree">
                                3. What payment methods do you accept?
                            </button>
                        </h3>
                        <div id="faqCollapseThree" class="accordion-collapse collapse" aria-labelledby="faqHeadingThree" data-bs-parent="#contactFaqAccordion">
                            <div class="accordion-body text-muted small pt-0 pb-3">
                                We accept Cash on Delivery (COD) as well as popular mobile money payments like Zaad and Sahal for maximum convenience.
                            </div>
                        </div>
                    </div>

                    <!-- Question 4 -->
                    <div class="accordion-item border-0 mb-3 rounded shadow-sm overflow-hidden">
                        <h3 class="accordion-header" id="faqHeadingFour">
                            <button class="accordion-button fw-bold text-dark collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseFour" aria-expanded="false" aria-controls="faqCollapseFour">
                                4. Can I cancel my order?
                            </button>
                        </h3>
                        <div id="faqCollapseFour" class="accordion-collapse collapse" aria-labelledby="faqHeadingFour" data-bs-parent="#contactFaqAccordion">
                            <div class="accordion-body text-muted small pt-0 pb-3">
                                Orders can be modified or cancelled before the kitchen begins preparing your food. Contact our support line immediately at <a href="tel:+252907619928" class="text-primary-accent fw-bold text-decoration-none">+252 90 761 9928</a> if you need changes.
                            </div>
                        </div>
                    </div>

                    <!-- Question 5 -->
                    <div class="accordion-item border-0 mb-3 rounded shadow-sm overflow-hidden">
                        <h3 class="accordion-header" id="faqHeadingFive">
                            <button class="accordion-button fw-bold text-dark collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseFive" aria-expanded="false" aria-controls="faqCollapseFive">
                                5. How can I contact customer support?
                            </button>
                        </h3>
                        <div id="faqCollapseFive" class="accordion-collapse collapse" aria-labelledby="faqHeadingFive" data-bs-parent="#contactFaqAccordion">
                            <div class="accordion-body text-muted small pt-0 pb-3">
                                You can reach our friendly support team via phone at <a href="tel:+252907619928" class="text-primary-accent fw-bold text-decoration-none">+252 90 761 9928</a>, email at <a href="mailto:yaasmiin378@gmail.com" class="text-primary-accent fw-bold text-decoration-none">yaasmiin378@gmail.com</a>, or using the contact form on this page.
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>

    </main>

    <!-- Main Footer Component -->
    <?php include_once __DIR__ . '/includes/footer.php'; ?>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
</body>
</html>
