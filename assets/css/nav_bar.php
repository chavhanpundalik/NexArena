<!-- =========================================================
     NEXARENA NAVBAR – FULLY RESPONSIVE
     Theme: White + Black + Orange (with Dark Mode Support)
     Works on all screen sizes with hamburger menu
========================================================= -->

<style>

    /* =========================================================
       GLOBAL SMOOTH SCROLL
    ========================================================= */

    html {
        scroll-behavior: smooth;
    }


    /* =========================================================
       SITE HEADER
    ========================================================= */

    .site-header {
        display: flex;
        justify-content: center;
        align-items: center;

        position: sticky;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1000;

        width: 100%;
        height: 70px;

        background: rgba(255, 255, 255, 0.98);

        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);

        border-bottom: 1px solid rgba(0, 0, 0, 0.06);

        box-shadow: 0 2px 20px rgba(0, 0, 0, 0.04);

        transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }


    /* Header when page is scrolled */

    .site-header.scrolled {
        background: rgba(255, 255, 255, 0.99);
        box-shadow: 0 4px 40px rgba(0, 0, 0, 0.08);
        height: 62px;
    }


    /* =========================================================
       DARK MODE HEADER
    ========================================================= */

    [data-theme="dark"] .site-header {
        background: rgba(18, 18, 34, 0.98) !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06) !important;
        box-shadow: 0 2px 20px rgba(0, 0, 0, 0.3) !important;
    }

    [data-theme="dark"] .site-header.scrolled {
        background: rgba(18, 18, 34, 0.99) !important;
        box-shadow: 0 4px 40px rgba(0, 0, 0, 0.4) !important;
    }


    /* =========================================================
       NAV CONTAINER
    ========================================================= */

    .nav-container {
        width: 100%;
        max-width: 1280px;

        min-height: 60px;

        margin: 0 auto;
        padding: 0 16px;

        display: flex;
        align-items: center;
        justify-content: space-between;

        gap: 12px;

        position: relative;
    }


    /* =========================================================
       BRAND / LOGO - TEXT LOGO
    ========================================================= */

    .brand {
        display: flex;
        align-items: center;

        flex-shrink: 0;

        text-decoration: none;
        z-index: 1003;
    }

    /* Text Logo */
    .brand .logo-text {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 24px;
        font-weight: 900;
        letter-spacing: -0.5px;
        display: flex;
        align-items: center;
        gap: 1px;
        transition: color 0.3s ease;
    }

    .brand .logo-text .nex {
        color: #1a1a2e;
        transition: color 0.3s ease;
    }

    .brand .logo-text .arena {
        color: #f97316;
        transition: color 0.3s ease;
    }

    /* Dark Mode Logo Colors */
    [data-theme="dark"] .brand .logo-text .nex {
        color: #ffffff !important;
    }

    [data-theme="dark"] .brand .logo-text .arena {
        color: #f97316 !important;
    }


    /* =========================================================
       MAIN NAVIGATION - DESKTOP
    ========================================================= */

    .main-nav {
        display: flex;
        align-items: center;
        justify-content: center;

        gap: 4px;

        flex: 1;

        margin: 0 8px;
    }


    .main-nav > a {
        position: relative;

        padding: 6px 14px;
        margin: 0 2px;

        color: #222222;

        font-size: 14px;
        font-weight: 600;

        letter-spacing: 0.2px;

        text-decoration: none;

        border-radius: 8px;

        white-space: nowrap;

        transition: all 0.25s cubic-bezier(0.25, 0.46, 0.45, 0.94);

        cursor: pointer;
    }


    /* =========================================================
       DARK MODE NAV LINKS
    ========================================================= */

    [data-theme="dark"] .main-nav > a {
        color: #e2e8f0 !important;
    }


    [data-theme="dark"] .main-nav > a:hover {
        color: #f97316 !important;
        background: rgba(249, 115, 22, 0.08) !important;
    }


    [data-theme="dark"] .main-nav > a.active {
        color: #f97316 !important;
    }


    /* =========================================================
       NAV UNDERLINE
    ========================================================= */

    .main-nav > a::after {
        content: "";

        position: absolute;

        left: 50%;
        bottom: 2px;

        width: 0;
        height: 2.5px;

        background: #f97316;

        border-radius: 4px;

        transform: translateX(-50%);

        transition: width 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }


    /* Hover */

    .main-nav > a:hover {
        color: #f97316;
        background: rgba(249, 115, 22, 0.06);
    }


    .main-nav > a:hover::after {
        width: 50%;
    }


    /* Active */

    .main-nav > a.active {
        color: #f97316;
        background: rgba(249, 115, 22, 0.06);
    }


    .main-nav > a.active::after {
        width: 50%;
    }


    /* =========================================================
       NAV ACTIONS - DESKTOP
    ========================================================= */

    .nav-actions {
        display: flex;
        align-items: center;

        gap: 8px;

        flex-shrink: 0;
        z-index: 1003;
    }


    /* =========================================================
       ATTRACTIVE DARK MODE TOGGLE BUTTON
    ========================================================= */

    .dark-mode-toggle {
        display: flex;
        align-items: center;
        justify-content: center;

        width: 38px;
        height: 38px;

        border: 2px solid #e2e8f0;
        border-radius: 50%;

        background: #ffffff;

        cursor: pointer;

        font-size: 16px;

        flex-shrink: 0;

        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        
        position: relative;
        overflow: hidden;
    }

    /* Glow effect on hover */
    .dark-mode-toggle::before {
        content: '';
        position: absolute;
        inset: -2px;
        border-radius: 50%;
        background: linear-gradient(135deg, #f97316, #fb923c, #f97316);
        opacity: 0;
        transition: opacity 0.4s ease;
        z-index: 0;
    }

    .dark-mode-toggle:hover::before {
        opacity: 1;
    }

    .dark-mode-toggle:hover {
        border-color: transparent;
        transform: scale(1.08);
        box-shadow: 0 0 30px rgba(249, 115, 22, 0.3);
    }

    .dark-mode-toggle:active {
        transform: scale(0.92);
    }

    /* Icon inside toggle - positioned above glow */
    .dark-mode-toggle #darkModeIcon {
        position: relative;
        z-index: 1;
        line-height: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .dark-mode-toggle:hover #darkModeIcon {
        transform: rotate(20deg) scale(1.1);
    }

    /* Dark mode toggle in dark mode */
    [data-theme="dark"] .dark-mode-toggle {
        background: #1a1a2e !important;
        border-color: #3d3d5c !important;
        color: #fbbf24 !important;
    }

    [data-theme="dark"] .dark-mode-toggle::before {
        background: linear-gradient(135deg, #fbbf24, #f59e0b, #fbbf24) !important;
    }

    [data-theme="dark"] .dark-mode-toggle:hover {
        border-color: transparent !important;
        box-shadow: 0 0 30px rgba(251, 191, 36, 0.3) !important;
    }

    [data-theme="dark"] .dark-mode-toggle:hover #darkModeIcon {
        transform: rotate(-20deg) scale(1.1);
    }


    /* =========================================================
       LOGIN + REGISTER - DESKTOP
    ========================================================= */

    .nav-login,
    .nav-register {
        display: inline-flex;
        align-items: center;
        justify-content: center;

        min-height: 34px;

        padding: 6px 16px;

        border-radius: 8px;

        font-size: 13px;
        font-weight: 700;

        letter-spacing: 0.2px;

        text-decoration: none;

        white-space: nowrap;

        transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }


    /* Login */

    .nav-login {
        color: #222222;
        background: transparent;
        border: 1.5px solid #e0e0e0;
    }


    .nav-login:hover {
        color: #f97316;
        background: rgba(249, 115, 22, 0.06);
        border-color: #f97316;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(249, 115, 22, 0.12);
    }


    /* Register */

    .nav-register {
        color: #ffffff;
        background: #f97316;
        border: 1.5px solid #f97316;
        box-shadow: 0 2px 12px rgba(249, 115, 22, 0.20);
    }


    .nav-register:hover {
        color: #ffffff;
        background: #ea580c;
        border-color: #ea580c;
        transform: translateY(-2px);
        box-shadow: 0 6px 25px rgba(249, 115, 22, 0.30);
    }


    /* =========================================================
       DARK MODE LOGIN + REGISTER
    ========================================================= */

    [data-theme="dark"] .nav-login {
        color: #e2e8f0 !important;
        background: transparent !important;
        border-color: #2d2d44 !important;
    }


    [data-theme="dark"] .nav-login:hover {
        color: #f97316 !important;
        background: rgba(249, 115, 22, 0.08) !important;
        border-color: #f97316 !important;
    }


    [data-theme="dark"] .nav-register {
        color: #ffffff !important;
        background: #f97316 !important;
        border-color: #f97316 !important;
    }


    [data-theme="dark"] .nav-register:hover {
        background: #ea580c !important;
        border-color: #ea580c !important;
    }


    /* =========================================================
       MOBILE MENU BUTTON - HAMBURGER
    ========================================================= */

    .menu-toggle {
        display: none;

        flex-direction: column;
        align-items: center;
        justify-content: center;

        width: 40px;
        height: 40px;

        padding: 0;

        border: 1.5px solid #e8e8e8;

        border-radius: 8px;

        background: #ffffff;

        cursor: pointer;

        transition: all 0.3s ease;

        gap: 4px;

        flex-shrink: 0;

        z-index: 1003;
    }


    .menu-toggle:hover {
        border-color: #f97316;
        background: rgba(249, 115, 22, 0.04);
    }


    .menu-toggle span {
        display: block;

        width: 20px;
        height: 2.5px;

        background: #222222;

        border-radius: 4px;

        transition: all 0.3s ease;

        transform-origin: center;
    }


    /* =========================================================
       DARK MODE MOBILE MENU BUTTON
    ========================================================= */

    [data-theme="dark"] .menu-toggle {
        background: #1a1a2e !important;
        border-color: #2d2d44 !important;
    }


    [data-theme="dark"] .menu-toggle span {
        background: #e2e8f0 !important;
    }


    [data-theme="dark"] .menu-toggle:hover {
        border-color: #f97316 !important;
        background: rgba(249, 115, 22, 0.08) !important;
    }


    /* Hamburger → X */
    .menu-toggle.active span:nth-child(1) {
        transform: rotate(45deg) translate(5px, 5px);
    }

    .menu-toggle.active span:nth-child(2) {
        opacity: 0;
        transform: scaleX(0);
    }

    .menu-toggle.active span:nth-child(3) {
        transform: rotate(-45deg) translate(5px, -5px);
    }


    /* =========================================================
       MOBILE ACTIONS
    ========================================================= */

    .mobile-nav-actions {
        display: none;
    }


    /* =========================================================
       SCROLL OFFSET FOR HASH LINKS
    ========================================================= */

    #home,
    #sports,
    #events,
    #about,
    #contact {
        scroll-margin-top: 90px;
    }


    /* =========================================================
       RESPONSIVE - TABLET / MOBILE
    ========================================================= */

    @media (max-width: 992px) {

        .site-header {
            height: 64px;
        }

        .site-header.scrolled {
            height: 58px;
        }

        .nav-container {
            padding: 0 14px;
            min-height: 56px;
        }


        /* Logo - Smaller on tablet */
        .brand .logo-text {
            font-size: 20px;
            letter-spacing: -0.3px;
        }


        /* Hide desktop actions */

        .nav-actions {
            display: none;
        }


        /* Show hamburger */

        .menu-toggle {
            display: flex;
        }


        /* =====================================================
           MOBILE NAV - FULL SCREEN OVERLAY
        ===================================================== */

        .main-nav {
            position: fixed;

            top: 0;
            left: 0;
            right: 0;
            bottom: 0;

            width: 100%;
            height: 100vh;

            display: flex;

            flex-direction: column;

            align-items: center;
            justify-content: center;

            gap: 8px;

            padding: 80px 20px 40px;

            background: rgba(255, 255, 255, 0.98);

            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);

            opacity: 0;

            visibility: hidden;

            pointer-events: none;

            transform: scale(0.95);

            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);

            z-index: 1001;

            margin: 0;

            flex: none;

            overflow-y: auto;
        }


        /* =====================================================
           DARK MODE MOBILE NAV
        ===================================================== */

        [data-theme="dark"] .main-nav {
            background: rgba(18, 18, 34, 0.98) !important;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }


        /* Open mobile menu */
        .main-nav.active {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
            transform: scale(1);
        }


        /* Mobile links */
        .main-nav > a {
            width: 100%;
            max-width: 400px;

            padding: 14px 20px;

            margin: 4px 0;

            border-radius: 12px;

            color: #222222;

            font-size: 18px;
            font-weight: 600;

            text-align: center;

            white-space: normal;

            border: 1px solid transparent;

            transition: all 0.3s ease;
        }


        /* Remove underline on mobile */
        .main-nav > a::after {
            display: none;
        }


        .main-nav > a:hover {
            color: #f97316;
            background: rgba(249, 115, 22, 0.06);
            border-color: rgba(249, 115, 22, 0.1);
        }


        .main-nav > a.active {
            color: #f97316;
            background: rgba(249, 115, 22, 0.08);
            border-color: #f97316;
        }


        /* =====================================================
           DARK MODE MOBILE LINKS
        ===================================================== */

        [data-theme="dark"] .main-nav > a {
            color: #e2e8f0 !important;
        }


        [data-theme="dark"] .main-nav > a:hover {
            color: #f97316 !important;
            background: rgba(249, 115, 22, 0.08) !important;
            border-color: rgba(249, 115, 22, 0.15) !important;
        }


        [data-theme="dark"] .main-nav > a.active {
            color: #f97316 !important;
            background: rgba(249, 115, 22, 0.12) !important;
            border-color: #f97316 !important;
        }


        /* =====================================================
           MOBILE LOGIN / REGISTER
        ===================================================== */

        .mobile-nav-actions {
            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 12px;

            margin-top: 20px;

            padding-top: 20px;

            border-top: 1.5px solid #f0f0f0;

            width: 100%;
            max-width: 400px;
        }


        /* =====================================================
           DARK MODE MOBILE ACTIONS DIVIDER
        ===================================================== */

        [data-theme="dark"] .mobile-nav-actions {
            border-top-color: rgba(255, 255, 255, 0.08) !important;
        }


        .mobile-nav-actions .nav-login,
        .mobile-nav-actions .nav-register {
            width: 100%;

            min-height: 44px;

            border-radius: 10px;

            font-size: 15px;

            justify-content: center;
        }


        .mobile-nav-actions .nav-login {
            border-color: #e0e0e0;
        }


        [data-theme="dark"] .mobile-nav-actions .nav-login {
            border-color: rgba(255, 255, 255, 0.1) !important;
            color: #e2e8f0 !important;
        }


        [data-theme="dark"] .mobile-nav-actions .nav-login:hover {
            color: #f97316 !important;
            border-color: #f97316 !important;
            background: rgba(249, 115, 22, 0.08) !important;
        }

    }


    /* =========================================================
       SMALL MOBILE
    ========================================================= */

    @media (max-width: 480px) {

        .site-header {
            height: 56px;
        }

        .site-header.scrolled {
            height: 52px;
        }

        .nav-container {
            padding: 0 10px;
            min-height: 52px;
            gap: 6px;
        }


        .brand .logo-text {
            font-size: 17px;
            letter-spacing: -0.2px;
        }


        .menu-toggle {
            width: 36px;
            height: 36px;
            border-radius: 6px;
            gap: 3px;
        }


        .menu-toggle span {
            width: 16px;
            height: 2px;
        }


        .menu-toggle.active span:nth-child(1) {
            transform: rotate(45deg) translate(4px, 4px);
        }


        .menu-toggle.active span:nth-child(3) {
            transform: rotate(-45deg) translate(4px, -4px);
        }


        .main-nav > a {
            font-size: 16px;
            padding: 12px 16px;
        }


        .mobile-nav-actions {
            grid-template-columns: 1fr;
            gap: 8px;
        }


        .mobile-nav-actions .nav-login,
        .mobile-nav-actions .nav-register {
            min-height: 40px;
            font-size: 14px;
        }


        #home,
        #sports,
        #events,
        #about,
        #contact {
            scroll-margin-top: 80px;
        }

    }


    /* =========================================================
       VERY SMALL PHONES
    ========================================================= */

    @media (max-width: 360px) {

        .brand .logo-text {
            font-size: 15px;
        }


        .main-nav > a {
            padding: 10px 14px;
            font-size: 15px;
        }


        .nav-container {
            padding: 0 8px;
        }

    }

