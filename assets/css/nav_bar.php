<!-- =========================================================
     NEXARENA NAVBAR – SELF CONTAINED
     Theme: White + Black + Orange (with Dark Mode Support)
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
        z-index: 1000;

        width: 100%;
        height: 100px;

        background: rgba(255, 255, 255, 0.98);

        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);

        border-bottom: 1px solid rgba(0, 0, 0, 0.06);

        box-shadow: 0 2px 20px rgba(0, 0, 0, 0.04);

        transition: all 0.3s ease;
    }


    /* Header when page is scrolled */

    .site-header.scrolled {
        background: rgba(255, 255, 255, 0.99);
        box-shadow: 0 4px 40px rgba(0, 0, 0, 0.08);
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

        min-height: 72px;

        margin: 0 auto;
        padding: 0 20px;

        display: flex;
        align-items: center;
        justify-content: space-between;

        gap: 20px;

        position: relative;
    }


    /* =========================================================
       BRAND / LOGO
    ========================================================= */

    .brand {
        display: flex;
        align-items: center;

        flex-shrink: 0;

        text-decoration: none;

        margin-right: 10px;
    }


    .brand img {
        display: block;

        height: 120px;
        width: auto;

        margin-right: 50px;

        object-fit: contain;

        transition: transform 0.3s ease;
    }


    .brand:hover img {
        transform: scale(1.03);
    }

/* =========================================================
   LOGO - Dark Mode Support
========================================================= */

/* Default logo (for light mode) */
.brand img {
    display: block;
    height: 120px;
    width: auto;
    margin-right: 50px;
    object-fit: contain;
    transition: transform 0.3s ease;
}

