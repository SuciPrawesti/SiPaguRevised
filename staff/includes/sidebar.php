<?php
/**
 * SIDEBAR TEMPLATE - SiPagu Staff
 * Konsisten dengan admin
 */
?>

<!-- Sidebar -->
<div class="main-sidebar sidebar-style-2">
    <aside id="sidebar-wrapper">

        <!-- Brand Logo Full -->
        <div class="sidebar-brand">
            <a href="<?= BASE_URL ?>staff/index.php">
                <img
                    src="<?= ASSETS_URL ?>/img/logoSiPagu.png"
                    alt="Logo SiPagu"
                    style="max-height: 40px; max-width: 150px; object-fit: contain;"
                >
            </a>
        </div>

        <!-- Brand Logo Small (Mini Sidebar) -->
        <div class="sidebar-brand sidebar-brand-sm">
            <a href="<?= BASE_URL ?>staff/index.php">
                <img
                    src="<?= ASSETS_URL ?>/img/logoSiPagu.png"
                    alt="Logo SiPagu"
                    style="max-height: 30px; max-width: 40px; object-fit: contain;"
                >
            </a>
        </div>

        <!-- Menu Items -->
        <ul class="sidebar-menu">
            
            <!-- ================= DASHBOARD ================= -->
            <li class="menu-header">Dashboard</li>
            <li class="<?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">
                <a href="<?= BASE_URL ?>staff/index.php" class="nav-link">
                    <i class="fas fa-fire"></i><span>Dashboard</span>
                </a>
            </li>

            <!-- ================= HONOR SAYA ================= -->
            <li class="menu-header">Honor Saya</li>
            <?php
            $honor_pages   = ['riwayat_honor', 'slip_honor', 'detail_slip', 'cetak_slip'];
            $is_honor_page = in_array(basename($_SERVER['PHP_SELF'], '.php'), $honor_pages);
            ?>
            <li class="dropdown <?= $is_honor_page ? 'active' : ''; ?>">
                <a href="#" class="nav-link has-dropdown">
                    <i class="fas fa-money-bill-wave"></i><span>Informasi Honor</span>
                </a>
                <ul class="dropdown-menu">
                    <li class="<?= basename($_SERVER['PHP_SELF']) === 'riwayat_honor.php' ? 'active' : ''; ?>">
                        <a class="nav-link" href="<?= BASE_URL ?>staff/riwayat_honor.php">Riwayat Honor</a>
                    </li>
                    <li class="<?= basename($_SERVER['PHP_SELF']) === 'slip_honor.php' ? 'active' : ''; ?>">
                        <a class="nav-link" href="<?= BASE_URL ?>staff/slip_honor.php">Slip Honor</a>
                    </li>
                </ul>
            </li>

        </ul>
        
        <!-- ================= FOOTER SIDEBAR ================= -->
        <div class="sidebar-footer mt-4 mb-4 p-3" style="margin-top: auto !important;">
            <!-- Logout Button with Proper Alignment -->
            <button type="button" onclick="showLogoutModal()" class="btn btn-danger btn-lg btn-block btn-icon-split d-flex align-items-center justify-content-center">
                <i class="fas fa-sign-out-alt mr-2"></i>
                <span class="flex-grow-1 text-center">Logout</span>
            </button>
        </div>
    </aside>
</div>
<!-- End Sidebar -->

<style>
/* ========== HIDE SIDEBAR USER PANEL ========== */
.sidebar-user,
.user-panel,
.author-box,
#sidebar-wrapper .sidebar-user,
#sidebar-wrapper .user-panel,
#sidebar-wrapper .author-box {
    display: none !important;
}

/* ========== LOGOUT MODAL STYLES ========== */
.logout-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.logout-modal-overlay.active {
    display: flex;
    opacity: 1;
}

.logout-modal {
    background: var(--white);
    border-radius: 20px;
    box-shadow: var(--shadow-xl);
    width: 90%;
    max-width: 400px;
    overflow: hidden;
    transform: translateY(-20px);
    transition: transform 0.3s ease;
    border: 1px solid var(--border);
}

.logout-modal-overlay.active .logout-modal {
    transform: translateY(0);
}

.logout-modal-header {
    padding: 2rem 2rem 1rem;
    text-align: center;
    background: linear-gradient(135deg, rgba(0, 61, 122, 0.05) 0%, rgba(59, 130, 246, 0.05) 100%);
}

.logout-modal-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--primary-dark);
    margin-bottom: 0.5rem;
}

.logout-modal-subtitle {
    color: var(--accent);
    font-size: 0.95rem;
    line-height: 1.5;
}

.logout-modal-body {
    padding: 2rem;
    text-align: center;
}