</style>


<!-- =========================================================
     NAVBAR HTML
========================================================= -->

<header class="site-header" id="siteHeader">

    <div class="nav-container">


        <!-- =====================================================
             LOGO - TEXT LOGO
        ====================================================== -->

        <a href="index.php" class="brand">

            <div class="logo-text">
                <span class="nex">Nex</span><span class="arena">Arena</span>
            </div>

        </a>


        <!-- =====================================================
             MOBILE MENU BUTTON - HAMBURGER
        ====================================================== -->

        <button class="menu-toggle" id="menuToggle" type="button" aria-label="Open menu" aria-expanded="false">

            <span></span>
            <span></span>
            <span></span>

        </button>


        <!-- =====================================================
             MAIN NAVIGATION
        ====================================================== -->

        <nav class="main-nav" id="mainNav" aria-label="Main Navigation">

            <!-- Home -->
            <a href="index.php">
                Home
            </a>


            <!-- Sports -->
            <a href="index.php#sports">
                Sports
            </a>


            <!-- Events -->
            <a href="index.php#events">
                Events
            </a>


            <!-- About -->
            <a href="index.php#about">
                About
            </a>


            <!-- Contact -->
            <a href="index.php#contact">
                Contact
            </a>


            <!-- =================================================
                 MOBILE LOGIN / REGISTER
            ================================================== -->

            <div class="mobile-nav-actions">

                <a href="login.php" class="nav-login">
                    Login
                </a>


                <a href="registration.php" class="nav-register">
                    Register
                </a>

            </div>

        </nav>


        <!-- =====================================================
             DESKTOP LOGIN / REGISTER + ATTRACTIVE DARK MODE TOGGLE
        ====================================================== -->

        <div class="nav-actions">

            <!-- =================================================
                 ATTRACTIVE DARK MODE TOGGLE BUTTON
            ================================================== -->

            <button class="dark-mode-toggle" id="darkModeToggle" type="button" aria-label="Toggle Dark Mode" title="Toggle Dark Mode">

                <span id="darkModeIcon">🌙</span>

            </button>


            <a href="login.php" class="nav-login">
                Login
            </a>


            <a href="registration.php" class="nav-register">
                Register
            </a>

        </div>

    </div>