/* Dark mode logo - Fully Orange */
[data-theme="dark"] .brand img {
    filter: brightness(0) invert(1) sepia(1) saturate(100) hue-rotate(0deg) brightness(1.2) !important;
}
    /* =========================================================
       MAIN NAVIGATION
    ========================================================= */

    .main-nav {
        display: flex;
        align-items: center;
        justify-content: center;

        gap: 0;

        flex: 1;

        margin: 0 10px;
    }


    .main-nav > a {
        position: relative;

        padding: 8px 18px;
        margin: 0 4px;

        color: #222222;

        font-size: 14px;
        font-weight: 600;

        letter-spacing: 0.3px;

        text-decoration: none;

        border-radius: 8px;

        white-space: nowrap;

        transition: all 0.25s ease;
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
        bottom: 4px;

        width: 0;
        height: 2.5px;

        background: #ff7600;

        border-radius: 4px;

        transform: translateX(-50%);

        transition: width 0.3s ease;
    }


    /* Hover */

    .main-nav > a:hover {
        color: #ff7600;
        background: rgba(255, 118, 0, 0.06);
    }


    .main-nav > a:hover::after {
        width: 60%;
    }


    /* Active */

    .main-nav > a.active {
        color: #ff7600;
    }


    .main-nav > a.active::after {
        width: 60%;
    }


    /* =========================================================
       NAV ACTIONS
    ========================================================= */

    .nav-actions {
        display: flex;
        align-items: center;

        gap: 10px;

        flex-shrink: 0;

        margin-left: 5px;
    }


    /* =========================================================
       DARK MODE TOGGLE BUTTON
    ========================================================= */

    .dark-mode-toggle {
        display: flex;
        align-items: center;
        justify-content: center;

        width: 40px;
        height: 40px;

        border: 1.5px solid #e0e0e0;
        border-radius: 10px;

        background: #ffffff;

        cursor: pointer;

        font-size: 18px;

        flex-shrink: 0;

        transition: all 0.3s ease;
    }


    .dark-mode-toggle:hover {
        border-color: #ff7600;
        background: rgba(255, 118, 0, 0.04);
        transform: scale(1.05);
    }


    .dark-mode-toggle #darkModeIcon {
        line-height: 1;
    }


    /* Dark mode toggle in dark mode */

    [data-theme="dark"] .dark-mode-toggle {
        background: #1a1a2e !important;
        border-color: #2d2d44 !important;
        color: #e2e8f0 !important;
    }


    [data-theme="dark"] .dark-mode-toggle:hover {
        border-color: #ff7600 !important;
        background: rgba(249, 115, 22, 0.08) !important;
    }


    /* =========================================================
       LOGIN + REGISTER
    ========================================================= */

    .nav-login,
    .nav-register {
        display: inline-flex;
        align-items: center;
        justify-content: center;

        min-height: 38px;

        padding: 8px 20px;

        border-radius: 10px;

        font-size: 13px;
        font-weight: 700;

        letter-spacing: 0.3px;

        text-decoration: none;

        white-space: nowrap;

        transition: all 0.3s ease;
    }


    /* Login */

    .nav-login {
        color: #222222;
        background: transparent;
        border: 1.5px solid #e0e0e0;
    }


    .nav-login:hover {
        color: #ff7600;
        background: rgba(255, 118, 0, 0.06);
        border-color: #ff7600;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(255, 118, 0, 0.12);
    }


    /* Register */

    .nav-register {
        color: #ffffff;
        background: #ff7600;
        border: 1.5px solid #ff7600;
        box-shadow: 0 2px 12px rgba(255, 118, 0, 0.20);
    }


    .nav-register:hover {
        color: #ffffff;
        background: #e45d00;
        border-color: #e45d00;
        transform: translateY(-2px);
        box-shadow: 0 6px 25px rgba(255, 118, 0, 0.30);
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
       MOBILE MENU BUTTON
    ========================================================= */

    .menu-toggle {
        display: none;

        flex-direction: column;
        align-items: center;
        justify-content: center;

        width: 44px;
        height: 44px;

        padding: 0;

        border: 1.5px solid #e8e8e8;

        border-radius: 10px;

        background: #ffffff;

        cursor: pointer;

        transition: all 0.3s ease;

        gap: 5px;

        flex-shrink: 0;
    }


    .menu-toggle:hover {
        border-color: #ff7600;
        background: rgba(255, 118, 0, 0.04);
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
       EVENT / ABOUT / CONTACT SCROLL OFFSET
       
       This prevents the sticky navbar from covering
       the section heading after clicking navbar links.
    ========================================================= */

    #events,
    #about,
    #contact {
        scroll-margin-top: 105px;
    }


    /* =========================================================
       TABLET / MOBILE
    ========================================================= */

    @media (max-width: 820px) {

        .site-header {
            height: 80px;
        }


        .nav-container {
            padding: 0 16px;
            min-height: 70px;
        }


        /* Logo */

        .brand img {
            height: 90px;
            margin-right: 0;
        }


        /* Hide desktop actions */

        .nav-actions {
            display: none;
        }


        /* Show hamburger */

        .menu-toggle {
            display: flex;
            position: relative;
            z-index: 1002;
        }


        /* =====================================================
           MOBILE NAV
        ===================================================== */

        .main-nav {
            position: absolute;

            top: calc(100% + 8px);

            left: 16px;
            right: 16px;

            width: auto;

            display: flex;

            flex-direction: column;

            align-items: stretch;

            gap: 0;

            padding: 12px 14px;

            background: #ffffff;

            border: 1px solid #eeeeee;

            border-radius: 16px;

            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.12);

            opacity: 0;

            visibility: hidden;

            pointer-events: none;

            transform: translateY(-12px) scale(0.97);

            transition: all 0.3s ease;

            z-index: 1001;

            margin: 0;

            flex: none;
        }


        /* =====================================================
           DARK MODE MOBILE NAV
        ===================================================== */

        [data-theme="dark"] .main-nav {
            background: #1a1a2e !important;
            border-color: #2d2d44 !important;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5) !important;
        }


        /* Open mobile menu */

        .main-nav.active {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
            transform: translateY(0) scale(1);
        }


        /* Mobile links */

        .main-nav > a {
            width: 100%;

            padding: 12px 16px;

            margin: 1px 0;

            border-radius: 10px;

            color: #222222;

            font-size: 15px;

            font-weight: 600;

            text-align: left;
        }


        /* Remove underline on mobile */

        .main-nav > a::after {
            display: none;
        }


        .main-nav > a:hover {
            color: #ff7600;
            background: rgba(255, 118, 0, 0.06);
        }


        .main-nav > a.active {
            color: #ff7600;
            background: rgba(255, 118, 0, 0.06);
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
        }


        [data-theme="dark"] .main-nav > a.active {
            color: #f97316 !important;
            background: rgba(249, 115, 22, 0.08) !important;
        }


        /* =====================================================
           MOBILE LOGIN / REGISTER
        ===================================================== */

        .mobile-nav-actions {
            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 10px;

            margin-top: 10px;

            padding-top: 14px;

            border-top: 1.5px solid #f0f0f0;
        }


        /* =====================================================
           DARK MODE MOBILE ACTIONS DIVIDER
        ===================================================== */

        [data-theme="dark"] .mobile-nav-actions {
            border-top-color: #2d2d44 !important;
        }


        .mobile-nav-actions .nav-login,
        .mobile-nav-actions .nav-register {
            width: 100%;

            min-height: 44px;

            border-radius: 10px;

            font-size: 13px;

            justify-content: center;
        }


        .mobile-nav-actions .nav-login {
            border-color: #e0e0e0;
        }


        [data-theme="dark"] .mobile-nav-actions .nav-login {
            border-color: #2d2d44 !important;
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
            height: 72px;
        }


        .nav-container {
            padding: 0 12px;
            min-height: 60px;
        }


        .brand img {
            height: 82px;
        }


        .menu-toggle {
            width: 40px;
            height: 40px;
            border-radius: 8px;
        }


        .menu-toggle span {
            width: 18px;
            height: 2px;
        }


        .main-nav {
            left: 12px;
            right: 12px;

            padding: 10px 12px;

            border-radius: 14px;
        }


        .main-nav > a {
            padding: 10px 14px;
            font-size: 14px;
        }


        .mobile-nav-actions {
            grid-template-columns: 1fr;
            gap: 8px;
        }


        #events,
        #about,
        #contact {
            scroll-margin-top: 85px;
        }

    }

