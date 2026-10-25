/**
 * FastBite / Bink Bite Fast Food - Main JavaScript
 * Step 2: Navbar • Step 11: Menu • Step 12: Details • Step 13: Cart • Step 14: Checkout • Step 15: Auth • Step 16: Dashboard • Step 17: Confirmation & My Orders • Step 18: Order Details • Step 19: Profile & Settings • Step 20: Saved Addresses • Step 21: Notifications Center
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Navbar mobile auto-close on link click
    const navLinks = document.querySelectorAll('.navbar-links-list .nav-link');
    const navbarCollapse = document.getElementById('navbarMainContent');

    if (navbarCollapse && typeof bootstrap !== 'undefined') {
        const bsCollapse = bootstrap.Collapse.getOrCreateInstance(navbarCollapse, { toggle: false });
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 992 && navbarCollapse.classList.contains('show')) {
                    bsCollapse.hide();
                }
            });
        });
    }

    // 2. Menu Page Search & Category Filter System (Step 21 Functional Fix)
    const menuSearchInput = document.getElementById('menuSearchInput');
    const menuPills = document.querySelectorAll('.btn-menu-pill');
    const categoryGroups = document.querySelectorAll('.menu-category-group');
    const foodCols = document.querySelectorAll('.menu-food-col');
    const noResults = document.getElementById('menuNoResults');

    let currentActiveCategory = 'all';

    function normalizeCategoryKey(raw) {
        if (!raw) return 'all';
        let clean = raw.toLowerCase().trim().replace(/^#(cat-)?/, '').replace(/^cat-/, '');
        if (clean === 'fried-chicken') clean = 'chicken';
        if (clean === 'fries-sides') clean = 'fries';
        if (clean === '' || clean === 'all') return 'all';
        return clean;
    }

    function applyMenuFiltering(targetCategory, searchQuery = '') {
        const catKey = normalizeCategoryKey(targetCategory);
        currentActiveCategory = catKey;
        const query = (searchQuery || '').toLowerCase().trim();

        // 1. Update active pill state
        if (menuPills.length > 0) {
            menuPills.forEach(pill => {
                const pillCat = normalizeCategoryKey(pill.getAttribute('data-category'));
                if (pillCat === catKey) {
                    pill.classList.add('active');
                } else {
                    pill.classList.remove('active');
                }
            });
        }

        let totalVisibleFoods = 0;

        // 2. Filter category groups and items
        categoryGroups.forEach(group => {
            const groupCat = normalizeCategoryKey(group.getAttribute('data-category-group') || group.id);
            const matchesCategory = (catKey === 'all') || (groupCat === catKey);

            if (!matchesCategory) {
                group.classList.add('d-none');
                const groupFoods = group.querySelectorAll('.menu-food-col');
                groupFoods.forEach(f => f.classList.add('d-none'));
            } else {
                const groupFoods = group.querySelectorAll('.menu-food-col');
                let groupVisibleCount = 0;

                groupFoods.forEach(food => {
                    const name = (food.getAttribute('data-food-name') || '').toLowerCase();
                    const type = (food.getAttribute('data-food-type') || '').toLowerCase();
                    const matchesSearch = !query || name.includes(query) || type.includes(query);

                    if (matchesSearch) {
                        food.classList.remove('d-none');
                        groupVisibleCount++;
                        totalVisibleFoods++;
                    } else {
                        food.classList.add('d-none');
                    }
                });

                if (groupVisibleCount > 0) {
                    group.classList.remove('d-none');
                } else {
                    group.classList.add('d-none');
                }
            }
        });

        // 3. Handle No Results State
        if (noResults) {
            if (totalVisibleFoods === 0) {
                noResults.classList.remove('d-none');
            } else {
                noResults.classList.add('d-none');
            }
        }
    }

    // Hash sync helper
    function syncCategoryFromUrlHash() {
        if (categoryGroups.length === 0) return;
        const hash = window.location.hash;
        const targetCat = normalizeCategoryKey(hash);
        const query = menuSearchInput ? menuSearchInput.value : '';
        applyMenuFiltering(targetCat, query);
    }

    if (categoryGroups.length > 0) {
        // Initial load check
        syncCategoryFromUrlHash();

        // Listen to hash changes (back/forward navigation & direct hash changes)
        window.addEventListener('hashchange', () => {
            syncCategoryFromUrlHash();
        });

        // Pill click interactions
        if (menuPills.length > 0) {
            menuPills.forEach(pill => {
                pill.addEventListener('click', (e) => {
                    e.preventDefault();
                    const cat = normalizeCategoryKey(pill.getAttribute('data-category'));
                    
                    if (cat === 'all') {
                        if (window.location.hash) {
                            history.pushState(null, '', window.location.pathname + window.location.search);
                        }
                    } else {
                        window.location.hash = `cat-${cat}`;
                    }

                    const query = menuSearchInput ? menuSearchInput.value : '';
                    applyMenuFiltering(cat, query);
                });
            });
        }

        // Search input real-time filtering within active category
        if (menuSearchInput) {
            menuSearchInput.addEventListener('input', (e) => {
                const query = e.target.value;
                applyMenuFiltering(currentActiveCategory, query);
            });
        }
    }

    // 3. Food Details Page Interactions (Frontend Only)
    const btnQtyMinus = document.getElementById('btnQtyMinus');
    const btnQtyPlus = document.getElementById('btnQtyPlus');
    const qtyDisplay = document.getElementById('qtyDisplay');
    const optionPills = document.querySelectorAll('.btn-option-pill');
    const btnAddToCartDemo = document.getElementById('btnAddToCartDemo');
    const btnAddToCartText = document.getElementById('btnAddToCartText');

    let currentQty = 1;

    if (btnQtyMinus && btnQtyPlus && qtyDisplay) {
        btnQtyMinus.addEventListener('click', () => {
            if (currentQty > 1) {
                currentQty--;
                qtyDisplay.textContent = currentQty;
            }
        });

        btnQtyPlus.addEventListener('click', () => {
            currentQty++;
            qtyDisplay.textContent = currentQty;
        });
    }

    if (optionPills.length > 0) {
        optionPills.forEach(pill => {
            pill.addEventListener('click', () => {
                const group = pill.getAttribute('data-option-group');
                const siblingPills = document.querySelectorAll(`.btn-option-pill[data-option-group="${group}"]`);
                siblingPills.forEach(p => p.classList.remove('active'));
                pill.classList.add('active');
            });
        });
    }

    if (btnAddToCartDemo && btnAddToCartText) {
        btnAddToCartDemo.addEventListener('click', () => {
            const originalText = btnAddToCartText.textContent;
            btnAddToCartText.textContent = 'Added to Cart!';
            btnAddToCartDemo.classList.add('btn-success');
            btnAddToCartDemo.classList.remove('btn-details-cart');

            setTimeout(() => {
                btnAddToCartText.textContent = originalText;
                btnAddToCartDemo.classList.remove('btn-success');
                btnAddToCartDemo.classList.add('btn-details-cart');
            }, 1800);
        });
    }

    // 4. Cart Page Interactions (Frontend Only)
    const cartCards = document.querySelectorAll('.cart-item-card');
    const summarySubtotal = document.getElementById('summarySubtotal');
    const summaryTotal = document.getElementById('summaryTotal');
    const activeCartContainer = document.getElementById('activeCartContainer');
    const emptyCartState = document.getElementById('emptyCartState');
    const navCartCount = document.getElementById('nav-cart-count');

    const deliveryFeeVal = 2.00;

    function recalculateCart() {
        const remainingCards = document.querySelectorAll('.cart-item-card');
        let newSubtotal = 0;
        let totalItems = 0;

        remainingCards.forEach(card => {
            const price = parseFloat(card.getAttribute('data-price')) || 0;
            const qty = parseInt(card.getAttribute('data-qty')) || 0;
            const itemSubtotal = price * qty;
            newSubtotal += itemSubtotal;
            totalItems += qty;

            const itemSubtotalSpan = card.querySelector('.item-subtotal-val');
            if (itemSubtotalSpan) {
                itemSubtotalSpan.textContent = itemSubtotal.toFixed(2);
            }
        });

        if (summarySubtotal) {
            summarySubtotal.textContent = newSubtotal.toFixed(2);
        }

        if (summaryTotal) {
            const newTotal = (newSubtotal > 0) ? (newSubtotal + deliveryFeeVal) : 0;
            summaryTotal.textContent = newTotal.toFixed(2);
        }

        if (navCartCount) {
            navCartCount.textContent = totalItems;
        }

        if (remainingCards.length === 0) {
            if (activeCartContainer) activeCartContainer.classList.add('d-none');
            if (emptyCartState) emptyCartState.classList.remove('d-none');
            if (navCartCount) navCartCount.textContent = '0';
        }
    }

    if (cartCards.length > 0) {
        cartCards.forEach(card => {
            const btnMinus = card.querySelector('.btn-cart-minus');
            const btnPlus = card.querySelector('.btn-cart-plus');
            const qtyVal = card.querySelector('.cart-qty-val');
            const btnRemove = card.querySelector('.btn-cart-remove');

            if (btnMinus && btnPlus && qtyVal) {
                btnMinus.addEventListener('click', () => {
                    let current = parseInt(card.getAttribute('data-qty')) || 1;
                    if (current > 1) {
                        current--;
                        card.setAttribute('data-qty', current);
                        qtyVal.textContent = current;
                        recalculateCart();
                    }
                });

                btnPlus.addEventListener('click', () => {
                    let current = parseInt(card.getAttribute('data-qty')) || 1;
                    current++;
                    card.setAttribute('data-qty', current);
                    qtyVal.textContent = current;
                    recalculateCart();
                });
            }

            if (btnRemove) {
                btnRemove.addEventListener('click', () => {
                    card.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        card.remove();
                        recalculateCart();
                    }, 250);
                });
            }
        });
    }

    // 5. Checkout Page Interactions (Frontend Only)
    const deliveryTimeRadios = document.querySelectorAll('input[name="deliveryTime"]');
    const paymentMethodRadios = document.querySelectorAll('input[name="paymentMethod"]');
    const checkoutForm = document.getElementById('checkoutForm');
    const demoOrderNotice = document.getElementById('demoOrderNotice');

    if (deliveryTimeRadios.length > 0) {
        deliveryTimeRadios.forEach(radio => {
            radio.addEventListener('change', () => {
                document.querySelectorAll('.delivery-time-card').forEach(card => card.classList.remove('active'));
                const parentLabel = radio.closest('.delivery-time-card');
                if (parentLabel) parentLabel.classList.add('active');
            });
        });
    }

    if (paymentMethodRadios.length > 0) {
        paymentMethodRadios.forEach(radio => {
            radio.addEventListener('change', () => {
                document.querySelectorAll('.payment-method-card').forEach(card => card.classList.remove('active'));
                const parentLabel = radio.closest('.payment-method-card');
                if (parentLabel) parentLabel.classList.add('active');
            });
        });
    }

    if (checkoutForm) {
        checkoutForm.addEventListener('submit', (e) => {
            e.preventDefault();

            if (!checkoutForm.checkValidity()) {
                e.stopPropagation();
                checkoutForm.classList.add('was-validated');
                return;
            }

            checkoutForm.classList.add('was-validated');

            if (demoOrderNotice) {
                demoOrderNotice.classList.remove('d-none');
            }

            // Smooth transition to confirmation.php in frontend demo
            setTimeout(() => {
                window.location.href = 'confirmation.php';
            }, 300);
        });
    }

    // 6. Authentication Pages Interactions (Frontend Only)
    const pwToggleButtons = document.querySelectorAll('.auth-pw-toggle');
    if (pwToggleButtons.length > 0) {
        pwToggleButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const group = btn.closest('.input-group');
                if (group) {
                    const input = group.querySelector('.password-field');
                    const icon = btn.querySelector('i');
                    if (input && icon) {
                        if (input.type === 'password') {
                            input.type = 'text';
                            icon.classList.remove('bi-eye');
                            icon.classList.add('bi-eye-slash');
                        } else {
                            input.type = 'password';
                            icon.classList.remove('bi-eye-slash');
                            icon.classList.add('bi-eye');
                        }
                    }
                }
            });
        });
    }

    const loginForm = document.getElementById('loginForm');
    const loginDemoNotice = document.getElementById('loginDemoNotice');
    if (loginForm) {
        loginForm.addEventListener('submit', (e) => {
            e.preventDefault();

            if (!loginForm.checkValidity()) {
                e.stopPropagation();
                loginForm.classList.add('was-validated');
                return;
            }

            loginForm.classList.add('was-validated');

            if (loginDemoNotice) {
                loginDemoNotice.classList.remove('d-none');
                setTimeout(() => {
                    loginDemoNotice.classList.add('d-none');
                }, 4000);
            }
        });
    }

    const registerForm = document.getElementById('registerForm');
    const registerDemoNotice = document.getElementById('registerDemoNotice');
    const regPassword = document.getElementById('regPassword');
    const regConfirmPassword = document.getElementById('regConfirmPassword');

    if (registerForm) {
        registerForm.addEventListener('submit', (e) => {
            e.preventDefault();

            let isValid = true;

            if (regPassword && regPassword.value.length < 8) {
                regPassword.setCustomValidity('Password must be at least 8 characters.');
                isValid = false;
            } else if (regPassword) {
                regPassword.setCustomValidity('');
            }

            if (regPassword && regConfirmPassword) {
                if (regPassword.value !== regConfirmPassword.value) {
                    regConfirmPassword.setCustomValidity('Passwords do not match.');
                    isValid = false;
                } else {
                    regConfirmPassword.setCustomValidity('');
                }
            }

            if (!registerForm.checkValidity() || !isValid) {
                e.stopPropagation();
                registerForm.classList.add('was-validated');
                return;
            }

            registerForm.classList.add('was-validated');

            if (registerDemoNotice) {
                registerDemoNotice.classList.remove('d-none');
                registerDemoNotice.scrollIntoView({ behavior: 'smooth', block: 'center' });
                setTimeout(() => {
                    registerDemoNotice.classList.add('d-none');
                }, 4000);
            }
        });

        if (regPassword && regConfirmPassword) {
            regConfirmPassword.addEventListener('input', () => {
                if (regPassword.value !== regConfirmPassword.value) {
                    regConfirmPassword.setCustomValidity('Passwords do not match.');
                } else {
                    regConfirmPassword.setCustomValidity('');
                }
            });
        }
    }

    const forgotForm = document.getElementById('forgotForm');
    if (forgotForm) {
        forgotForm.addEventListener('submit', (e) => {
            const emailInput = document.getElementById('forgotEmail');
            const submitBtn = document.getElementById('btnForgotSubmit');

            if (!emailInput || !emailInput.value.trim() || !forgotForm.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                forgotForm.classList.add('was-validated');
                return;
            }

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Generating Link...';
            }
        });
    }

    // 7. Customer Dashboard Interactions (Frontend Only)
    const dashboardNavLinks = document.querySelectorAll('.dashboard-sidebar-nav .dashboard-nav-link');
    if (dashboardNavLinks.length > 0) {
        dashboardNavLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                if (link.getAttribute('href').startsWith('#')) {
                    dashboardNavLinks.forEach(l => l.classList.remove('active'));
                    link.classList.add('active');
                }
            });
        });
    }

    const deleteAddrButtons = document.querySelectorAll('.btn-addr-delete');
    if (deleteAddrButtons.length > 0) {
        deleteAddrButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const cardCol = btn.closest('.address-card-item, .col-12, .col-md-6');
                if (cardCol) {
                    cardCol.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
                    cardCol.style.opacity = '0';
                    cardCol.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        cardCol.remove();
                    }, 250);
                }
            });
        });
    }

    // 8. Order Confirmation Page Interactions (Frontend Only)
    const btnCopyOrderNum = document.getElementById('btnCopyOrderNum');
    const copyBtnText = document.getElementById('copyBtnText');
    const orderNumberValue = document.getElementById('orderNumberValue');

    if (btnCopyOrderNum && copyBtnText) {
        btnCopyOrderNum.addEventListener('click', () => {
            const orderText = orderNumberValue ? orderNumberValue.textContent.trim() : '#FB-1025';
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(orderText).catch(() => {});
            }
            const origText = copyBtnText.textContent;
            copyBtnText.textContent = 'Copied!';
            btnCopyOrderNum.classList.add('btn-success');
            btnCopyOrderNum.classList.remove('btn-outline-pink');

            setTimeout(() => {
                copyBtnText.textContent = origText;
                btnCopyOrderNum.classList.remove('btn-success');
                btnCopyOrderNum.classList.add('btn-outline-pink');
            }, 2000);
        });
    }

    // 9. My Orders Page Interactions (Frontend Only)
    const orderSearchInput = document.getElementById('orderSearchInput');
    const orderStatusFilter = document.getElementById('orderStatusFilter');
    const orderDateFilter = document.getElementById('orderDateFilter');
    const btnClearOrderFilters = document.getElementById('btnClearOrderFilters');
    const btnResetEmptyFilters = document.getElementById('btnResetEmptyFilters');
    const orderCards = document.querySelectorAll('.order-history-card');
    const noOrdersFoundState = document.getElementById('noOrdersFoundState');
    const ordersCountDisplay = document.getElementById('ordersCountDisplay');
    const reorderDemoToast = document.getElementById('reorderDemoToast');

    function applyOrderFilters() {
        if (!orderCards.length) return;

        const searchQuery = (orderSearchInput ? orderSearchInput.value : '').toLowerCase().trim();
        const selectedStatus = orderStatusFilter ? orderStatusFilter.value : 'all';
        const selectedDate = orderDateFilter ? orderDateFilter.value : 'all';

        let visibleCount = 0;

        orderCards.forEach(card => {
            const orderId = (card.getAttribute('data-order-id') || '').toLowerCase();
            const foodNames = (card.getAttribute('data-food-names') || '').toLowerCase();
            const status = card.getAttribute('data-status') || '';
            const datePeriod = card.getAttribute('data-date-period') || '';

            const matchesSearch = !searchQuery || orderId.includes(searchQuery) || foodNames.includes(searchQuery);
            const matchesStatus = (selectedStatus === 'all') || (status === selectedStatus);
            const matchesDate = (selectedDate === 'all') || (datePeriod === selectedDate);

            if (matchesSearch && matchesStatus && matchesDate) {
                card.classList.remove('d-none');
                visibleCount++;
            } else {
                card.classList.add('d-none');
            }
        });

        if (ordersCountDisplay) {
            ordersCountDisplay.textContent = `Showing ${visibleCount} order${visibleCount === 1 ? '' : 's'}`;
        }

        if (noOrdersFoundState) {
            if (visibleCount === 0) {
                noOrdersFoundState.classList.remove('d-none');
            } else {
                noOrdersFoundState.classList.add('d-none');
            }
        }
    }

    function resetOrderFilters() {
        if (orderSearchInput) orderSearchInput.value = '';
        if (orderStatusFilter) orderStatusFilter.value = 'all';
        if (orderDateFilter) orderDateFilter.value = 'all';
        applyOrderFilters();
    }

    if (orderSearchInput) {
        orderSearchInput.addEventListener('input', applyOrderFilters);
    }

    if (orderStatusFilter) {
        orderStatusFilter.addEventListener('change', applyOrderFilters);
    }

    if (orderDateFilter) {
        orderDateFilter.addEventListener('change', applyOrderFilters);
    }

    if (btnClearOrderFilters) {
        btnClearOrderFilters.addEventListener('click', resetOrderFilters);
    }

    if (btnResetEmptyFilters) {
        btnResetEmptyFilters.addEventListener('click', resetOrderFilters);
    }

    // Reorder Buttons Demo Toast
    const reorderButtons = document.querySelectorAll('.btn-reorder-demo');
    if (reorderButtons.length > 0 && reorderDemoToast) {
        reorderButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                reorderDemoToast.classList.remove('d-none');
                reorderDemoToast.scrollIntoView({ behavior: 'smooth', block: 'center' });
                setTimeout(() => {
                    reorderDemoToast.classList.add('d-none');
                }, 3500);
            });
        });
    }

    // View Details Modal Dynamic Data Population
    const orderDetailsModal = document.getElementById('orderDetailsModal');
    if (orderDetailsModal) {
        const modalLabel = document.getElementById('orderDetailsModalLabel');
        const modalOrderDate = document.getElementById('modalOrderDate');
        const modalOrderStatus = document.getElementById('modalOrderStatus');
        const modalOrderAddress = document.getElementById('modalOrderAddress');
        const modalOrderPayment = document.getElementById('modalOrderPayment');
        const modalSubtotal = document.getElementById('modalSubtotal');
        const modalDeliveryFee = document.getElementById('modalDeliveryFee');
        const modalTotal = document.getElementById('modalTotal');
        const modalItemsList = document.getElementById('modalItemsList');
        const modalTimelineArea = document.getElementById('modalTimelineArea');
        const modalTimelineTrack = document.getElementById('modalTimelineTrack');
        const modalCancelledNotice = document.getElementById('modalCancelledNotice');

        const viewDetailBtns = document.querySelectorAll('.btn-view-order-details');
        viewDetailBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const orderId = btn.getAttribute('data-order-id') || '#FB-1024';
                const orderDate = btn.getAttribute('data-order-date') || '';
                const orderStatus = btn.getAttribute('data-order-status') || '';
                const statusCode = btn.getAttribute('data-status-code') || '';
                const orderTotal = btn.getAttribute('data-order-total') || '$0.00';
                const orderSubtotal = btn.getAttribute('data-order-subtotal') || '$0.00';
                const orderDelivery = btn.getAttribute('data-order-delivery') || '$2.00';
                const orderAddress = btn.getAttribute('data-order-address') || 'Main Street, City Center';
                const orderPayment = btn.getAttribute('data-order-payment') || 'Cash on Delivery';

                let rawItems = btn.getAttribute('data-order-items');
                let items = [];
                try {
                    items = rawItems ? JSON.parse(rawItems) : [];
                } catch (err) {
                    items = [];
                }

                if (modalLabel) modalLabel.textContent = `Order ${orderId}`;
                if (modalOrderDate) modalOrderDate.textContent = orderDate;
                if (modalOrderStatus) {
                    modalOrderStatus.textContent = orderStatus;
                    if (statusCode === 'delivered') modalOrderStatus.className = 'small fw-bold text-success';
                    else if (statusCode === 'preparing') modalOrderStatus.className = 'small fw-bold text-warning';
                    else if (statusCode === 'out_for_delivery') modalOrderStatus.className = 'small fw-bold text-primary';
                    else if (statusCode === 'cancelled') modalOrderStatus.className = 'small fw-bold text-danger';
                }
                if (modalOrderAddress) modalOrderAddress.textContent = orderAddress;
                if (modalOrderPayment) modalOrderPayment.textContent = orderPayment;
                if (modalSubtotal) modalSubtotal.textContent = orderSubtotal;
                if (modalDeliveryFee) modalDeliveryFee.textContent = orderDelivery;
                if (modalTotal) modalTotal.textContent = orderTotal;

                // Build Items HTML
                if (modalItemsList) {
                    modalItemsList.innerHTML = items.map(it => `
                        <div class="d-flex align-items-center justify-content-between gap-2 py-1">
                            <div class="d-flex align-items-center gap-2">
                                <div class="order-item-thumb-box flex-shrink-0">
                                    <img src="${it.image || 'assets/images/placeholders/food-placeholder.jpg'}" alt="${it.alt || it.name}" class="order-item-thumb">
                                </div>
                                <span class="small fw-semibold text-dark">${it.name} <span class="text-muted fw-normal">&times; ${it.qty}</span></span>
                            </div>
                            <span class="small fw-bold text-dark">$${((it.price || 0) * (it.qty || 1)).toFixed(2)}</span>
                        </div>
                    `).join('');
                }

                // Build Timeline HTML
                if (statusCode === 'cancelled') {
                    if (modalTimelineArea) modalTimelineArea.classList.add('d-none');
                    if (modalCancelledNotice) modalCancelledNotice.classList.remove('d-none');
                } else {
                    if (modalTimelineArea) modalTimelineArea.classList.remove('d-none');
                    if (modalCancelledNotice) modalCancelledNotice.classList.add('d-none');

                    let step1Class = 'step-completed';
                    let step2Class = (statusCode === 'preparing') ? 'step-active' : ((statusCode === 'out_for_delivery' || statusCode === 'delivered') ? 'step-completed' : 'step-pending');
                    let step3Class = (statusCode === 'out_for_delivery') ? 'step-active' : ((statusCode === 'delivered') ? 'step-completed' : 'step-pending');
                    let step4Class = (statusCode === 'delivered') ? 'step-completed' : 'step-pending';

                    if (modalTimelineTrack) {
                        modalTimelineTrack.innerHTML = `
                            <div class="timeline-step ${step1Class} text-center">
                                <div class="timeline-dot mb-1 mx-auto"><i class="bi bi-check2"></i></div>
                                <span class="d-block extra-small fw-bold">Placed</span>
                            </div>
                            <div class="timeline-step ${step2Class} text-center">
                                <div class="timeline-dot mb-1 mx-auto"><i class="bi bi-hourglass-split"></i></div>
                                <span class="d-block extra-small fw-bold">Preparing</span>
                            </div>
                            <div class="timeline-step ${step3Class} text-center">
                                <div class="timeline-dot mb-1 mx-auto"><i class="bi bi-truck"></i></div>
                                <span class="d-block extra-small fw-bold">Out for Delivery</span>
                            </div>
                            <div class="timeline-step ${step4Class} text-center">
                                <div class="timeline-dot mb-1 mx-auto"><i class="bi bi-house-door"></i></div>
                                <span class="d-block extra-small fw-bold">Delivered</span>
                            </div>
                        `;
                    }
                }
            });
        });
    }

    // Demo Pagination Click Switching
    const paginationLinks = document.querySelectorAll('.orders-pagination-nav .page-link:not(.notif-page-link)');
    if (paginationLinks.length > 0) {
        paginationLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const parentLi = link.closest('.page-item');
                if (parentLi && !parentLi.classList.contains('disabled') && !isNaN(parseInt(link.textContent.trim()))) {
                    document.querySelectorAll('.orders-pagination-nav .page-item').forEach(li => li.classList.remove('active'));
                    parentLi.classList.add('active');
                }
            });
        });
    }

    // 10. Customer Profile & Account Settings Interactions (Frontend Only)
    const profileGlobalToast = document.getElementById('profileGlobalToast');
    const profileToastText = document.getElementById('profileToastText');
    let profileToastTimeout = null;

    function showProfileToast(message, isSuccess = true) {
        if (!profileGlobalToast || !profileToastText) return;

        profileToastText.textContent = message;
        profileGlobalToast.className = `alert ${isSuccess ? 'alert-success' : 'alert-danger'} py-2 px-3 mb-4 small text-center shadow-sm`;
        profileGlobalToast.classList.remove('d-none');
        profileGlobalToast.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        if (profileToastTimeout) clearTimeout(profileToastTimeout);
        profileToastTimeout = setTimeout(() => {
            profileGlobalToast.classList.add('d-none');
        }, 3500);
    }

    // Profile Info Form Demo Submission
    const profileInfoForm = document.getElementById('profileInfoForm');
    const profFullName = document.getElementById('profFullName');
    const profEmail = document.getElementById('profEmail');
    const profileHeaderName = document.getElementById('profileHeaderName');
    const sidebarUserName = document.getElementById('sidebarUserName');
    const profileHeaderEmail = document.getElementById('profileHeaderEmail');
    const sidebarUserEmail = document.getElementById('sidebarUserEmail');

    if (profileInfoForm) {
        profileInfoForm.addEventListener('submit', (e) => {
            e.preventDefault();

            if (!profileInfoForm.checkValidity()) {
                e.stopPropagation();
                profileInfoForm.classList.add('was-validated');
                return;
            }

            profileInfoForm.classList.add('was-validated');

            if (profFullName && profileHeaderName) profileHeaderName.textContent = profFullName.value;
            if (profFullName && sidebarUserName) sidebarUserName.textContent = profFullName.value;
            if (profEmail && profileHeaderEmail) profileHeaderEmail.textContent = profEmail.value;
            if (profEmail && sidebarUserEmail) sidebarUserEmail.textContent = profEmail.value;

            showProfileToast("Profile changes saved successfully. This is currently a demo.");
        });
    }

    // Change Photo Demo
    const btnChangePhotoDemo = document.getElementById('btnChangePhotoDemo');
    const avatarFileInput = document.getElementById('avatarFileInput');

    if (btnChangePhotoDemo && avatarFileInput) {
        btnChangePhotoDemo.addEventListener('click', () => {
            avatarFileInput.click();
        });

        avatarFileInput.addEventListener('change', () => {
            showProfileToast("Photo updated successfully — demo only.");
        });
    }

    // Password Strength Detection & Confirm Password Validation
    const newPasswordInput = document.getElementById('newPassword');
    const confirmNewPasswordInput = document.getElementById('confirmNewPassword');
    const passwordStrengthLabel = document.getElementById('passwordStrengthLabel');
    const passwordStrengthBar = document.getElementById('passwordStrengthBar');
    const profilePasswordForm = document.getElementById('profilePasswordForm');

    function calculatePasswordStrength(pass) {
        if (!pass) return { score: 0, label: 'None', color: 'bg-danger', width: '0%' };

        let score = 0;
        if (pass.length >= 8) score++;
        if (/[A-Z]/.test(pass)) score++;
        if (/[a-z]/.test(pass)) score++;
        if (/[0-9]/.test(pass)) score++;
        if (/[^A-Za-z0-9]/.test(pass)) score++;

        if (score <= 2) {
            return { score, label: 'Weak', color: 'bg-danger', width: '33%' };
        } else if (score <= 4) {
            return { score, label: 'Medium', color: 'bg-warning', width: '66%' };
        } else {
            return { score, label: 'Strong', color: 'bg-success', width: '100%' };
        }
    }

    if (newPasswordInput && passwordStrengthLabel && passwordStrengthBar) {
        newPasswordInput.addEventListener('input', () => {
            const result = calculatePasswordStrength(newPasswordInput.value);
            passwordStrengthLabel.textContent = result.label;
            passwordStrengthBar.style.width = result.width;
            passwordStrengthBar.className = `progress-bar ${result.color}`;

            if (confirmNewPasswordInput && confirmNewPasswordInput.value) {
                if (confirmNewPasswordInput.value !== newPasswordInput.value) {
                    confirmNewPasswordInput.setCustomValidity('Passwords do not match.');
                } else {
                    confirmNewPasswordInput.setCustomValidity('');
                }
            }
        });
    }

    if (confirmNewPasswordInput && newPasswordInput) {
        confirmNewPasswordInput.addEventListener('input', () => {
            if (confirmNewPasswordInput.value !== newPasswordInput.value) {
                confirmNewPasswordInput.setCustomValidity('Passwords do not match.');
            } else {
                confirmNewPasswordInput.setCustomValidity('');
            }
        });
    }

    if (profilePasswordForm) {
        profilePasswordForm.addEventListener('submit', (e) => {
            e.preventDefault();

            let isValid = true;
            if (newPasswordInput && newPasswordInput.value.length < 8) {
                newPasswordInput.setCustomValidity('Password must be at least 8 characters.');
                isValid = false;
            } else if (newPasswordInput) {
                newPasswordInput.setCustomValidity('');
            }

            if (newPasswordInput && confirmNewPasswordInput) {
                if (newPasswordInput.value !== confirmNewPasswordInput.value) {
                    confirmNewPasswordInput.setCustomValidity('Passwords do not match.');
                    isValid = false;
                } else {
                    confirmNewPasswordInput.setCustomValidity('');
                }
            }

            if (!profilePasswordForm.checkValidity() || !isValid) {
                e.stopPropagation();
                profilePasswordForm.classList.add('was-validated');
                return;
            }

            profilePasswordForm.classList.add('was-validated');
            showProfileToast("Password updated successfully. This is a frontend demonstration.");
            profilePasswordForm.reset();
            profilePasswordForm.classList.remove('was-validated');

            if (passwordStrengthLabel && passwordStrengthBar) {
                passwordStrengthLabel.textContent = 'None';
                passwordStrengthBar.style.width = '0%';
            }
        });
    }

    // Notification Preferences & Account Preferences Toggle Feedback
    const prefToggles = document.querySelectorAll('.pref-toggle');
    if (prefToggles.length > 0) {
        prefToggles.forEach(toggle => {
            toggle.addEventListener('change', () => {
                const label = toggle.closest('div')?.parentElement?.querySelector('label')?.textContent || 'Preference';
                showProfileToast(`${label} updated.`);
            });
        });
    }

    // Add / Edit Address Demo Buttons
    const btnAddAddressModalBtn = document.getElementById('btnAddAddressModalBtn');
    const editAddressBtns = document.querySelectorAll('.btn-edit-address-demo');

    if (btnAddAddressModalBtn) {
        btnAddAddressModalBtn.addEventListener('click', () => {
            showProfileToast("Address added successfully — demo only.");
        });
    }

    if (editAddressBtns.length > 0) {
        editAddressBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const addr = btn.getAttribute('data-address') || 'Address';
                showProfileToast(`${addr} address updated — demo only.`);
            });
        });
    }

    // Danger Zone: Deactivate Account
    const btnConfirmDeactivate = document.getElementById('btnConfirmDeactivate');
    if (btnConfirmDeactivate) {
        btnConfirmDeactivate.addEventListener('click', () => {
            showProfileToast("Account deactivated (Demo). You can reactivate anytime.");
        });
    }

    // Danger Zone: Delete Account Requirement (Must type DELETE)
    const deleteConfirmInput = document.getElementById('deleteConfirmInput');
    const btnConfirmDeleteAccount = document.getElementById('btnConfirmDeleteAccount');

    if (deleteConfirmInput && btnConfirmDeleteAccount) {
        deleteConfirmInput.addEventListener('input', () => {
            if (deleteConfirmInput.value.trim() === 'DELETE') {
                btnConfirmDeleteAccount.disabled = false;
            } else {
                btnConfirmDeleteAccount.disabled = true;
            }
        });

        btnConfirmDeleteAccount.addEventListener('click', () => {
            deleteConfirmInput.value = '';
            btnConfirmDeleteAccount.disabled = true;
            showProfileToast("This is a frontend demonstration. No account was deleted.");
        });
    }

    // 11. Saved Addresses Page (addresses.php) Interactions (Frontend Only)
    const addressGlobalToast = document.getElementById('addressGlobalToast');
    const addressToastText = document.getElementById('addressToastText');
    let addressToastTimeout = null;

    function showAddressToast(message, isSuccess = true) {
        if (!addressGlobalToast || !addressToastText) return;

        addressToastText.textContent = message;
        addressGlobalToast.className = `alert ${isSuccess ? 'alert-success' : 'alert-danger'} py-2 px-3 mb-4 small text-center shadow-sm`;
        addressGlobalToast.classList.remove('d-none');
        addressGlobalToast.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        if (addressToastTimeout) clearTimeout(addressToastTimeout);
        addressToastTimeout = setTimeout(() => {
            addressGlobalToast.classList.add('d-none');
        }, 3500);
    }

    function updateAddressesCountUI() {
        const remainingCards = document.querySelectorAll('#addressesGridContainer .address-card-item');
        const summary = document.getElementById('addressCountSummary');
        const emptyState = document.getElementById('emptyAddressesState');

        if (summary) {
            summary.textContent = `You currently have ${remainingCards.length} saved delivery address${remainingCards.length === 1 ? '' : 'es'}.`;
        }

        if (emptyState) {
            if (remainingCards.length === 0) {
                emptyState.classList.remove('d-none');
            } else {
                emptyState.classList.add('d-none');
            }
        }
    }

    // Add Address Form Submission
    const addAddressForm = document.getElementById('addAddressForm');
    const addAddressModalEl = document.getElementById('addAddressModal');
    const addressesGridContainer = document.getElementById('addressesGridContainer');

    if (addAddressForm) {
        addAddressForm.addEventListener('submit', (e) => {
            e.preventDefault();

            if (!addAddressForm.checkValidity()) {
                e.stopPropagation();
                addAddressForm.classList.add('was-validated');
                return;
            }

            addAddressForm.classList.add('was-validated');

            const type = document.getElementById('addAddrType').value;
            const name = document.getElementById('addAddrName').value;
            const phone = document.getElementById('addAddrPhone').value;
            const city = document.getElementById('addAddrCity').value;
            const area = document.getElementById('addAddrArea').value;
            const street = document.getElementById('addAddrStreet').value;
            const instructions = document.getElementById('addAddrInstructions').value;
            const isDefault = document.getElementById('addAddrIsDefault').checked;

            const iconClass = (type === 'Home') ? 'bi-house-door' : ((type === 'Work') ? 'bi-briefcase' : 'bi-geo-alt');
            const newId = 'addr-' + Date.now();

            if (isDefault) {
                document.querySelectorAll('#addressesGridContainer .default-badge').forEach(b => b.classList.add('d-none'));
            }

            const newCardHtml = `
                <div class="col-12 col-md-6 address-card-item" id="card-${newId}">
                    <div class="dashboard-address-card p-3 p-md-4 rounded-3 border bg-white shadow-sm h-100 d-flex flex-column justify-content-between position-relative">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="address-icon-box flex-shrink-0">
                                        <i class="bi ${iconClass}" aria-hidden="true"></i>
                                    </div>
                                    <h3 class="h6 fw-bold text-dark mb-0 addr-card-title">${type}</h3>
                                </div>
                                <span class="badge ${isDefault ? 'bg-pink-soft text-primary-accent border border-pink-subtle' : 'd-none'} default-badge extra-small">
                                    <i class="bi bi-star-fill me-1"></i>Default
                                </span>
                            </div>
                            <div class="address-details-body mb-3">
                                <p class="text-dark fw-bold small mb-1 addr-card-name">${name}</p>
                                <p class="text-muted extra-small mb-1 addr-card-street">
                                    <i class="bi bi-geo-alt me-1 text-primary-accent" aria-hidden="true"></i>
                                    <span>${street}</span>, 
                                    <span class="addr-card-area">${area}</span>, 
                                    <span class="addr-card-city">${city}</span>
                                </p>
                                <p class="text-muted extra-small mb-2 addr-card-phone">
                                    <i class="bi bi-telephone me-1 text-primary-accent" aria-hidden="true"></i>
                                    <span>${phone}</span>
                                </p>
                                ${instructions ? `
                                    <div class="p-2 rounded bg-light border extra-small text-muted addr-card-instructions">
                                        <i class="bi bi-chat-left-text me-1 text-primary-accent" aria-hidden="true"></i>
                                        <span>${instructions}</span>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pt-3 border-top mt-2">
                            <div>
                                ${!isDefault ? `
                                    <button type="button" class="btn btn-sm btn-outline-pink py-1 px-2 extra-small btn-set-default-addr" data-id="${newId}" title="Set this as default delivery address">
                                        <i class="bi bi-star me-1" aria-hidden="true"></i>Set Default
                                    </button>
                                ` : ''}
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 extra-small btn-open-edit-address" 
                                        data-id="${newId}"
                                        data-type="${type}"
                                        data-name="${name}"
                                        data-phone="${phone}"
                                        data-city="${city}"
                                        data-area="${area}"
                                        data-street="${street}"
                                        data-instructions="${instructions}"
                                        data-default="${isDefault ? 'true' : 'false'}"
                                        title="Edit address details">
                                    <i class="bi bi-pencil me-1" aria-hidden="true"></i>Edit
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2 extra-small btn-open-delete-address" data-id="${newId}" data-title="${type}" title="Delete this address">
                                    <i class="bi bi-trash me-1" aria-hidden="true"></i>Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            if (addressesGridContainer) {
                addressesGridContainer.insertAdjacentHTML('beforeend', newCardHtml);
            }

            if (addAddressModalEl && typeof bootstrap !== 'undefined') {
                const modalInstance = bootstrap.Modal.getInstance(addAddressModalEl);
                if (modalInstance) modalInstance.hide();
            }

            addAddressForm.reset();
            addAddressForm.classList.remove('was-validated');
            updateAddressesCountUI();
            showAddressToast("New delivery address added successfully (Demo).");
        });
    }

    // Edit Address Modal Dynamic Population & Submission
    const editAddressModalEl = document.getElementById('editAddressModal');
    const editAddressForm = document.getElementById('editAddressForm');

    document.addEventListener('click', (e) => {
        const editBtn = e.target.closest('.btn-open-edit-address');
        if (editBtn) {
            const addrId = editBtn.getAttribute('data-id');
            const type = editBtn.getAttribute('data-type') || 'Home';
            const name = editBtn.getAttribute('data-name') || '';
            const phone = editBtn.getAttribute('data-phone') || '';
            const city = editBtn.getAttribute('data-city') || 'Hargeisa';
            const area = editBtn.getAttribute('data-area') || '';
            const street = editBtn.getAttribute('data-street') || '';
            const instructions = editBtn.getAttribute('data-instructions') || '';
            const isDef = editBtn.getAttribute('data-default') === 'true';

            document.getElementById('editAddrId').value = addrId;
            document.getElementById('editAddrType').value = type;
            document.getElementById('editAddrName').value = name;
            document.getElementById('editAddrPhone').value = phone;
            document.getElementById('editAddrCity').value = city;
            document.getElementById('editAddrArea').value = area;
            document.getElementById('editAddrStreet').value = street;
            document.getElementById('editAddrInstructions').value = instructions;
            document.getElementById('editAddrIsDefault').checked = isDef;

            if (editAddressModalEl && typeof bootstrap !== 'undefined') {
                const bsModal = bootstrap.Modal.getOrCreateInstance(editAddressModalEl);
                bsModal.show();
            }
        }
    });

    if (editAddressForm) {
        editAddressForm.addEventListener('submit', (e) => {
            e.preventDefault();

            if (!editAddressForm.checkValidity()) {
                e.stopPropagation();
                editAddressForm.classList.add('was-validated');
                return;
            }

            editAddressForm.classList.add('was-validated');

            const id = document.getElementById('editAddrId').value;
            const type = document.getElementById('editAddrType').value;
            const name = document.getElementById('editAddrName').value;
            const phone = document.getElementById('editAddrPhone').value;
            const city = document.getElementById('editAddrCity').value;
            const area = document.getElementById('editAddrArea').value;
            const street = document.getElementById('editAddrStreet').value;
            const instructions = document.getElementById('editAddrInstructions').value;
            const isDefault = document.getElementById('editAddrIsDefault').checked;

            const targetCard = document.getElementById('card-' + id);
            if (targetCard) {
                const titleEl = targetCard.querySelector('.addr-card-title');
                const nameEl = targetCard.querySelector('.addr-card-name');
                const streetSpan = targetCard.querySelector('.addr-card-street span:first-of-type');
                const areaSpan = targetCard.querySelector('.addr-card-area');
                const citySpan = targetCard.querySelector('.addr-card-city');
                const phoneSpan = targetCard.querySelector('.addr-card-phone span');
                const instDiv = targetCard.querySelector('.addr-card-instructions span');
                const defaultBadge = targetCard.querySelector('.default-badge');
                const editBtn = targetCard.querySelector('.btn-open-edit-address');

                if (titleEl) titleEl.textContent = type;
                if (nameEl) nameEl.textContent = name;
                if (streetSpan) streetSpan.textContent = street;
                if (areaSpan) areaSpan.textContent = area;
                if (citySpan) citySpan.textContent = city;
                if (phoneSpan) phoneSpan.textContent = phone;
                if (instDiv) instDiv.textContent = instructions;

                if (editBtn) {
                    editBtn.setAttribute('data-type', type);
                    editBtn.setAttribute('data-name', name);
                    editBtn.setAttribute('data-phone', phone);
                    editBtn.setAttribute('data-city', city);
                    editBtn.setAttribute('data-area', area);
                    editBtn.setAttribute('data-street', street);
                    editBtn.setAttribute('data-instructions', instructions);
                    editBtn.setAttribute('data-default', isDefault ? 'true' : 'false');
                }

                if (isDefault) {
                    document.querySelectorAll('#addressesGridContainer .default-badge').forEach(b => b.classList.add('d-none'));
                    if (defaultBadge) defaultBadge.classList.remove('d-none');
                }
            }

            if (editAddressModalEl && typeof bootstrap !== 'undefined') {
                const modalInstance = bootstrap.Modal.getInstance(editAddressModalEl);
                if (modalInstance) modalInstance.hide();
            }

            editAddressForm.classList.remove('was-validated');
            showAddressToast("Delivery address updated successfully (Demo).");
        });
    }

    // Delete Address Modal Confirmation
    let addressToDeleteId = null;
    const deleteAddressModalEl = document.getElementById('deleteAddressModal');
    const deleteAddressTitleSpan = document.getElementById('deleteAddressTitleSpan');
    const btnConfirmDeleteAddress = document.getElementById('btnConfirmDeleteAddress');

    document.addEventListener('click', (e) => {
        const delBtn = e.target.closest('.btn-open-delete-address');
        if (delBtn) {
            addressToDeleteId = delBtn.getAttribute('data-id');
            const title = delBtn.getAttribute('data-title') || 'this address';
            if (deleteAddressTitleSpan) deleteAddressTitleSpan.textContent = `"${title}"`;

            if (deleteAddressModalEl && typeof bootstrap !== 'undefined') {
                const bsModal = bootstrap.Modal.getOrCreateInstance(deleteAddressModalEl);
                bsModal.show();
            }
        }
    });

    if (btnConfirmDeleteAddress) {
        btnConfirmDeleteAddress.addEventListener('click', () => {
            if (addressToDeleteId) {
                const card = document.getElementById('card-' + addressToDeleteId);
                if (card) {
                    card.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        card.remove();
                        updateAddressesCountUI();
                    }, 250);
                }
            }

            if (deleteAddressModalEl && typeof bootstrap !== 'undefined') {
                const modalInstance = bootstrap.Modal.getInstance(deleteAddressModalEl);
                if (modalInstance) modalInstance.hide();
            }

            showAddressToast("Address removed successfully (Demo).");
        });
    }

    // Set As Default Address Button
    document.addEventListener('click', (e) => {
        const setDefaultBtn = e.target.closest('.btn-set-default-addr');
        if (setDefaultBtn) {
            const card = setDefaultBtn.closest('.address-card-item');
            if (card) {
                document.querySelectorAll('#addressesGridContainer .default-badge').forEach(b => b.classList.add('d-none'));
                const badge = card.querySelector('.default-badge');
                if (badge) badge.classList.remove('d-none');
                setDefaultBtn.remove();
                showAddressToast("Default delivery address updated (Demo).");
            }
        }
    });

    // 12. Customer Notifications Center (notifications.php) Interactions (Frontend Only)
    const notificationGlobalToast = document.getElementById('notificationGlobalToast');
    const notificationToastText = document.getElementById('notificationToastText');
    let notificationToastTimeout = null;

    function showNotificationToast(message, isSuccess = true) {
        if (!notificationGlobalToast || !notificationToastText) return;

        notificationToastText.textContent = message;
        notificationGlobalToast.className = `alert ${isSuccess ? 'alert-success' : 'alert-danger'} py-2 px-3 mb-4 small text-center shadow-sm`;
        notificationGlobalToast.classList.remove('d-none');
        notificationGlobalToast.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        if (notificationToastTimeout) clearTimeout(notificationToastTimeout);
        notificationToastTimeout = setTimeout(() => {
            notificationGlobalToast.classList.add('d-none');
        }, 3500);
    }

    function updateNotificationCounters() {
        const allCards = document.querySelectorAll('#notificationsListContainer .notification-card');
        const unreadCards = document.querySelectorAll('#notificationsListContainer .notification-card[data-read="unread"]');
        const orderCards = document.querySelectorAll('#notificationsListContainer .notification-card[data-category="orders"]');
        const promoCards = document.querySelectorAll('#notificationsListContainer .notification-card[data-category="promotions"]');

        const statAll = document.getElementById('statAllCount');
        const statUnread = document.getElementById('statUnreadCount');
        const statOrders = document.getElementById('statOrdersCount');
        const statPromos = document.getElementById('statPromosCount');

        if (statAll) statAll.textContent = allCards.length;
        if (statUnread) statUnread.textContent = unreadCards.length;
        if (statOrders) statOrders.textContent = orderCards.length;
        if (statPromos) statPromos.textContent = promoCards.length;

        const emptyState = document.getElementById('emptyNotificationsState');
        if (emptyState && allCards.length === 0) {
            emptyState.classList.remove('d-none');
        }
    }

    const notifSearchInput = document.getElementById('notificationSearchInput');
    const notifCategoryFilter = document.getElementById('notificationCategoryFilter');
    const notifReadFilter = document.getElementById('notificationReadFilter');
    const btnClearNotifFilters = document.getElementById('btnClearNotificationFilters');
    const btnResetEmptyNotifFilters = document.getElementById('btnResetEmptyNotifFilters');

    function applyNotificationFilters() {
        const notifCards = document.querySelectorAll('#notificationsListContainer .notification-card');
        if (!notifCards.length) return;

        const query = (notifSearchInput ? notifSearchInput.value : '').toLowerCase().trim();
        const cat = notifCategoryFilter ? notifCategoryFilter.value : 'all';
        const readStatus = notifReadFilter ? notifReadFilter.value : 'all';

        let visibleCount = 0;

        notifCards.forEach(card => {
            const title = (card.getAttribute('data-title') || '').toLowerCase();
            const desc = (card.getAttribute('data-desc') || '').toLowerCase();
            const cardCat = card.getAttribute('data-category') || '';
            const cardRead = card.getAttribute('data-read') || '';

            const matchesSearch = !query || title.includes(query) || desc.includes(query) || cardCat.includes(query);
            const matchesCat = (cat === 'all') || (cardCat === cat);
            const matchesRead = (readStatus === 'all') || (cardRead === readStatus);

            if (matchesSearch && matchesCat && matchesRead) {
                card.classList.remove('d-none');
                visibleCount++;
            } else {
                card.classList.add('d-none');
            }
        });

        const emptyState = document.getElementById('emptyNotificationsState');
        if (emptyState) {
            if (visibleCount === 0) {
                emptyState.classList.remove('d-none');
            } else {
                emptyState.classList.add('d-none');
            }
        }
    }

    function resetNotificationFilters() {
        if (notifSearchInput) notifSearchInput.value = '';
        if (notifCategoryFilter) notifCategoryFilter.value = 'all';
        if (notifReadFilter) notifReadFilter.value = 'all';
        applyNotificationFilters();
    }

    if (notifSearchInput) notifSearchInput.addEventListener('input', applyNotificationFilters);
    if (notifCategoryFilter) notifCategoryFilter.addEventListener('change', applyNotificationFilters);
    if (notifReadFilter) notifReadFilter.addEventListener('change', applyNotificationFilters);
    if (btnClearNotifFilters) btnClearNotifFilters.addEventListener('click', resetNotificationFilters);
    if (btnResetEmptyNotifFilters) btnResetEmptyNotifFilters.addEventListener('click', resetNotificationFilters);

    // Mark Single Notification as Read
    document.addEventListener('click', (e) => {
        const markBtn = e.target.closest('.btn-mark-read');
        if (markBtn) {
            const card = markBtn.closest('.notification-card');
            if (card) {
                card.setAttribute('data-read', 'read');
                card.classList.remove('notif-unread', 'bg-pink-soft');
                card.classList.add('notif-read', 'bg-white');

                const dot = card.querySelector('.unread-dot');
                if (dot) dot.classList.add('d-none');

                const title = card.querySelector('.notif-card-title');
                if (title) {
                    title.classList.remove('fw-bold');
                    title.classList.add('fw-semibold');
                }

                markBtn.remove();
                updateNotificationCounters();
                showNotificationToast("Notification marked as read.");
            }
        }
    });

    // Mark All Notifications as Read
    const btnMarkAllNotificationsRead = document.getElementById('btnMarkAllNotificationsRead');
    if (btnMarkAllNotificationsRead) {
        btnMarkAllNotificationsRead.addEventListener('click', () => {
            const unreadCards = document.querySelectorAll('#notificationsListContainer .notification-card[data-read="unread"]');
            unreadCards.forEach(card => {
                card.setAttribute('data-read', 'read');
                card.classList.remove('notif-unread', 'bg-pink-soft');
                card.classList.add('notif-read', 'bg-white');

                const dot = card.querySelector('.unread-dot');
                if (dot) dot.classList.add('d-none');

                const title = card.querySelector('.notif-card-title');
                if (title) {
                    title.classList.remove('fw-bold');
                    title.classList.add('fw-semibold');
                }

                const markBtn = card.querySelector('.btn-mark-read');
                if (markBtn) markBtn.remove();
            });

            updateNotificationCounters();
            showNotificationToast("All notifications marked as read.");
        });
    }

    // Delete Single Notification
    document.addEventListener('click', (e) => {
        const delNotifBtn = e.target.closest('.btn-delete-notif');
        if (delNotifBtn) {
            const card = delNotifBtn.closest('.notification-card');
            if (card) {
                card.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    card.remove();
                    updateNotificationCounters();
                    applyNotificationFilters();
                }, 250);
                showNotificationToast("Notification removed.");
            }
        }
    });

    // Clear All Notifications Confirmation Modal
    const btnConfirmClearAllNotifications = document.getElementById('btnConfirmClearAllNotifications');
    const notificationsListContainer = document.getElementById('notificationsListContainer');

    if (btnConfirmClearAllNotifications && notificationsListContainer) {
        btnConfirmClearAllNotifications.addEventListener('click', () => {
            notificationsListContainer.innerHTML = '';
            updateNotificationCounters();
            const emptyState = document.getElementById('emptyNotificationsState');
            if (emptyState) emptyState.classList.remove('d-none');
            showNotificationToast("All notifications cleared.");
        });
    }

    // Notification Details Modal Dynamic Population
    const notifDetailsModalEl = document.getElementById('notificationDetailsModal');
    document.addEventListener('click', (e) => {
        const viewBtn = e.target.closest('.btn-view-notif-details');
        if (viewBtn) {
            const card = viewBtn.closest('.notification-card');
            if (card) {
                const title = card.getAttribute('data-title') || '';
                const desc = card.getAttribute('data-desc') || '';
                const time = card.getAttribute('data-time') || '';
                const cat = card.querySelector('.badge')?.textContent?.trim() || 'Notification';
                const icon = card.getAttribute('data-icon') || 'bi-bell';
                const actionUrl = card.getAttribute('data-action-url') || 'menu.php';
                const actionLabel = card.getAttribute('data-action-label') || 'View Details';

                const modalTitle = document.getElementById('notificationDetailsModalLabel');
                const modalDesc = document.getElementById('modalNotifDescription');
                const modalTime = document.getElementById('modalNotifTime');
                const modalCat = document.getElementById('modalNotifCategory');
                const modalIcon = document.getElementById('modalNotifIcon');
                const modalActionBtn = document.getElementById('modalNotifActionBtn');
                const modalActionLabel = document.getElementById('modalNotifActionLabel');

                if (modalTitle) modalTitle.textContent = title;
                if (modalDesc) modalDesc.textContent = desc;
                if (modalTime) modalTime.textContent = time;
                if (modalCat) modalCat.textContent = cat;
                if (modalIcon) modalIcon.className = `bi ${icon} text-primary-accent fs-5`;
                if (modalActionBtn) modalActionBtn.href = actionUrl;
                if (modalActionLabel) modalActionLabel.textContent = actionLabel;

                // Auto mark as read on view
                if (card.getAttribute('data-read') === 'unread') {
                    card.setAttribute('data-read', 'read');
                    card.classList.remove('notif-unread', 'bg-pink-soft');
                    card.classList.add('notif-read', 'bg-white');
                    const dot = card.querySelector('.unread-dot');
                    if (dot) dot.classList.add('d-none');
                    const cardTitle = card.querySelector('.notif-card-title');
                    if (cardTitle) {
                        cardTitle.classList.remove('fw-bold');
                        cardTitle.classList.add('fw-semibold');
                    }
                    const markBtn = card.querySelector('.btn-mark-read');
                    if (markBtn) markBtn.remove();
                    updateNotificationCounters();
                }

                if (notifDetailsModalEl && typeof bootstrap !== 'undefined') {
                    const bsModal = bootstrap.Modal.getOrCreateInstance(notifDetailsModalEl);
                    bsModal.show();
                }
            }
        }
    });

    // Pagination Demo Click Handling
    const notifPageLinks = document.querySelectorAll('.notif-page-link');
    if (notifPageLinks.length > 0) {
        notifPageLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const parentLi = link.closest('.page-item');
                if (parentLi && !parentLi.classList.contains('disabled') && !isNaN(parseInt(link.textContent.trim()))) {
                    document.querySelectorAll('.orders-pagination-nav .page-item').forEach(li => li.classList.remove('active'));
                    parentLi.classList.add('active');
                }
    // 14. Admin Menu & Food Management System (Step 23)
    // =========================================================================

    // A. Admin Sidebar Mobile Drawer Toggle
    const adminSidebarToggle = document.getElementById('adminSidebarToggle');
    const adminSidebar = document.getElementById('adminSidebar');
    const adminSidebarBackdrop = document.getElementById('adminSidebarBackdrop');

    if (adminSidebarToggle && adminSidebar) {
        adminSidebarToggle.addEventListener('click', () => {
            adminSidebar.classList.toggle('show');
            if (adminSidebarBackdrop) adminSidebarBackdrop.classList.toggle('show');
        });

        if (adminSidebarBackdrop) {
            adminSidebarBackdrop.addEventListener('click', () => {
                adminSidebar.classList.remove('show');
                adminSidebarBackdrop.classList.remove('show');
            });
        }
    }

    // B. Admin Menu Global Toast Helper
    function showAdminToast(message, type = 'success') {
        const toastEl = document.getElementById('adminMenuToast') || document.getElementById('categoryToast');
        if (!toastEl) return;
        
        const msgEl = toastEl.querySelector('#adminToastMessage') || toastEl.querySelector('#categoryToastMessage');
        const iconEl = toastEl.querySelector('#adminToastIcon');

        if (msgEl) msgEl.textContent = message;
        toastEl.className = `alert alert-${type} alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm py-2 px-3 mb-4`;
        if (iconEl) {
            iconEl.className = (type === 'danger') ? 'bi bi-exclamation-triangle-fill fs-5' : 'bi bi-check-circle-fill fs-5';
        }
        toastEl.classList.remove('d-none');

        setTimeout(() => {
            toastEl.classList.add('d-none');
        }, 4000);
    }

    // C. Admin Statistics Recalculator
    function recalculateAdminMenuStats() {
        const rows = document.querySelectorAll('.food-item-row');
        let total = rows.length;
        let available = 0;
        let soldOut = 0;
        let discounted = 0;

        rows.forEach(r => {
            const avail = r.getAttribute('data-availability');
            const disc = parseFloat(r.getAttribute('data-discount-percent')) || 0;
            if (avail === 'available') available++;
            else if (avail === 'sold_out') soldOut++;
            if (disc > 0) discounted++;
        });

        const elTotal = document.getElementById('statTotalFoods');
        const elAvail = document.getElementById('statAvailableFoods');
        const elSold = document.getElementById('statSoldOutFoods');
        const elDisc = document.getElementById('statDiscountFoods');

        if (elTotal) elTotal.textContent = total;
        if (elAvail) elAvail.textContent = available;
        if (elSold) elSold.textContent = soldOut;
        if (elDisc) elDisc.textContent = discounted;
    }

    // D. Real-Time Admin Food Filter & Search
    const foodSearchInput = document.getElementById('foodSearchInput');
    const catFilter = document.getElementById('adminMenuCategoryFilter');
    const availFilter = document.getElementById('adminMenuAvailabilityFilter');
    const discFilter = document.getElementById('adminMenuDiscountFilter');
    const btnClearFilters = document.getElementById('btnClearMenuFilters');
    const btnResetEmptyFilters = document.getElementById('btnResetEmptyFilters');
    const filteredCountBadge = document.getElementById('filteredCountBadge');
    const adminMenuEmptyState = document.getElementById('adminMenuEmptyState');

    function applyAdminFoodFilters() {
        const query = foodSearchInput ? foodSearchInput.value.toLowerCase().trim() : '';
        const cat = catFilter ? catFilter.value : 'all';
        const avail = availFilter ? availFilter.value : 'all';
        const disc = discFilter ? discFilter.value : 'all';

        const rows = document.querySelectorAll('.food-item-row');
        const cards = document.querySelectorAll('.food-item-card-col');
        let visibleCount = 0;

        rows.forEach(row => {
            const rName = row.getAttribute('data-name') || '';
            const rCat = row.getAttribute('data-category') || '';
            const rAvail = row.getAttribute('data-availability') || '';
            const rDisc = row.getAttribute('data-discount') || '';

            const matchQuery = !query || rName.includes(query);
            const matchCat = (cat === 'all') || (rCat === cat);
            const matchAvail = (avail === 'all') || (rAvail === avail);
            const matchDisc = (disc === 'all') || (rDisc === disc);

            const isMatch = matchQuery && matchCat && matchAvail && matchDisc;
            if (isMatch) {
                row.classList.remove('d-none');
                visibleCount++;
            } else {
                row.classList.add('d-none');
            }
        });

        cards.forEach(card => {
            const cName = card.getAttribute('data-name') || '';
            const cCat = card.getAttribute('data-category') || '';
            const cAvail = card.getAttribute('data-availability') || '';
            const cDisc = card.getAttribute('data-discount') || '';

            const matchQuery = !query || cName.includes(query);
            const matchCat = (cat === 'all') || (cCat === cat);
            const matchAvail = (avail === 'all') || (cAvail === avail);
            const matchDisc = (disc === 'all') || (cDisc === disc);

            if (matchQuery && matchCat && matchAvail && matchDisc) {
                card.classList.remove('d-none');
            } else {
                card.classList.add('d-none');
            }
        });

        if (filteredCountBadge) {
            filteredCountBadge.textContent = `${visibleCount} items shown`;
        }

        if (adminMenuEmptyState) {
            if (visibleCount === 0) {
                adminMenuEmptyState.classList.remove('d-none');
            } else {
                adminMenuEmptyState.classList.add('d-none');
            }
        }
    }

    if (foodSearchInput) foodSearchInput.addEventListener('input', applyAdminFoodFilters);
    if (catFilter) catFilter.addEventListener('change', applyAdminFoodFilters);
    if (availFilter) availFilter.addEventListener('change', applyAdminFoodFilters);
    if (discFilter) discFilter.addEventListener('change', applyAdminFoodFilters);

    function resetAdminFilters() {
        if (foodSearchInput) foodSearchInput.value = '';
        if (catFilter) catFilter.value = 'all';
        if (availFilter) availFilter.value = 'all';
        if (discFilter) discFilter.value = 'all';
        applyAdminFoodFilters();
    }

    if (btnClearFilters) btnClearFilters.addEventListener('click', resetAdminFilters);
    if (btnResetEmptyFilters) btnResetEmptyFilters.addEventListener('click', resetAdminFilters);

    // E. Food Availability Toggle (Available, Sold Out, Hidden)
    document.addEventListener('click', (e) => {
        const availLink = e.target.closest('.btn-set-availability');
        if (availLink) {
            e.preventDefault();
            const foodId = availLink.getAttribute('data-id');
            const newAvail = availLink.getAttribute('data-avail');

            const row = document.getElementById(`food-row-${foodId}`);
            const card = document.getElementById(`food-card-${foodId}`);
            let foodName = 'Food item';

            if (row) {
                row.setAttribute('data-availability', newAvail);
                const titleSpan = row.querySelector('.food-row-title');
                if (titleSpan) foodName = titleSpan.textContent.trim();

                const dropdownBtn = row.querySelector(`#availDropdown-${foodId}`);
                if (dropdownBtn) {
                    if (newAvail === 'available') {
                        dropdownBtn.className = 'btn btn-sm dropdown-toggle py-1 px-2 btn-success-subtle text-success border border-success-subtle extra-small fw-bold';
                        dropdownBtn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Available';
                    } else if (newAvail === 'sold_out') {
                        dropdownBtn.className = 'btn btn-sm dropdown-toggle py-1 px-2 btn-danger-subtle text-danger border border-danger-subtle extra-small fw-bold';
                        dropdownBtn.innerHTML = '<i class="bi bi-x-circle-fill me-1"></i> Sold Out';
                    } else {
                        dropdownBtn.className = 'btn btn-sm dropdown-toggle py-1 px-2 btn-secondary-subtle text-secondary border border-secondary-subtle extra-small fw-bold';
                        dropdownBtn.innerHTML = '<i class="bi bi-eye-slash-fill me-1"></i> Hidden';
                    }
                }
            }

            if (card) {
                card.setAttribute('data-availability', newAvail);
                const cardBtn = card.querySelector('.card-avail-badge');
                if (cardBtn) {
                    if (newAvail === 'available') {
                        cardBtn.className = 'btn btn-sm dropdown-toggle py-1 px-2 card-avail-badge btn-success-subtle text-success border border-success-subtle extra-small fw-bold';
                        cardBtn.innerHTML = '🟢 Available';
                    } else if (newAvail === 'sold_out') {
                        cardBtn.className = 'btn btn-sm dropdown-toggle py-1 px-2 card-avail-badge btn-danger-subtle text-danger border border-danger-subtle extra-small fw-bold';
                        cardBtn.innerHTML = '🔴 Sold Out';
                    } else {
                        cardBtn.className = 'btn btn-sm dropdown-toggle py-1 px-2 card-avail-badge btn-secondary-subtle text-secondary border border-secondary-subtle extra-small fw-bold';
                        cardBtn.innerHTML = '⚪ Hidden';
                    }
                }
            }

            // Sync with customer demo state
            try {
                const availMap = JSON.parse(localStorage.getItem('fastbite_food_availability') || '{}');
                availMap[foodId] = newAvail;
                localStorage.setItem('fastbite_food_availability', JSON.stringify(availMap));
            } catch (err) {}

            recalculateAdminMenuStats();
            applyAdminFoodFilters();

            const statusText = (newAvail === 'available') ? 'Available for ordering' : ((newAvail === 'sold_out') ? 'marked as Sold Out' : 'hidden from customer menu');
            showAdminToast(`${foodName} is now ${statusText}.`);
        }
    });

    // F. Live Price & Discount Calculation Form Helper
    const formPrice = document.getElementById('formFoodPrice');
    const formDisc = document.getElementById('formFoodDiscount');
    const formStart = document.getElementById('formDiscountStart');
    const formEnd = document.getElementById('formDiscountEnd');

    const liveFinalPrice = document.getElementById('livePreviewFinalPrice');
    const liveOrigPrice = document.getElementById('livePreviewOriginalPrice');
    const liveDiscBadge = document.getElementById('livePreviewDiscountBadge');
    const liveSavedAmt = document.getElementById('livePreviewSavedAmount');
    const liveCountdownWidget = document.getElementById('liveCountdownWidget');
    const liveCountdownText = document.getElementById('liveCountdownText');

    function updateLivePricePreview() {
        if (!formPrice || !liveFinalPrice) return;

        const price = parseFloat(formPrice.value) || 0;
        let disc = parseFloat(formDisc ? formDisc.value : 0) || 0;
        disc = Math.min(100, Math.max(0, disc));

        const discAmt = (price * disc) / 100;
        const finalP = Math.max(0, price - discAmt);

        liveFinalPrice.textContent = `$${finalP.toFixed(2)}`;

        if (disc > 0 && price > 0) {
            if (liveOrigPrice) {
                liveOrigPrice.textContent = `$${price.toFixed(2)}`;
                liveOrigPrice.classList.remove('d-none');
            }
            if (liveDiscBadge) {
                liveDiscBadge.textContent = `${disc}% OFF`;
                liveDiscBadge.classList.remove('d-none');
            }
            if (liveSavedAmt) {
                liveSavedAmt.textContent = `Discount: $${discAmt.toFixed(2)}`;
            }
        } else {
            if (liveOrigPrice) liveOrigPrice.classList.add('d-none');
            if (liveDiscBadge) liveDiscBadge.classList.add('d-none');
            if (liveSavedAmt) liveSavedAmt.textContent = `No discount applied`;
        }

        // Live countdown preview check
        if (formEnd && formEnd.value && disc > 0 && liveCountdownWidget) {
            const endDate = new Date(formEnd.value);
            const now = new Date();
            if (endDate > now) {
                const diffMs = endDate - now;
                const hours = Math.floor(diffMs / (1000 * 60 * 60));
                const mins = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
                liveCountdownWidget.classList.remove('d-none');
                if (liveCountdownText) liveCountdownText.textContent = `Offer ends in ${hours}h ${mins}m`;
            } else {
                liveCountdownWidget.classList.remove('d-none');
                if (liveCountdownText) liveCountdownText.textContent = `Schedule set (Expired)`;
            }
        } else if (liveCountdownWidget) {
            liveCountdownWidget.classList.add('d-none');
        }
    }

    if (formPrice) formPrice.addEventListener('input', updateLivePricePreview);
    if (formDisc) formDisc.addEventListener('input', updateLivePricePreview);
    if (formStart) formStart.addEventListener('change', updateLivePricePreview);
    if (formEnd) formEnd.addEventListener('change', updateLivePricePreview);

    // G. Drag & Drop Image Dropzone & Preview Handler
    const dropzone = document.getElementById('adminImageDropzone');
    const fileInput = document.getElementById('formImageInput');
    const dropEmpty = document.getElementById('dropzoneEmptyState');
    const dropPreview = document.getElementById('dropzonePreviewState');
    const imgPreview = document.getElementById('formImagePreview');
    const imgFilename = document.getElementById('formImageFilename');
    const btnClearImage = document.getElementById('btnClearImage');

    if (dropzone && fileInput) {
        dropzone.addEventListener('click', (e) => {
            if (e.target.closest('#btnClearImage')) return;
            fileInput.click();
        });

        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.classList.add('dragover');
        });

        dropzone.addEventListener('dragleave', () => {
            dropzone.classList.remove('dragover');
        });

        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('dragover');
            if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                handleSelectedImage(e.dataTransfer.files[0]);
            }
        });

        fileInput.addEventListener('change', (e) => {
            if (e.target.files && e.target.files[0]) {
                handleSelectedImage(e.target.files[0]);
            }
        });

        function handleSelectedImage(file) {
            if (!file.type.startsWith('image/')) {
                showAdminToast('Please select a valid image file (JPG, PNG, WEBP).', 'danger');
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                showAdminToast('Image size exceeds 5MB limit.', 'danger');
                return;
            }

            const reader = new FileReader();
            reader.onload = function (evt) {
                if (imgPreview) imgPreview.src = evt.target.result;
                if (imgFilename) imgFilename.textContent = file.name;
                if (dropEmpty) dropEmpty.classList.add('d-none');
                if (dropPreview) dropPreview.classList.remove('d-none');

                // Sync with card preview modal image
                const prevCardImg = document.getElementById('prevCardImage');
                if (prevCardImg) prevCardImg.src = evt.target.result;
            };
            reader.readAsDataURL(file);
        }

        if (btnClearImage) {
            btnClearImage.addEventListener('click', (e) => {
                e.stopPropagation();
                fileInput.value = '';
                if (imgPreview) imgPreview.src = '../assets/images/placeholders/food-placeholder.jpg';
                if (dropEmpty) dropEmpty.classList.remove('d-none');
                if (dropPreview) dropPreview.classList.add('d-none');
            });
        }
    }

    // H. Add / Edit Food Modal Controller
    const btnOpenAddModal = document.getElementById('btnOpenAddFoodModal');
    const foodModalEl = document.getElementById('foodFormModal');
    const formFoodId = document.getElementById('formFoodId');
    const formFoodName = document.getElementById('formFoodName');
    const formFoodCat = document.getElementById('formFoodCategory');
    const formFoodDesc = document.getElementById('formFoodDesc');
    const formFoodAvail = document.getElementById('formFoodAvailability');
    const formFoodFeat = document.getElementById('formFoodFeatured');
    const formFoodPop = document.getElementById('formFoodPopular');
    const foodModalTitle = document.getElementById('foodFormModalLabel');
    const btnSaveFoodLabel = document.getElementById('btnSaveFoodLabel');

    if (btnOpenAddModal && foodModalEl && typeof bootstrap !== 'undefined') {
        btnOpenAddModal.addEventListener('click', () => {
            if (formFoodId) formFoodId.value = '';
            if (formFoodName) formFoodName.value = '';
            if (formFoodCat) formFoodCat.value = '';
            if (formFoodDesc) formFoodDesc.value = '';
            if (formPrice) formPrice.value = '';
            if (formDisc) formDisc.value = '0';
            if (formStart) formStart.value = '';
            if (formEnd) formEnd.value = '';
            if (formFoodAvail) formFoodAvail.value = 'available';
            if (formFoodFeat) formFoodFeat.checked = false;
            if (formFoodPop) formFoodPop.checked = false;

            if (dropEmpty) dropEmpty.classList.remove('d-none');
            if (dropPreview) dropPreview.classList.add('d-none');

            if (foodModalTitle) foodModalTitle.textContent = 'Add New Food';
            if (btnSaveFoodLabel) btnSaveFoodLabel.textContent = 'Save Food';

            updateLivePricePreview();

            const modal = bootstrap.Modal.getOrCreateInstance(foodModalEl);
            modal.show();
        });
    }

    // Edit Food Click
    document.addEventListener('click', (e) => {
        const editBtn = e.target.closest('.btn-action-edit');
        if (editBtn && foodModalEl && typeof bootstrap !== 'undefined') {
            const foodId = editBtn.getAttribute('data-id');
            const row = document.getElementById(`food-row-${foodId}`) || document.getElementById(`food-card-${foodId}`);
            if (!row) return;

            if (formFoodId) formFoodId.value = foodId;
            if (formFoodName) {
                const titleEl = row.querySelector('.food-row-title') || row.querySelector('.card-food-title');
                formFoodName.value = titleEl ? titleEl.textContent.trim() : '';
            }
            if (formFoodCat) formFoodCat.value = row.getAttribute('data-category') || '';
            if (formFoodDesc) formFoodDesc.value = row.getAttribute('data-desc') || '';
            if (formPrice) formPrice.value = row.getAttribute('data-price') || '';
            if (formDisc) formDisc.value = row.getAttribute('data-discount-percent') || '0';
            if (formStart) formStart.value = row.getAttribute('data-discount-start') || '';
            if (formEnd) formEnd.value = row.getAttribute('data-discount-end') || '';
            if (formFoodAvail) formFoodAvail.value = row.getAttribute('data-availability') || 'available';
            if (formFoodFeat) formFoodFeat.checked = row.getAttribute('data-featured') === '1';
            if (formFoodPop) formFoodPop.checked = row.getAttribute('data-popular') === '1';

            const imgSrc = row.getAttribute('data-image') || '';
            if (imgPreview && imgSrc) {
                imgPreview.src = `../assets/images/foods/${imgSrc}`;
                if (imgFilename) imgFilename.textContent = imgSrc;
                if (dropEmpty) dropEmpty.classList.add('d-none');
                if (dropPreview) dropPreview.classList.remove('d-none');
            }

            if (foodModalTitle) foodModalTitle.textContent = 'Edit Food Item';
            if (btnSaveFoodLabel) btnSaveFoodLabel.textContent = 'Save Changes';

            updateLivePricePreview();

            const modal = bootstrap.Modal.getOrCreateInstance(foodModalEl);
            modal.show();
        }
    });

    // Save Food Form Submission
    const adminFoodForm = document.getElementById('adminFoodForm');
    if (adminFoodForm) {
        adminFoodForm.addEventListener('submit', (e) => {
            e.preventDefault();
            if (!formFoodName.value.trim() || !formFoodCat.value || !formPrice.value) {
                adminFoodForm.classList.add('was-validated');
                return;
            }

            const isEditing = Boolean(formFoodId.value);
            const foodNameVal = formFoodName.value.trim();

            if (foodModalEl && typeof bootstrap !== 'undefined') {
                const modal = bootstrap.Modal.getInstance(foodModalEl);
                if (modal) modal.hide();
            }

            showAdminToast(isEditing ? `"${foodNameVal}" updated successfully.` : `"${foodNameVal}" added to menu successfully.`);
            recalculateAdminMenuStats();
        });
    }

    // I. Customer Menu Live Card Preview Modal
    const previewModalEl = document.getElementById('foodPreviewModal');
    document.addEventListener('click', (e) => {
        const prevBtn = e.target.closest('.btn-action-preview');
        if (prevBtn && previewModalEl && typeof bootstrap !== 'undefined') {
            const foodId = prevBtn.getAttribute('data-id');
            const row = document.getElementById(`food-row-${foodId}`) || document.getElementById(`food-card-${foodId}`);
            if (!row) return;

            const name = row.querySelector('.food-row-title')?.textContent || row.querySelector('.card-food-title')?.textContent || 'Food Item';
            const cat = row.querySelector('.food-row-category')?.textContent || 'FastBite';
            const desc = row.getAttribute('data-desc') || 'Freshly prepared FastBite meal.';
            const price = parseFloat(row.getAttribute('data-price')) || 0;
            const finalPrice = parseFloat(row.getAttribute('data-final-price')) || price;
            const discPercent = parseFloat(row.getAttribute('data-discount-percent')) || 0;
            const avail = row.getAttribute('data-availability') || 'available';
            const imgSrc = row.querySelector('img')?.src || '../assets/images/placeholders/food-placeholder.jpg';

            const pTitle = document.getElementById('prevCardTitle');
            const pCat = document.getElementById('prevCardCategory');
            const pDesc = document.getElementById('prevCardDesc');
            const pFinal = document.getElementById('prevCardFinalPrice');
            const pOrig = document.getElementById('prevCardOriginalPrice');
            const pDiscBadge = document.getElementById('prevCardDiscountBadge');
            const pSoldBadge = document.getElementById('prevCardSoldOutBadge');
            const pBtn = document.getElementById('prevCardBtnAction');
            const pImg = document.getElementById('prevCardImage');

            if (pTitle) pTitle.textContent = name;
            if (pCat) pCat.textContent = cat;
            if (pDesc) pDesc.textContent = desc;
            if (pFinal) pFinal.textContent = `$${finalPrice.toFixed(2)}`;
            if (pImg) pImg.src = imgSrc;

            if (discPercent > 0 && price > finalPrice) {
                if (pOrig) {
                    pOrig.textContent = `$${price.toFixed(2)}`;
                    pOrig.classList.remove('d-none');
                }
                if (pDiscBadge) {
                    pDiscBadge.textContent = `${discPercent}% OFF`;
                    pDiscBadge.classList.remove('d-none');
                }
            } else {
                if (pOrig) pOrig.classList.add('d-none');
                if (pDiscBadge) pDiscBadge.classList.add('d-none');
            }

            if (avail === 'sold_out') {
                if (pSoldBadge) pSoldBadge.classList.remove('d-none');
                if (pBtn) {
                    pBtn.className = 'btn btn-sm btn-secondary py-1 px-3 disabled';
                    pBtn.innerHTML = 'Sold Out';
                }
            } else {
                if (pSoldBadge) pSoldBadge.classList.add('d-none');
                if (pBtn) {
                    pBtn.className = 'btn btn-sm btn-auth-primary py-1 px-3 d-inline-flex align-items-center gap-1';
                    pBtn.innerHTML = '<i class="bi bi-bag-plus"></i> Add to Cart';
                }
            }

            const modal = bootstrap.Modal.getOrCreateInstance(previewModalEl);
            modal.show();
        }
    });

    // J. Delete Food Modal & Confirmation
    const deleteModalEl = document.getElementById('deleteFoodModal');
    const deleteNamePrompt = document.getElementById('deleteFoodNamePrompt');
    const btnConfirmDelete = document.getElementById('btnConfirmDeleteFood');
    let pendingDeleteId = null;

    document.addEventListener('click', (e) => {
        const delBtn = e.target.closest('.btn-action-delete');
        if (delBtn && deleteModalEl && typeof bootstrap !== 'undefined') {
            pendingDeleteId = delBtn.getAttribute('data-id');
            const name = delBtn.getAttribute('data-name') || 'this item';
            if (deleteNamePrompt) deleteNamePrompt.textContent = name;

            const modal = bootstrap.Modal.getOrCreateInstance(deleteModalEl);
            modal.show();
        }
    });

    if (btnConfirmDelete) {
        btnConfirmDelete.addEventListener('click', () => {
            if (pendingDeleteId) {
                const row = document.getElementById(`food-row-${pendingDeleteId}`);
                const card = document.getElementById(`food-card-${pendingDeleteId}`);
                const name = deleteNamePrompt ? deleteNamePrompt.textContent : 'Food item';

                if (row) row.remove();
                if (card) card.remove();

                if (deleteModalEl && typeof bootstrap !== 'undefined') {
                    const modal = bootstrap.Modal.getInstance(deleteModalEl);
                    if (modal) modal.hide();
                }

                recalculateAdminMenuStats();
                applyAdminFoodFilters();
                showAdminToast(`${name} has been deleted.`, 'danger');
                pendingDeleteId = null;
            }
        });
    }

    // K. Category Management Modals & Category Discount Controller
    const btnAddCat = document.getElementById('btnOpenAddCategoryModal');
    const catModalEl = document.getElementById('categoryFormModal');
    const catDiscountModalEl = document.getElementById('categoryDiscountModal');

    if (btnAddCat && catModalEl && typeof bootstrap !== 'undefined') {
        btnAddCat.addEventListener('click', () => {
            const form = document.getElementById('adminCategoryForm');
            if (form) form.reset();
            const modal = bootstrap.Modal.getOrCreateInstance(catModalEl);
            modal.show();
        });
    }

    document.addEventListener('click', (e) => {
        const catDiscBtn = e.target.closest('.btn-cat-discount');
        if (catDiscBtn && catDiscountModalEl && typeof bootstrap !== 'undefined') {
            const catName = catDiscBtn.getAttribute('data-name');
            const catDisc = catDiscBtn.getAttribute('data-discount') || '0';
            const catStart = catDiscBtn.getAttribute('data-start') || '';
            const catEnd = catDiscBtn.getAttribute('data-end') || '';

            const titleEl = document.getElementById('discountCatNameTitle');
            const discInput = document.getElementById('formCatDiscountPercent');
            const startInput = document.getElementById('formCatDiscountStart');
            const endInput = document.getElementById('formCatDiscountEnd');

            if (titleEl) titleEl.textContent = catName;
            if (discInput) discInput.value = catDisc;
            if (startInput) startInput.value = catStart;
            if (endInput) endInput.value = catEnd;

            const modal = bootstrap.Modal.getOrCreateInstance(catDiscountModalEl);
            modal.show();
        }
    });

    const catDiscForm = document.getElementById('adminCategoryDiscountForm');
    if (catDiscForm) {
        catDiscForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const discVal = document.getElementById('formCatDiscountPercent')?.value || 0;
            const catName = document.getElementById('discountCatNameTitle')?.textContent || 'Category';

            if (catDiscountModalEl && typeof bootstrap !== 'undefined') {
                const modal = bootstrap.Modal.getInstance(catDiscountModalEl);
                if (modal) modal.hide();
            }

            showAdminToast(`Category discount of ${discVal}% applied to ${catName}. Individual food discounts will maintain priority.`);
        });
    }

    // L. Global Scheduled Discount Real-Time Countdown Handler
    setInterval(() => {
        const chips = document.querySelectorAll('.countdown-chip');
        const now = new Date();

        chips.forEach(chip => {
            const endStr = chip.getAttribute('data-end');
            if (!endStr) return;

            const endDate = new Date(endStr);
            if (endDate > now) {
                const diffMs = endDate - now;
                const hours = Math.floor(diffMs / (1000 * 60 * 60));
                const mins = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
                const secs = Math.floor((diffMs % (1000 * 60)) / 1000);
                chip.innerHTML = `<i class="bi bi-clock me-1"></i>Ends in ${hours}h ${mins}m ${secs}s`;
            } else {
                chip.innerHTML = `<span class="text-muted"><i class="bi bi-clock-history me-1"></i>Offer expired</span>`;
            }
        });
    }, 1000);

    // M. Customer Menu Demo State Synchronization (menu.php)
    try {
        const savedAvail = JSON.parse(localStorage.getItem('fastbite_food_availability') || '{}');
        Object.entries(savedAvail).forEach(([foodId, state]) => {
            const customerCard = document.getElementById(`food-${foodId}`) || document.querySelector(`.menu-food-card[id*="${foodId}"]`);
            if (customerCard) {
                if (state === 'sold_out') {
                    customerCard.classList.add('sold-out');
                    const cartBtn = customerCard.querySelector('.btn-menu-cart');
                    if (cartBtn) {
                        cartBtn.classList.add('disabled');
                        cartBtn.innerHTML = '<i class="bi bi-x-circle"></i> Sold Out';
                    }
                    const imgWrap = customerCard.querySelector('.menu-food-image-wrap');
                    if (imgWrap && !imgWrap.querySelector('.sold-out-badge')) {
                        const badge = document.createElement('div');
                        badge.className = 'position-absolute top-0 end-0 m-2';
                        badge.innerHTML = '<span class="sold-out-badge shadow-sm">SOLD OUT</span>';
                        imgWrap.appendChild(badge);
                    }
                } else if (state === 'hidden') {
                    const col = customerCard.closest('.menu-food-col');
                    if (col) col.classList.add('d-none');
                }
            }
        });
    } catch (err) {}

    // 22. Customer Contact Page Form Validation & Demo Toast (Step 22)
    const customerContactForm = document.getElementById('customerContactForm');
    const contactSuccessAlert = document.getElementById('contactSuccessAlert');

    if (customerContactForm) {
        const inputName = document.getElementById('contactFullName');
        const inputEmail = document.getElementById('contactEmail');
        const inputPhone = document.getElementById('contactPhone');
        const inputSubject = document.getElementById('contactSubject');
        const inputMessage = document.getElementById('contactMessage');
        const btnSubmit = document.getElementById('btnContactSubmit');

        function validateEmailFormat(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }

        function setFieldStatus(inputEl, feedbackEl, isValid, errorMsg = '') {
            if (!inputEl) return;
            if (isValid) {
                inputEl.classList.remove('is-invalid');
                inputEl.classList.add('is-valid');
            } else {
                inputEl.classList.remove('is-valid');
                inputEl.classList.add('is-invalid');
                if (feedbackEl && errorMsg) {
                    feedbackEl.textContent = errorMsg;
                }
            }
        }

        // Live input validation clear on type
        [inputName, inputEmail, inputSubject, inputMessage].forEach(input => {
            if (input) {
                input.addEventListener('input', () => {
                    input.classList.remove('is-invalid');
                });
            }
        });

        customerContactForm.addEventListener('submit', function(e) {
            e.preventDefault();

            let isValid = true;

            // 1. Full Name (Required)
            const nameVal = inputName ? inputName.value.trim() : '';
            const nameFeedback = document.getElementById('nameFeedback');
            if (!nameVal) {
                setFieldStatus(inputName, nameFeedback, false, 'Please enter your name.');
                isValid = false;
            } else {
                setFieldStatus(inputName, nameFeedback, true);
            }

            // 2. Email Address (Required & format)
            const emailVal = inputEmail ? inputEmail.value.trim() : '';
            const emailFeedback = document.getElementById('emailFeedback');
            if (!emailVal) {
                setFieldStatus(inputEmail, emailFeedback, false, 'Please enter a valid email address.');
                isValid = false;
            } else if (!validateEmailFormat(emailVal)) {
                setFieldStatus(inputEmail, emailFeedback, false, 'Please enter a valid email address.');
                isValid = false;
            } else {
                setFieldStatus(inputEmail, emailFeedback, true);
            }

            // 3. Subject (Optional)
            const subjectVal = inputSubject ? inputSubject.value.trim() : '';
            if (inputSubject) {
                setFieldStatus(inputSubject, document.getElementById('subjectFeedback'), true);
            }

            // 4. Message (Required)
            const messageVal = inputMessage ? inputMessage.value.trim() : '';
            const messageFeedback = document.getElementById('messageFeedback');
            if (!messageVal) {
                setFieldStatus(inputMessage, messageFeedback, false, 'Please enter your message.');
                isValid = false;
            } else {
                setFieldStatus(inputMessage, messageFeedback, true);
            }

            if (isValid) {
                if (btnSubmit) {
                    const origBtnHtml = btnSubmit.innerHTML;
                    btnSubmit.disabled = true;
                    btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Sending...';

                    const formData = new FormData(customerContactForm);
                    if (!formData.get('subject')) {
                        formData.set('subject', 'General Inquiry');
                    }

                    fetch('contact.php', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json().catch(() => ({ success: true })))
                    .then(data => {
                        btnSubmit.disabled = false;
                        btnSubmit.innerHTML = origBtnHtml;

                        if (data && data.success) {
                            if (contactSuccessAlert) {
                                contactSuccessAlert.classList.remove('d-none');
                                contactSuccessAlert.classList.add('d-flex');
                                contactSuccessAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                            }
                            const errAlert = document.getElementById('contactErrorAlert');
                            if (errAlert) {
                                errAlert.classList.add('d-none');
                                errAlert.classList.remove('d-flex');
                            }
                            customerContactForm.reset();
                            [inputName, inputEmail, inputSubject, inputMessage].forEach(input => {
                                if (input) {
                                    input.classList.remove('is-valid');
                                    input.classList.remove('is-invalid');
                                }
                            });
                        } else {
                            const errAlert = document.getElementById('contactErrorAlert');
                            const errText = document.getElementById('contactErrorAlertText');
                            if (errAlert) {
                                if (errText && data.message) errText.textContent = data.message;
                                errAlert.classList.remove('d-none');
                                errAlert.classList.add('d-flex');
                                errAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                            }
                        }
                    })
                    .catch(error => {
                        // If AJAX fails, fall back to native POST submission
                        customerContactForm.submit();
                    });
                }
            }
        });
    }

    // 23. Home Page Contact Form Handler (#homeContactForm)
    const homeContactForm = document.getElementById('homeContactForm');
    if (homeContactForm) {
        homeContactForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const nameInput = document.getElementById('contact_name');
            const emailInput = document.getElementById('contact_email');
            const phoneInput = document.getElementById('contact_phone');
            const subjectInput = document.getElementById('contact_subject');
            const messageInput = document.getElementById('contact_message');
            const submitBtn = document.getElementById('btnHomeContactSubmit');
            const successAlert = document.getElementById('homeContactSuccessAlert');
            const errorAlert = document.getElementById('homeContactErrorAlert');
            const errorText = document.getElementById('homeContactErrorText');

            const name = nameInput ? nameInput.value.trim() : '';
            const email = emailInput ? emailInput.value.trim() : '';
            const message = messageInput ? messageInput.value.trim() : '';

            if (!name || !email || !message) {
                if (errorAlert) {
                    if (errorText) errorText.textContent = 'Fadlan buuxi Magaca, Email-ka, iyo Fariinta.';
                    errorAlert.classList.remove('d-none');
                    errorAlert.classList.add('d-flex');
                }
                if (successAlert) successAlert.classList.add('d-none');
                return;
            }

            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                if (errorAlert) {
                    if (errorText) errorText.textContent = 'Fadlan geli email sax ah.';
                    errorAlert.classList.remove('d-none');
                    errorAlert.classList.add('d-flex');
                }
                if (successAlert) successAlert.classList.add('d-none');
                return;
            }

            if (errorAlert) errorAlert.classList.add('d-none');

            const originalBtnHtml = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Sending...';
            }

            const formData = new FormData(homeContactForm);
            if (!formData.get('subject')) {
                formData.set('subject', 'General Inquiry');
            }

            fetch('contact.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json().catch(() => ({ success: true })))
            .then(data => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHtml;
                }
                if (data && data.success) {
                    if (successAlert) {
                        successAlert.classList.remove('d-none');
                        successAlert.classList.add('d-flex');
                        successAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                    if (errorAlert) errorAlert.classList.add('d-none');
                    homeContactForm.reset();
                } else {
                    if (errorAlert) {
                        if (errorText && data.message) errorText.textContent = data.message;
                        errorAlert.classList.remove('d-none');
                        errorAlert.classList.add('d-flex');
                    }
                }
            })
            .catch(() => {
                // Fall back to native submission
                homeContactForm.submit();
            });
        });
    }

    console.log('FastBite Application, Admin Menu & Contact Page initialized successfully.');
});

