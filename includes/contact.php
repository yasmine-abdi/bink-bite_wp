<?php
/**
 * FastBite - Contact Us Section Component
 * Step 10: Contact Us Section
 * Design Identity: Pink & White
 */

$restPhone = function_exists('dbGetSetting') ? dbGetSetting('restaurant_phone', '+252 90 761 9928') : '+252 90 761 9928';

$contactDetails = [
    [
        'id' => 'contact-location',
        'icon' => 'bi bi-geo-alt',
        'title' => 'Our Location',
        'line1' => 'FastBite Restaurant',
        'line2' => 'City Center, Bosaso'
    ],
    [
        'id' => 'contact-phone',
        'icon' => 'bi bi-telephone',
        'title' => 'Call Us',
        'line1' => '+252 90 761 9928',
        'line2' => 'Customer Support &amp; Orders'
    ],
    [
        'id' => 'contact-email',
        'icon' => 'bi bi-envelope',
        'title' => 'Email Us',
        'line1' => 'yaasmiin378@gmail.com',
        'line2' => 'Quick response within 24h'
    ],
    [
        'id' => 'contact-hours',
        'icon' => 'bi bi-clock',
        'title' => 'Opening Hours',
        'line1' => 'Every Day',
        'line2' => '10:00 AM — 11:00 PM'
    ]
];
?>
<section class="contact-section py-5" id="contact" aria-label="Contact FastBite">
    <div class="container py-2 py-lg-4">
        <!-- Section Header -->
        <div class="contact-header text-center mb-4 mb-lg-5">
            <span class="contact-eyebrow d-inline-block">GET IN TOUCH</span>
            <h2 class="contact-title fw-bold mb-2">Contact FastBite</h2>
            <p class="contact-desc text-muted mb-0">Have a question, suggestion, or just want to say hello? We'd love to hear from you.</p>
        </div>

        <div class="row g-4 g-lg-5 contact-wrapper align-items-stretch">
            <!-- Left Column: Contact Information Cards -->
            <div class="col-lg-5 contact-info-col">
                <div class="contact-info-list d-flex flex-column gap-3 h-100 justify-content-between">
                    <?php foreach ($contactDetails as $info): ?>
                        <div class="contact-info-item card p-3" id="<?= htmlspecialchars($info['id'], ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="d-flex align-items-start gap-3">
                                <div class="contact-info-icon flex-shrink-0">
                                    <i class="<?= htmlspecialchars($info['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>
                                </div>
                                <div class="contact-info-content">
                                    <h3 class="contact-info-title mb-1"><?= htmlspecialchars($info['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <?php if ($info['id'] === 'contact-phone'): ?>
                                        <p class="contact-info-text mb-0 fw-semibold text-dark">
                                            <a href="tel:+252907619928" class="text-decoration-none text-dark hover-pink"><?= $info['line1']; ?></a>
                                        </p>
                                    <?php else: ?>
                                        <p class="contact-info-text mb-0 fw-semibold text-dark"><?= $info['line1']; ?></p>
                                    <?php endif; ?>
                                    <p class="contact-info-subtext mb-0 text-muted small"><?= $info['line2']; ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Right Column: Contact Form -->
            <div class="col-lg-7 contact-form-col">
                <div class="contact-form-wrapper card p-4 p-md-5 h-100">
                    <div class="alert alert-success alert-dismissible fade show d-none align-items-center gap-2 shadow-sm mb-3" id="homeContactSuccessAlert" role="alert">
                        <i class="bi bi-check-circle-fill fs-5 text-success"></i>
                        <div><strong>Mahadsanid!</strong> Fariintaada si guul leh ayaa loo diray. Kooxdayadu way kula soo xidhiidhi doonaan.</div>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <div class="alert alert-danger alert-dismissible fade show d-none align-items-center gap-2 shadow-sm mb-3" id="homeContactErrorAlert" role="alert">
                        <i class="bi bi-exclamation-triangle-fill fs-5 text-danger"></i>
                        <div id="homeContactErrorText">Fadlan buuxi meelaha loo baahan yahay (Magaca, Email-ka, iyo Fariinta).</div>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>

                    <form class="contact-form" id="homeContactForm" method="POST" action="contact.php" novalidate>
                        <?= function_exists('csrfField') ? csrfField() : ''; ?>
                        <div class="row g-3">
                            <!-- Full Name -->
                            <div class="col-12 col-md-6 contact-form-group">
                                <label for="contact_name" class="form-label contact-form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control contact-form-input" 
                                       id="contact_name" 
                                       name="fullName" 
                                       placeholder="Enter your full name" 
                                       required>
                            </div>

                            <!-- Email Address -->
                            <div class="col-12 col-md-6 contact-form-group">
                                <label for="contact_email" class="form-label contact-form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" 
                                       class="form-control contact-form-input" 
                                       id="contact_email" 
                                       name="email" 
                                       placeholder="Enter your email address" 
                                       required>
                            </div>

                            <!-- Phone Number -->
                            <div class="col-12 col-md-6 contact-form-group">
                                <label for="contact_phone" class="form-label contact-form-label">Phone Number <span class="text-muted fw-normal">(Optional)</span></label>
                                <input type="tel" 
                                       class="form-control contact-form-input" 
                                       id="contact_phone" 
                                       name="phone" 
                                       placeholder="Enter your phone number">
                            </div>

                            <!-- Subject -->
                            <div class="col-12 col-md-6 contact-form-group">
                                <label for="contact_subject" class="form-label contact-form-label">Subject <span class="text-muted fw-normal">(Optional)</span></label>
                                <input type="text" 
                                       class="form-control contact-form-input" 
                                       id="contact_subject" 
                                       name="subject" 
                                       placeholder="What is this about?">
                            </div>

                            <!-- Message -->
                            <div class="col-12 contact-form-group">
                                <label for="contact_message" class="form-label contact-form-label">Message <span class="text-danger">*</span></label>
                                <textarea class="form-control contact-form-textarea" 
                                          id="contact_message" 
                                          name="message" 
                                          rows="4" 
                                          placeholder="Write your message here..." 
                                          required></textarea>
                            </div>

                            <!-- Submit Button -->
                            <div class="col-12 pt-2">
                                <button type="submit" 
                                        class="btn btn-contact-submit w-100 d-inline-flex align-items-center justify-content-center gap-2"
                                        id="btnHomeContactSubmit">
                                    <span>Send Message</span>
                                    <i class="bi bi-send"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
