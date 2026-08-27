<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Explore Sports | NexArena</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <!-- Theme CSS -->
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        /* ================================================================
               EXPLORE SPORTS PAGE - MODERN DESIGN
            ================================================================ */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-body: #f8f9fc;
            --bg-card: #ffffff;
            --bg-card-hover: #f5f7fb;
            --bg-input: #f1f3f7;
            --text-primary: #1a1a2e;
            --text-secondary: #4a4a5a;
            --text-muted: #7a7a8a;
            --accent: #ff6b00;
            --accent-hover: #e85f00;
            --accent-light: #fff3e8;
            --border-color: #e8ecf2;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            --shadow-hover: 0 12px 40px rgba(255, 107, 0, 0.12);
            --radius: 16px;
            --radius-sm: 10px;
            --transition: 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        [data-theme="dark"] {
            --bg-body: #12121a;
            --bg-card: #1a1a2e;
            --bg-card-hover: #1e1e3a;
            --bg-input: #2a2a4a;
            --text-primary: #e5e5e5;
            --text-secondary: #cccccc;
            --text-muted: #aaaaaa;
            --accent: #ff6b00;
            --accent-hover: #ff8533;
            --accent-light: #2a1a0a;
            --border-color: #2d2d44;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            --shadow-hover: 0 12px 40px rgba(255, 107, 0, 0.2);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-body);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
            padding: 0;
            transition: background 0.3s ease, color 0.3s ease;
        }

        /* ================================================================
           PAGE HEADER
        ================================================================ */
        .page-wrapper {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 28px 60px;
        }

        .page-header {
            padding: 40px 0 20px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }

        .page-header .header-left .page-label {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.8px;
            color: var(--accent);
            background: var(--accent-light);
            padding: 4px 16px;
            border-radius: 40px;
            display: inline-block;
            margin-bottom: 8px;
        }

        .page-header .header-left h1 {
            font-size: 2.4rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: var(--text-primary);
        }

        .page-header .header-left p {
            color: var(--text-secondary);
            font-size: 0.95rem;
            margin-top: 4px;
            max-width: 420px;
        }

        /* ================================================================
           SPORTS GRID
        ================================================================ */
        .sports-grid-section {
            padding: 20px 0 40px;
        }

        .section-title {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-title .accent-line {
            width: 4px;
            height: 28px;
            background: var(--accent);
            border-radius: 4px;
        }

        .section-title span {
            color: var(--accent);
        }

        .sports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
        }

        /* ================================================================
           SPORT CARD
        ================================================================ */
        .sport-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 24px 22px 20px;
            transition: all var(--transition);
            cursor: default;
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
        }

        .sport-card:hover {
            transform: translateY(-6px);
            border-color: var(--accent);
            box-shadow: var(--shadow-hover);
            background: var(--bg-card-hover);
        }

        .sport-card .card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 14px;
        }

        .sport-card .sport-icon {
            font-size: 2.2rem;
            background: var(--accent-light);
            width: 56px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            transition: all var(--transition);
        }

        .sport-card:hover .sport-icon {
            background: var(--accent);
            color: #fff;
            transform: scale(1.05);
        }

        .sport-card .status-badge {
            font-size: 0.6rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 4px 14px;
            border-radius: 40px;
            background: #e6f7ed;
            color: #0f8b4e;
            border: 1px solid #b8e6cf;
        }

        [data-theme="dark"] .sport-card .status-badge {
            background: #1a2a1a;
            color: #4ade80;
            border-color: #2a4a2a;
        }

        .sport-card h3 {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 2px;
        }

        .sport-card .category-tag {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: var(--text-muted);
            margin-bottom: 10px;
        }

        .sport-card .category-tag strong {
            color: var(--text-primary);
            font-weight: 600;
            background: var(--bg-input);
            padding: 2px 14px;
            border-radius: 40px;
            font-size: 0.7rem;
        }

        [data-theme="dark"] .sport-card .category-tag strong {
            background: var(--bg-input);
            color: var(--text-primary);
        }

        .sport-card .description {
            color: var(--text-secondary);
            font-size: 0.88rem;
            line-height: 1.6;
            flex: 1;
            margin-bottom: 16px;
        }

        .sport-card .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 14px;
            border-top: 1px solid var(--border-color);
            font-size: 0.7rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .sport-card .card-footer .arrow {
            color: var(--accent);
            font-weight: 700;
            transition: transform var(--transition);
            font-size: 1rem;
        }

        .sport-card:hover .card-footer .arrow {
            transform: translateX(5px);
        }

        /* ================================================================
           FIXTURES SECTION - AT BOTTOM
        ================================================================ */
        .fixtures-section {
            margin-top: 40px;
            padding-top: 40px;
            border-top: 2px solid var(--border-color);
        }

        [data-theme="dark"] .fixtures-section {
            border-top-color: var(--border-color);
        }

        .fixtures-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 24px;
        }

        .fixtures-header .fixtures-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .fixtures-header .fixtures-title h2 {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--text-primary);
        }

        .fixtures-header .fixtures-title .accent-line {
            width: 4px;
            height: 28px;
            background: var(--accent);
            border-radius: 4px;
        }

        .fixtures-header .fixtures-count {
            font-size: 0.85rem;
            color: var(--text-muted);
            background: var(--bg-input);
            padding: 6px 16px;
            border-radius: 40px;
            font-weight: 600;
        }

        [data-theme="dark"] .fixtures-header .fixtures-count {
            background: var(--bg-input);
            color: var(--text-muted);
        }

        .fixtures-header .fixtures-count strong {
            color: var(--accent);
        }

        /* Fixtures Grid */
        .fixtures-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 20px;
        }

        /* Fixture Card */
        .fixture-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 20px 22px;
            transition: all var(--transition);
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
        }

        .fixture-card:hover {
            transform: translateY(-4px);
            border-color: var(--accent);
            box-shadow: var(--shadow-hover);
        }

        .fixture-card .fixture-sport-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.6rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--accent);
            background: var(--accent-light);
            padding: 3px 12px;
            border-radius: 40px;
            margin-bottom: 12px;
            align-self: flex-start;
        }

        [data-theme="dark"] .fixture-card .fixture-sport-tag {
            background: var(--accent-light);
            color: var(--accent);
        }

        .fixture-card .fixture-teams {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
        }

        .fixture-card .fixture-teams .team {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            flex: 1;
        }

        .fixture-card .fixture-teams .team .team-icon {
            font-size: 1.8rem;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-input);
            border-radius: 50%;
            transition: 0.3s ease;
        }

        [data-theme="dark"] .fixture-card .fixture-teams .team .team-icon {
            background: var(--bg-input);
        }

        .fixture-card:hover .fixture-teams .team .team-icon {
            background: var(--accent-light);
        }

        .fixture-card .fixture-teams .team .team-name {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-primary);
            text-align: center;
        }

        .fixture-card .fixture-teams .vs-divider {
            font-size: 0.7rem;
            font-weight: 800;
            color: var(--accent);
            background: var(--accent-light);
            padding: 4px 10px;
            border-radius: 40px;
            flex-shrink: 0;
        }

        [data-theme="dark"] .fixture-card .fixture-teams .vs-divider {
            background: var(--accent-light);
            color: var(--accent);
        }

        .fixture-card .fixture-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            padding-top: 12px;
            border-top: 1px solid var(--border-color);
            margin-top: 4px;
        }

        [data-theme="dark"] .fixture-card .fixture-details {
            border-top-color: var(--border-color);
        }

        .fixture-card .fixture-details .fixture-info {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.78rem;
            color: var(--text-secondary);
        }

        .fixture-card .fixture-details .fixture-info span {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .fixture-card .fixture-details .fixture-status-badge {
            font-size: 0.55rem;
            font-weight: 700;
            text-transform: uppercase;
            padding: 3px 14px;
            border-radius: 20px;
        }

        .fixture-card .fixture-details .fixture-status-badge.upcoming {
            background: #fef3c7;
            color: #92400e;
        }

        .fixture-card .fixture-details .fixture-status-badge.live {
            background: #fee2e2;
            color: #991b1b;
            animation: pulse-live 1.5s infinite;
        }

        .fixture-card .fixture-details .fixture-status-badge.completed {
            background: #e6f7ed;
            color: #0f8b4e;
        }

        [data-theme="dark"] .fixture-card .fixture-details .fixture-status-badge.upcoming {
            background: #2a2a1a;
            color: #fbbf24;
        }

        [data-theme="dark"] .fixture-card .fixture-details .fixture-status-badge.live {
            background: #2a1a1a;
            color: #f87171;
        }

        [data-theme="dark"] .fixture-card .fixture-details .fixture-status-badge.completed {
            background: #1a2a1a;
            color: #4ade80;
        }

        @keyframes pulse-live {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .fixture-card .fixture-details .fixture-view-btn {
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--accent);
            text-decoration: none;
            transition: 0.3s ease;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .fixture-card .fixture-details .fixture-view-btn:hover {
            color: var(--accent-hover);
            gap: 8px;
        }

        /* ================================================================
           DARK MODE TOGGLE BUTTON
        ================================================================ */
        .dark-mode-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border: 1.5px solid var(--border-color);
            border-radius: 50%;
            background: var(--bg-card);
            cursor: pointer;
            font-size: 18px;
            flex-shrink: 0;
            transition: all 0.3s ease;
            color: var(--text-primary);
        }

        .dark-mode-toggle:hover {
            border-color: var(--accent);
            transform: scale(1.05);
        }

        [data-theme="dark"] .dark-mode-toggle {
            background: var(--bg-card);
            border-color: var(--border-color);
            color: #fbbf24;
        }

        [data-theme="dark"] .dark-mode-toggle:hover {
            border-color: var(--accent);
        }

        /* ================================================================
           BACK BUTTON
        ================================================================ */
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: 0.3s ease;
            padding: 8px 0;
        }

        .back-btn:hover {
            color: var(--accent);
        }

        .back-btn svg {
            transition: 0.3s ease;
        }

        .back-btn:hover svg {
            transform: translateX(-4px);
        }

        /* ================================================================
           RESPONSIVE
        ================================================================ */
        @media (max-width: 768px) {
            .page-wrapper {
                padding: 0 16px 40px;
            }

            .page-header {
                flex-direction: column;
                align-items: stretch;
            }

            .page-header .header-left h1 {
                font-size: 1.8rem;
            }

            .sports-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .fixtures-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .sport-card {
                padding: 18px 16px;
            }

            .sport-card .sport-icon {
                width: 48px;
                height: 48px;
                font-size: 1.8rem;
            }

            .fixture-card .fixture-teams {
                flex-direction: column;
                gap: 8px;
            }

            .fixture-card .fixture-teams .team {
                flex-direction: row;
                gap: 10px;
                width: 100%;
            }

            .fixture-card .fixture-teams .team .team-icon {
                width: 36px;
                height: 36px;
                font-size: 1.2rem;
            }

            .fixture-card .fixture-teams .vs-divider {
                padding: 2px 14px;
                font-size: 0.6rem;
            }

            .fixture-card .fixture-details {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .fixtures-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 480px) {
            .page-wrapper {
                padding: 0 12px 30px;
            }

            .page-header .header-left h1 {
                font-size: 1.5rem;
            }

            .sport-card h3 {
                font-size: 1rem;
            }

            .sport-card .description {
                font-size: 0.8rem;
            }

            .fixture-card .fixture-details .fixture-info {
                font-size: 0.7rem;
                flex-wrap: wrap;
            }

            .section-title {
                font-size: 1.3rem;
            }

            .fixtures-header .fixtures-title h2 {
                font-size: 1.3rem;
            }
        }
    </style>
</head>

<body>

    <?php include 'assets/css/nav_bar.php'; ?>

    <div class="page-wrapper">

        <!-- ================================================================
        PAGE HEADER
        ================================================================ -->
        <header class="page-header">
            <div class="header-left">
                <span class="page-label">
                    <i class="fas fa-bolt" style="margin-right:6px;"></i> EXPLORE
                </span>
                <h1>Explore Sports</h1>
                <p>Discover your favorite sports and explore everything available on NexArena.</p>
            </div>
            <div class="header-actions" style="display:flex; gap:12px; align-items:center;">
                <a href="index.php" class="back-btn">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
                        <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
                    </svg>
                    Back to Home
                </a>
                <button class="dark-mode-toggle" id="darkModeToggle" aria-label="Toggle Dark Mode">
                    <span id="darkModeIcon">🌙</span>
                </button>
            </div>
        </header>

        <!-- ================================================================
        SPORTS GRID
        ================================================================ -->
        <section class="sports-grid-section">
            <div class="section-title">
                <span class="accent-line"></span>
                All <span>Sports</span>
            </div>
            <div class="sports-grid" id="sportsGrid">
                <!-- Cards rendered by JavaScript -->
            </div>
        </section>

        <!-- ================================================================
        FIXTURES SECTION - AT BOTTOM
        ================================================================ -->
        <section class="fixtures-section" id="fixtures">
            <div class="fixtures-header">
                <div class="fixtures-title">
                    <span class="accent-line"></span>
                    <h2>📋 Upcoming <span>Fixtures</span></h2>
                </div>
                <div class="fixtures-count">
                    <strong id="fixtureCount">0</strong> Fixtures
                </div>
            </div>
            <div class="fixtures-grid" id="fixturesGrid">
                <!-- Fixtures rendered by JavaScript -->
            </div>
        </section>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // ============================================================
            // DARK MODE TOGGLE
            // ============================================================
            const darkModeToggle = document.getElementById('darkModeToggle');
            const darkModeIcon = document.getElementById('darkModeIcon');
            const html = document.documentElement;

            function updateDarkModeIcon(theme) {
                if (darkModeIcon) {
                    darkModeIcon.textContent = theme === 'dark' ? '☀️' : '🌙';
                }
            }

            function applyTheme(theme) {
                if (theme === 'dark') {
                    html.setAttribute('data-theme', 'dark');
                } else {
                    html.removeAttribute('data-theme');
                }
                localStorage.setItem('nexarena_theme', theme);
                updateDarkModeIcon(theme);
                document.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: theme } }));
            }

            const savedTheme = localStorage.getItem('nexarena_theme');
            if (savedTheme) {
                applyTheme(savedTheme);
            } else {
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                applyTheme(prefersDark ? 'dark' : 'light');
            }

            if (darkModeToggle) {
                darkModeToggle.addEventListener('click', function() {
                    const currentTheme = html.getAttribute('data-theme');
                    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                    applyTheme(newTheme);
                    if (window.ThemeManager) window.ThemeManager.toggleTheme();
                });
            }

            document.addEventListener('themeChanged', function(e) {
                if (e.detail && e.detail.theme) {
                    updateDarkModeIcon(e.detail.theme);
                }
            });

            // ============================================================
            // SPORTS DATA WITH FIXTURES
            // ============================================================
            const sportsData = [{
                id: 1,
                name: 'Football',
                category: 'Football',
                description: 'The world\'s most popular sport. Join the action on NexArena.',
                icon: '⚽',
                status: 'active',
                fixtures: [
                    { team1: 'Manchester United', team2: 'Liverpool', date: '25 Aug 2026', time: '6:30 PM', status: 'upcoming' },
                    { team1: 'Real Madrid', team2: 'Barcelona', date: '28 Aug 2026', time: '8:00 PM', status: 'upcoming' },
                    { team1: 'Bayern Munich', team2: 'Dortmund', date: '30 Aug 2026', time: '7:00 PM', status: 'live' },
                    { team1: 'PSG', team2: 'Manchester City', date: '02 Sep 2026', time: '9:00 PM', status: 'upcoming' }
                ]
            }, {
                id: 2,
                name: 'Basketball',
                category: 'Basketball',
                description: 'Fast-paced, high-flying hoops action from around the globe.',
                icon: '🏀',
                status: 'active',
                fixtures: [
                    { team1: 'LA Lakers', team2: 'Boston Celtics', date: '26 Aug 2026', time: '7:30 PM', status: 'upcoming' },
                    { team1: 'Chicago Bulls', team2: 'Miami Heat', date: '29 Aug 2026', time: '8:00 PM', status: 'upcoming' },
                    { team1: 'Golden State', team2: 'Brooklyn Nets', date: '01 Sep 2026', time: '7:00 PM', status: 'upcoming' }
                ]
            }, {
                id: 3,
                name: 'Cricket',
                category: 'Cricket',
                description: 'From T20 to Test matches, experience the gentleman\'s game.',
                icon: '🏏',
                status: 'active',
                fixtures: [
                    { team1: 'India', team2: 'Australia', date: '27 Aug 2026', time: '2:00 PM', status: 'upcoming' },
                    { team1: 'England', team2: 'South Africa', date: '30 Aug 2026', time: '10:00 AM', status: 'live' },
                    { team1: 'New Zealand', team2: 'Pakistan', date: '01 Sep 2026', time: '2:00 PM', status: 'upcoming' },
                    { team1: 'West Indies', team2: 'Sri Lanka', date: '03 Sep 2026', time: '2:00 PM', status: 'upcoming' }
                ]
            }, {
                id: 4,
                name: 'Tennis',
                category: 'Tennis',
                description: 'Grand Slam battles and epic rallies — all in one place.',
                icon: '🎾',
                status: 'active',
                fixtures: [
                    { team1: 'Novak Djokovic', team2: 'Carlos Alcaraz', date: '26 Aug 2026', time: '4:00 PM', status: 'completed' },
                    { team1: 'Iga Swiatek', team2: 'Aryna Sabalenka', date: '28 Aug 2026', time: '3:30 PM', status: 'upcoming' },
                    { team1: 'Daniil Medvedev', team2: 'Stefanos Tsitsipas', date: '31 Aug 2026', time: '5:00 PM', status: 'upcoming' }
                ]
            }, {
                id: 5,
                name: 'Esports',
                category: 'Esports',
                description: 'Competitive gaming at its finest. Watch the best players clash.',
                icon: '🎮',
                status: 'active',
                fixtures: [
                    { team1: 'Team Liquid', team2: 'Fnatic', date: '26 Aug 2026', time: '7:00 PM', status: 'live' },
                    { team1: 'Navi', team2: 'G2 Esports', date: '29 Aug 2026', time: '6:00 PM', status: 'upcoming' },
                    { team1: 'Cloud9', team2: 'T1', date: '01 Sep 2026', time: '8:00 PM', status: 'upcoming' }
                ]
            }, {
                id: 6,
                name: 'Kabaddi',
                category: 'Combat',
                description: 'The ancient Indian sport of strength and strategy.',
                icon: '🤼',
                status: 'active',
                fixtures: [
                    { team1: 'Patna Pirates', team2: 'Bengal Warriors', date: '27 Aug 2026', time: '7:30 PM', status: 'upcoming' },
                    { team1: 'Dabang Delhi', team2: 'Jaipur Pink Panthers', date: '30 Aug 2026', time: '8:00 PM', status: 'upcoming' },
                    { team1: 'U Mumba', team2: 'Tamil Thalaivas', date: '02 Sep 2026', time: '7:30 PM', status: 'upcoming' }
                ]
            }, {
                id: 7,
                name: 'Badminton',
                category: 'Racket',
                description: 'Fast shuttlecock action with lightning reflexes.',
                icon: '🏸',
                status: 'active',
                fixtures: [
                    { team1: 'PV Sindhu', team2: 'Carolina Marin', date: '28 Aug 2026', time: '5:00 PM', status: 'upcoming' },
                    { team1: 'Lakshya Sen', team2: 'Viktor Axelsen', date: '31 Aug 2026', time: '4:30 PM', status: 'upcoming' }
                ]
            }, {
                id: 8,
                name: 'Volleyball',
                category: 'Volleyball',
                description: 'Fast-paced net action with spikes, blocks, and digs.',
                icon: '🏐',
                status: 'active',
                fixtures: [
                    { team1: 'Brazil', team2: 'USA', date: '29 Aug 2026', time: '7:00 PM', status: 'upcoming' },
                    { team1: 'Italy', team2: 'France', date: '01 Sep 2026', time: '6:30 PM', status: 'upcoming' },
                    { team1: 'Poland', team2: 'Russia', date: '03 Sep 2026', time: '8:00 PM', status: 'live' }
                ]
            }];

            // ============================================================
            // RENDER SPORTS
            // ============================================================
            const grid = document.getElementById('sportsGrid');

            function renderSports(sports) {
                grid.innerHTML = '';

                sports.forEach(function(sport) {
                    const card = document.createElement('article');
                    card.className = 'sport-card';

                    card.innerHTML = `
                        <div class="card-top">
                            <div class="sport-icon">${sport.icon || '🏅'}</div>
                            <span class="status-badge">${sport.status.toUpperCase()}</span>
                        </div>
                        <h3>${sport.name}</h3>
                        <div class="category-tag">
                            CATEGORY <strong>${sport.category}</strong>
                        </div>
                        <p class="description">${sport.description}</p>
                        <div class="card-footer">
                            <span>${sport.fixtures ? sport.fixtures.length : 0} Fixtures</span>
                            <span class="arrow">→</span>
                        </div>
                    `;
                    grid.appendChild(card);
                });
            }

            // ============================================================
            // RENDER FIXTURES AT BOTTOM
            // ============================================================
            const fixturesGrid = document.getElementById('fixturesGrid');
            const fixtureCount = document.getElementById('fixtureCount');

            function renderFixtures(sports) {
                // Collect all fixtures from all sports
                let allFixtures = [];

                sports.forEach(function(sport) {
                    if (sport.fixtures && sport.fixtures.length > 0) {
                        sport.fixtures.forEach(function(fixture) {
                            allFixtures.push({
                                sport: sport.name,
                                sportIcon: sport.icon,
                                team1: fixture.team1,
                                team2: fixture.team2,
                                date: fixture.date,
                                time: fixture.time,
                                status: fixture.status
                            });
                        });
                    }
                });

                // Sort by date (upcoming first, then live, then completed)
                const statusOrder = { 'live': 0, 'upcoming': 1, 'completed': 2 };
                allFixtures.sort(function(a, b) {
                    return statusOrder[a.status] - statusOrder[b.status];
                });

                // Update count
                fixtureCount.textContent = allFixtures.length;

                fixturesGrid.innerHTML = '';

                if (allFixtures.length === 0) {
                    fixturesGrid.innerHTML = `
                        <div style="grid-column:1/-1; text-align:center; padding:40px; color:var(--text-muted);">
                            <div style="font-size:3rem; margin-bottom:12px;">📅</div>
                            <h3 style="color:var(--text-primary);">No Fixtures Available</h3>
                            <p>Fixtures will appear here when they are scheduled.</p>
                        </div>
                    `;
                    return;
                }

                allFixtures.forEach(function(fixture) {
                    const card = document.createElement('div');
                    card.className = 'fixture-card';

                    const statusClass = fixture.status;

                    card.innerHTML = `
                        <span class="fixture-sport-tag">${fixture.sportIcon} ${fixture.sport}</span>
                        <div class="fixture-teams">
                            <div class="team">
                                <div class="team-icon">${fixture.sportIcon}</div>
                                <span class="team-name">${fixture.team1}</span>
                            </div>
                            <div class="vs-divider">VS</div>
                            <div class="team">
                                <div class="team-icon">${fixture.sportIcon}</div>
                                <span class="team-name">${fixture.team2}</span>
                            </div>
                        </div>
                        <div class="fixture-details">
                            <div class="fixture-info">
                                <span>📅 ${fixture.date}</span>
                                <span>⏰ ${fixture.time}</span>
                            </div>
                            <span class="fixture-status-badge ${statusClass}">${fixture.status}</span>
                            <a href="#" class="fixture-view-btn">
                                View Match →
                            </a>
                        </div>
                    `;
                    fixturesGrid.appendChild(card);
                });
            }

            // ============================================================
            // INITIAL RENDER
            // ============================================================
            renderSports(sportsData);
            renderFixtures(sportsData);

        });
    </script>

    <!-- Theme JavaScript -->
    <script src="assets/js/theme.js"></script>

</body>
</html>