</header>


<!-- =========================================================
     NAVBAR JAVASCRIPT - SIMPLIFIED & WORKING
========================================================= -->

<script>
    (function() {

        // Get elements
        const menuToggle = document.getElementById('menuToggle');
        const mainNav = document.getElementById('mainNav');
        const siteHeader = document.getElementById('siteHeader');

        // Make sure elements exist
        if (!menuToggle || !mainNav) {
            console.error('Navbar elements not found!');
            return;
        }

        // Menu toggle function
        function toggleMenu() {
            const isOpen = mainNav.classList.toggle('active');
            menuToggle.classList.toggle('active');

            // Update ARIA attributes
            menuToggle.setAttribute('aria-expanded', isOpen);
            menuToggle.setAttribute('aria-label', isOpen ? 'Close menu' : 'Open menu');

            // Prevent body scroll
            document.body.style.overflow = isOpen ? 'hidden' : '';
        }

        // Close menu function
        function closeMenu() {
            mainNav.classList.remove('active');
            menuToggle.classList.remove('active');
            menuToggle.setAttribute('aria-expanded', 'false');
            menuToggle.setAttribute('aria-label', 'Open menu');
            document.body.style.overflow = '';
        }

        // Event: Toggle button click
        menuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleMenu();
        });

        // Event: Click on nav links
        document.querySelectorAll('#mainNav a').forEach(function(link) {
            link.addEventListener('click', function() {
                closeMenu();

                // Handle hash links
                const href = this.getAttribute('href');
                if (href && href.includes('#') && !href.includes('login.php') && !href.includes('registration.php')) {
                    const hash = href.split('#')[1];
                    if (hash) {
                        const target = document.getElementById(hash);
                        if (target) {
                            setTimeout(function() {
                                const headerOffset = 90;
                                const elementPosition = target.getBoundingClientRect().top;
                                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                                window.scrollTo({ top: offsetPosition, behavior: 'smooth' });
                            }, 300);
                        }
                    }
                }
            });
        });

        // Event: Click outside to close
        document.addEventListener('click', function(event) {
            if (!mainNav.contains(event.target) && !menuToggle.contains(event.target)) {
                closeMenu();
            }
        });

        // Event: ESC key to close
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeMenu();
            }
        });

        // Event: Resize to desktop
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                if (window.innerWidth > 992) {
                    closeMenu();
                }
            }, 250);
        });


        // =========================================================
        // HEADER SCROLL EFFECT
        // =========================================================

        if (siteHeader) {
            window.addEventListener('scroll', function() {
                if (window.scrollY > 20) {
                    siteHeader.classList.add('scrolled');
                } else {
                    siteHeader.classList.remove('scrolled');
                }
            }, { passive: true });
        }


        // =========================================================
        // ACTIVE NAV LINK
        // =========================================================

        function updateActiveLinks() {
            const currentPage = window.location.pathname.split('/').pop() || 'index.php';
            const currentHash = window.location.hash;

            document.querySelectorAll('#mainNav a').forEach(function(link) {
                const href = link.getAttribute('href');
                if (!href) return;

                // Skip login/register
                if (href.includes('login.php') || href.includes('registration.php')) {
                    return;
                }

                link.classList.remove('active');

                // Check for exact match
                if (href === currentPage || (href === 'index.php' && currentPage === 'index.php')) {
                    link.classList.add('active');
                }

                // Check for hash match
                if (currentHash && href === 'index.php' + currentHash) {
                    link.classList.add('active');
                }

                // Check if href contains hash and matches current hash
                if (href.includes('#') && href.split('#')[1] === currentHash.replace('#', '')) {
                    link.classList.add('active');
                }
            });
        }

        updateActiveLinks();
        window.addEventListener('hashchange', updateActiveLinks);


        // =========================================================
        // DARK MODE TOGGLE
        // =========================================================

        const darkModeToggle = document.getElementById('darkModeToggle');
        const darkModeIcon = document.getElementById('darkModeIcon');
        const html = document.documentElement;

        if (darkModeToggle) {
            const icons = { light: '🌙', dark: '☀️' };

            function applyTheme(theme) {
                if (theme === 'dark') {
                    html.setAttribute('data-theme', 'dark');
                    if (darkModeIcon) darkModeIcon.textContent = icons.dark;
                } else {
                    html.removeAttribute('data-theme');
                    if (darkModeIcon) darkModeIcon.textContent = icons.light;
                }
                localStorage.setItem('nexarena_theme', theme);
            }

            // Load saved theme
            const savedTheme = localStorage.getItem('nexarena_theme');
            if (savedTheme) {
                applyTheme(savedTheme);
            } else {
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                applyTheme(prefersDark ? 'dark' : 'light');
            }

            darkModeToggle.addEventListener('click', function() {
                const currentTheme = html.getAttribute('data-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                applyTheme(newTheme);

                if (window.ThemeManager) {
                    window.ThemeManager.toggleTheme();
                }
            });
        }

        console.log('Navbar loaded successfully!');
        console.log('Screen width:', window.innerWidth);

    })();
</script>