</style>


<!-- =========================================================
     NAVBAR HTML
========================================================= -->

<header class="site-header" id="siteHeader">

    <div class="nav-container">


        <!-- =====================================================
             LOGO
        ====================================================== -->

        <a href="index.php" class="brand">

            <img
                src="assets/images/logo.png"
                alt="NexArena Logo"
            >

        </a>


        <!-- =====================================================
             MOBILE MENU BUTTON
        ====================================================== -->

        <button
            class="menu-toggle"
            id="menuToggle"
            type="button"
            aria-label="Open menu"
            aria-expanded="false"
        >

            <span></span>
            <span></span>
            <span></span>

        </button>


        <!-- =====================================================
             MAIN NAVIGATION
        ====================================================== -->

        <nav
            class="main-nav"
            id="mainNav"
            aria-label="Main Navigation"
        >

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

                <a
                    href="login.php"
                    class="nav-login"
                >
                    Login
                </a>


                <a
                    href="registration.php"
                    class="nav-register"
                >
                    Register
                </a>

            </div>

        </nav>


        <!-- =====================================================
             DESKTOP LOGIN / REGISTER + DARK MODE TOGGLE
        ====================================================== -->

        <div class="nav-actions">

            <!-- =================================================
                 DARK MODE TOGGLE BUTTON
            ================================================== -->

            <button
                class="dark-mode-toggle"
                id="darkModeToggle"
                type="button"
                aria-label="Toggle Dark Mode"
            >

                <span id="darkModeIcon">🌙</span>

            </button>


            <a
                href="login.php"
                class="nav-login"
            >
                Login
            </a>


            <a
                href="registration.php"
                class="nav-register"
            >
                Register
            </a>

        </div>

    </div>

</header>


<!-- =========================================================
     NAVBAR JAVASCRIPT
========================================================= -->

<script>