.logout-modal-message {
    font-size: 1rem;
    color: var(--primary-dark);
    margin-bottom: 2rem;
    line-height: 1.6;
}

.logout-modal-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

.logout-modal-btn {
    padding: 0.75rem 2rem;
    border-radius: 999px;
    font-weight: 500;
    font-size: 0.9375rem;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    min-width: 120px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
}

.logout-modal-btn i {
    margin-right: 8px;
    font-size: 0.9rem;
}

.logout-modal-btn-cancel {
    background: linear-gradient(135deg, var(--light-gray) 0%, #f0f9ff 100%);
    color: var(--primary);
    border: 1px solid var(--border);
}

.logout-modal-btn-cancel:hover {
    background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.logout-modal-btn-confirm {
    background: linear-gradient(135deg, var(--danger) 0%, #dc2626 100%);
    color: var(--white);
}

.logout-modal-btn-confirm:hover {
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

/* ========== RESPONSIVE STYLES ========== */
@media (max-width: 768px) {
    .logout-modal { width: 95%; margin: 1rem; }
    .logout-modal-header { padding: 1.5rem 1.5rem 1rem; }
    .logout-modal-title { font-size: 1.35rem; }
    .logout-modal-body { padding: 1.75rem; }
    .logout-modal-actions { flex-direction: column; gap: 0.75rem; }
    .logout-modal-btn { width: 100%; min-width: auto; padding: 0.75rem 1.5rem; }
}

@media (max-width: 576px) {
    .logout-modal { width: calc(100% - 30px); margin: 0 15px; }
    .logout-modal-header { padding: 1.25rem 1.25rem 0.75rem; }
    .logout-modal-title { font-size: 1.25rem; }
    .logout-modal-body { padding: 1.5rem; }
    .logout-modal-message { font-size: 0.95rem; margin-bottom: 1.5rem; }
}

@media (max-width: 480px) {
    .logout-modal-header { padding: 1rem 1rem 0.5rem; }
    .logout-modal-title { font-size: 1.15rem; margin-bottom: 0.25rem; }
    .logout-modal-subtitle { font-size: 0.85rem; }
    .logout-modal-body { padding: 1.25rem; }
    .logout-modal-message { font-size: 0.9rem; margin-bottom: 1.25rem; }
}

/* ========== SIDEBAR FOOTER STYLES ========== */
.sidebar-footer {
    flex-shrink: 0;
    position: sticky;
    bottom: 0;
    background: #fff;
    z-index: 10;
    padding: 12px 15px !important;
    margin-top: auto;
    border-top: 1px solid #f0f0f0;
}

.dropdown-menu {
    z-index: 1001 !important;
    position: relative;
}

.btn-icon-split {
    padding: 10px 15px;
    border-radius: 6px;
    transition: all 0.3s ease;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    cursor: pointer;
    width: 100%;
}

.btn-icon-split:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.btn-icon-split i {
    font-size: 1rem;
    width: 20px;
    text-align: center;
}

body.sidebar-mini .sidebar-footer {
    width: 65px;
    padding: 10px !important;
}

body.sidebar-mini .btn-icon-split {
    padding: 8px 10px;
}

body.sidebar-mini .btn-icon-split span {
    display: none;
}

body.sidebar-mini .btn-icon-split i {
    margin-right: 0;
    font-size: 18px;
}

@media (max-width: 768px) {
    .sidebar-menu { max-height: 60vh; }
    .sidebar-footer { position: relative; margin-top: 20px; border-top: 1px solid #e0e0e0; }
}
</style>

<!-- Notifikasi Logout Modal -->
<div class="logout-modal-overlay" id="logoutModalSidebar">
    <div class="logout-modal">
        <div class="logout-modal-header">
            <h3 class="logout-modal-title">Logout Confirmation</h3>
            <p class="logout-modal-subtitle">Confirm your action</p>
        </div>
        <div class="logout-modal-body">
            <p class="logout-modal-message">
                Apakah Anda yakin ingin logout dari sistem? 
                Anda akan dialihkan ke halaman login.
            </p>
            <div class="logout-modal-actions">
                <button class="logout-modal-btn logout-modal-btn-cancel" onclick="hideLogoutModalSidebar()">
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
                <a href="<?= BASE_URL ?>logout.php" class="logout-modal-btn logout-modal-btn-confirm">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// =======================================================
// BodyScrollManager — terpusat, counter-based
// =======================================================
if (!window.BodyScrollManager) {
    window.BodyScrollManager = (function() {
        var lockCount = 0;

        var styleEl = document.getElementById('body-scroll-lock-style');
        if (!styleEl) {
            styleEl          = document.createElement('style');
            styleEl.id       = 'body-scroll-lock-style';
            styleEl.textContent = 'body.body-scroll-locked { overflow: hidden !important; }';
            document.head.appendChild(styleEl);
        }

        return {
            lock: function() {
                lockCount++;
                document.body.classList.add('body-scroll-locked');
            },
            unlock: function() {
                lockCount = Math.max(0, lockCount - 1);
                if (lockCount === 0) {
                    document.body.classList.remove('body-scroll-locked');
                }
            },
            reset: function() {
                lockCount = 0;
                document.body.classList.remove('body-scroll-locked');
            }
        };
    })();
}

// -------------------------------------------------------
// Fungsi global logout modal — dipanggil dari inline onclick
// -------------------------------------------------------
function showLogoutModal() {
    var modal = document.getElementById('logoutModalSidebar');
    if (!modal) return;
    modal.classList.add('active');
    window.BodyScrollManager.lock();
}

function hideLogoutModalSidebar() {
    var modal = document.getElementById('logoutModalSidebar');
    if (!modal) return;
    modal.classList.remove('active');
    window.BodyScrollManager.unlock();
}

// Tutup modal saat klik backdrop
document.getElementById('logoutModalSidebar').addEventListener('click', function(e) {
    if (e.target === this) {
        hideLogoutModalSidebar();
    }
});

// ESC handler
document.addEventListener('keydown', function(e) {
    if (e.key !== 'Escape') return;

    var sidebarModal = document.getElementById('logoutModalSidebar');
    var navbarModal  = document.getElementById('logoutModal');

    if (sidebarModal && sidebarModal.classList.contains('active')) {
        hideLogoutModalSidebar();
    }
    if (navbarModal && navbarModal.classList.contains('active') && typeof hideLogoutModal === 'function') {
        hideLogoutModal();
    }
});

// -------------------------------------------------------
// Sidebar menu height manager
// -------------------------------------------------------
document.addEventListener('DOMContentLoaded', function() {
    var sidebarMenu     = document.querySelector('.sidebar-menu');
    var sidebarFooter   = document.querySelector('.sidebar-footer');
    var dropdownToggles = document.querySelectorAll('.nav-link.has-dropdown');

    if (!sidebarMenu || !sidebarFooter) return;

    function adjustMenuHeight() {
        var isMini       = document.body.classList.contains('sidebar-mini');
        var sidebarBrand = document.querySelector('.sidebar-brand');
        var headerHeight = sidebarBrand ? sidebarBrand.offsetHeight : (isMini ? 60 : 80);
        var windowHeight  = window.innerHeight;
        var footerHeight  = sidebarFooter.offsetHeight;
        var maxMenuHeight = windowHeight - footerHeight - headerHeight - 16;

        sidebarMenu.style.maxHeight = Math.max(maxMenuHeight, 150) + 'px';

        requestAnimationFrame(function() {
            if (sidebarMenu.scrollHeight > sidebarMenu.clientHeight) {
                sidebarMenu.style.overflowY = 'auto';
            } else {
                sidebarMenu.style.overflowY = 'visible';
            }
        });
    }

    function debounce(fn, delay) {
        var timer;
        return function() {
            clearTimeout(timer);
            timer = setTimeout(fn, delay);
        };
    }
    var debouncedAdjust = debounce(adjustMenuHeight, 80);

    function setupDropdownListeners() {
        dropdownToggles.forEach(function(toggle) {
            toggle.addEventListener('click', function() {
                setTimeout(function() {
                    adjustMenuHeight();

                    var parentLi = toggle.closest('li');
                    if (parentLi && parentLi.classList.contains('show')) {
                        var dropdownMenu = parentLi.querySelector('.dropdown-menu');
                        if (dropdownMenu) {
                            var rect       = parentLi.getBoundingClientRect();
                            var footerRect = sidebarFooter.getBoundingClientRect();

                            if (rect.bottom + dropdownMenu.offsetHeight > footerRect.top - 10) {
                                sidebarMenu.scrollTop += dropdownMenu.offsetHeight + 10;
                            }
                        }
                    }
                }, 320);
            });
        });
    }

    var bodyObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                setTimeout(adjustMenuHeight, 350);
            }
        });
    });

    bodyObserver.observe(document.body, {
        attributes: true,
        attributeFilter: ['class']
    });

    var sidebarToggle = document.querySelector('[data-toggle="sidebar"]');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            setTimeout(adjustMenuHeight, 350);
        });
    }

    window.addEventListener('resize', debouncedAdjust);

    adjustMenuHeight();
    setupDropdownListeners();
});
</script>
