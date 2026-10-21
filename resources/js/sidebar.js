/**
 * Sidebar collapsible behaviour
 * Persists state in localStorage. Applies data-sidebar-collapsed attribute to .app-sidebar.
 * Also manages mobile overlay drawer.
 */
(function () {
    const STORAGE_KEY = 'eficaz_sidebar_collapsed';
    const MOBILE_BREAKPOINT = 1024;

    const sidebar = document.querySelector('.app-sidebar');
    const toggleBtn = document.getElementById('sidebar-toggle');
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const overlay = document.getElementById('sidebar-overlay');

    if (!sidebar) return;

    // ── Desktop collapsed state ─────────────────────────────────────────────
    function isCollapsed() {
        try {
            return localStorage.getItem(STORAGE_KEY) === '1';
        } catch (error) {
            return false;
        }
    }

    function applyCollapsed(collapsed) {
        if (collapsed) {
            sidebar.setAttribute('data-collapsed', '');
            document.documentElement.setAttribute('data-sidebar-collapsed', '');
        } else {
            sidebar.removeAttribute('data-collapsed');
            document.documentElement.removeAttribute('data-sidebar-collapsed');
        }
        if (toggleBtn) {
            toggleBtn.setAttribute('aria-pressed', String(collapsed));
            toggleBtn.setAttribute('aria-label', collapsed ? 'Expandir menu' : 'Recolher menu');
        }
    }

    function toggleCollapsed() {
        const next = !isCollapsed();
        try {
            localStorage.setItem(STORAGE_KEY, next ? '1' : '0');
        } catch (error) {
            // The visual state still works when browser storage is unavailable.
        }
        applyCollapsed(next);
    }

    // Initialise on load (desktop only)
    if (window.innerWidth >= MOBILE_BREAKPOINT) {
        applyCollapsed(isCollapsed());
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (window.innerWidth < MOBILE_BREAKPOINT) {
                closeMobileMenu();
            } else {
                toggleCollapsed();
            }
        });
    }

    // ── Mobile overlay drawer ───────────────────────────────────────────────
    function openMobileMenu() {
        sidebar.setAttribute('data-mobile-open', '');
        sidebar.setAttribute('aria-hidden', 'false');
        if (overlay) {
            overlay.classList.add('is-visible');
            overlay.setAttribute('aria-hidden', 'false');
        }
        if (mobileMenuBtn) mobileMenuBtn.setAttribute('aria-expanded', 'true');
        if (toggleBtn) toggleBtn.setAttribute('aria-label', 'Fechar menu');
        document.body.style.overflow = 'hidden';
    }

    function closeMobileMenu() {
        sidebar.removeAttribute('data-mobile-open');
        if (window.innerWidth < MOBILE_BREAKPOINT) {
            sidebar.setAttribute('aria-hidden', 'true');
        } else {
            sidebar.removeAttribute('aria-hidden');
        }
        if (overlay) {
            overlay.classList.remove('is-visible');
            overlay.setAttribute('aria-hidden', 'true');
        }
        if (mobileMenuBtn) mobileMenuBtn.setAttribute('aria-expanded', 'false');
        if (toggleBtn && window.innerWidth < MOBILE_BREAKPOINT) toggleBtn.setAttribute('aria-label', 'Abrir menu');
        document.body.style.overflow = '';
    }

    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function () {
            if (sidebar.hasAttribute('data-mobile-open')) {
                closeMobileMenu();
            } else {
                openMobileMenu();
            }
        });
    }

    if (overlay) {
        overlay.addEventListener('click', closeMobileMenu);
    }

    sidebar.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
            if (window.innerWidth < MOBILE_BREAKPOINT) closeMobileMenu();
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && sidebar.hasAttribute('data-mobile-open')) {
            closeMobileMenu();
            if (mobileMenuBtn) mobileMenuBtn.focus();
        }
    });

    if (window.innerWidth < MOBILE_BREAKPOINT) {
        closeMobileMenu();
    }

    // Close mobile menu on resize to desktop
    window.addEventListener('resize', function () {
        if (window.innerWidth >= MOBILE_BREAKPOINT) {
            closeMobileMenu();
            applyCollapsed(isCollapsed());
        } else if (!sidebar.hasAttribute('data-mobile-open')) {
            sidebar.setAttribute('aria-hidden', 'true');
        }
    });
})();