document.addEventListener("DOMContentLoaded", function () {

    const menuToggle = document.getElementById("menuToggle");
    const mainNav = document.getElementById("mainNav");
    const siteHeader = document.getElementById("siteHeader");

    const navLinks = mainNav.querySelectorAll("a");


    /* =========================================================
       MOBILE MENU TOGGLE
    ========================================================= */

    if (menuToggle) {
        menuToggle.addEventListener("click", function () {

            const isOpen =
                mainNav.classList.toggle("active");

            menuToggle.classList.toggle("active");

            menuToggle.setAttribute(
                "aria-expanded",
                isOpen
            );

            menuToggle.setAttribute(
                "aria-label",
                isOpen
                    ? "Close menu"
                    : "Open menu"
            );

        });
    }


    /* =========================================================
       CLOSE MOBILE MENU AFTER CLICK
    ========================================================= */

    navLinks.forEach(function (link) {

        link.addEventListener("click", function () {

            mainNav.classList.remove("active");

            menuToggle.classList.remove("active");

            menuToggle.setAttribute(
                "aria-expanded",
                "false"
            );

            menuToggle.setAttribute(
                "aria-label",
                "Open menu"
            );

        });

    });


    /* =========================================================
       CLOSE MENU WHEN CLICKING OUTSIDE
    ========================================================= */

    document.addEventListener("click", function (event) {

        if (
            !mainNav.contains(event.target) &&
            !menuToggle.contains(event.target)
        ) {

            mainNav.classList.remove("active");

            menuToggle.classList.remove("active");

            menuToggle.setAttribute(
                "aria-expanded",
                "false"
            );

            menuToggle.setAttribute(
                "aria-label",
                "Open menu"
            );

        }

    });


    /* =========================================================
       HEADER SCROLL EFFECT
    ========================================================= */

    function handleScroll() {

        if (window.scrollY > 20) {

            siteHeader.classList.add("scrolled");

        } else {

            siteHeader.classList.remove("scrolled");

        }

    }


    window.addEventListener(
        "scroll",
        handleScroll,
        { passive: true }
    );


    handleScroll();


    /* =========================================================
       ACTIVE NAV LINK
    ========================================================= */

    const currentPage =
        window.location.pathname.split("/").pop();


    navLinks.forEach(function (link) {

        const href =
            link.getAttribute("href");

        if (!href) return;


        /*
         * Don't mark Login/Register as active.
         */

        if (
            href.includes("login.php") ||
            href.includes("registration.php")
        ) {
            return;
        }


        /*
         * Sports page
         */

        if (
            currentPage === "sports.php" &&
            href === "sports.php"
        ) {

            link.classList.add("active");

        }


        /*
         * Home page
         */

        if (
            (currentPage === "" ||
             currentPage === "index.php") &&
            href === "index.php"
        ) {

            link.classList.add("active");

        }

    });


    /* =========================================================
       EVENTS LINK ACTIVE WHEN HASH = #events
    ========================================================= */

    function updateHashActive() {

        const hash =
            window.location.hash;

        navLinks.forEach(function (link) {

            if (
                link.getAttribute("href") ===
                "index.php#events"
            ) {

                link.classList.remove("active");

            }

        });


        if (hash === "#events") {

            navLinks.forEach(function (link) {

                if (
                    link.getAttribute("href") ===
                    "index.php#events"
                ) {

                    link.classList.add("active");

                }

            });

        }

    }


    window.addEventListener(
        "hashchange",
        updateHashActive
    );

    updateHashActive();


    /* =========================================================
       DARK MODE TOGGLE
    ========================================================= */

    const darkModeToggle = document.getElementById('darkModeToggle');
    const darkModeIcon = document.getElementById('darkModeIcon');
    const html = document.documentElement;

    function applyTheme(theme) {
        if (theme === 'dark') {
            html.setAttribute('data-theme', 'dark');
            if (darkModeIcon) darkModeIcon.textContent = '☀️';
        } else {
            html.removeAttribute('data-theme');
            if (darkModeIcon) darkModeIcon.textContent = '🌙';
        }
        localStorage.setItem('nexarena_theme', theme);
        document.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: theme } }));
    }

    // Load saved theme or system preference
    const savedTheme = localStorage.getItem('nexarena_theme');
    if (savedTheme) {
        applyTheme(savedTheme);
    } else {
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        applyTheme(prefersDark ? 'dark' : 'light');
    }

    // Toggle dark mode on button click
    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', function() {
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            applyTheme(newTheme);
            
            // Try to use ThemeManager if available
            if (window.ThemeManager) {
                window.ThemeManager.toggleTheme();
            }
        });
    }

    // Listen for theme changes from ThemeManager
    document.addEventListener('themeChanged', function(e) {
        if (e.detail && e.detail.theme) {
            if (darkModeIcon) {
                darkModeIcon.textContent = e.detail.theme === 'dark' ? '☀️' : '🌙';
            }
        }
    });

});

</script>