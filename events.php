<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Explore Events | NexArena</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <!-- Theme CSS -->
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        /* ================================================================
               EXPLORE EVENTS PAGE - MODERN DESIGN
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
            padding: 0 28px;
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
           EVENTS GRID
        ================================================================ */
        .events-grid-section {
            padding: 20px 0 60px;
        }

        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 24px;
        }

        /* ================================================================
           EVENT CARD
        ================================================================ */
        .event-card {
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

        .event-card:hover {
            transform: translateY(-6px);
            border-color: var(--accent);
            box-shadow: var(--shadow-hover);
            background: var(--bg-card-hover);
        }

        .event-card .card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 14px;
        }

        .event-card .event-icon {
            font-size: 2rem;
            background: var(--accent-light);
            width: 52px;
            height: 52px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            transition: all var(--transition);
        }

        .event-card:hover .event-icon {
            background: var(--accent);
            color: #fff;
            transform: scale(1.05);
        }

        .event-card .status-badge {
            font-size: 0.6rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 4px 14px;
            border-radius: 40px;
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
        }

        .event-card .status-badge.open {
            background: #e6f7ed;
            color: #0f8b4e;
            border-color: #b8e6cf;
        }

        .event-card .status-badge.upcoming {
            background: #fef3c7;
            color: #92400e;
            border-color: #fcd34d;
        }

        .event-card .status-badge.registration-closed {
            background: #fee2e2;
            color: #991b1b;
            border-color: #fecaca;
        }

        [data-theme="dark"] .event-card .status-badge {
            background: #2a2a1a;
            color: #fbbf24;
            border-color: #4a4a2a;
        }

        [data-theme="dark"] .event-card .status-badge.open {
            background: #1a2a1a;
            color: #4ade80;
            border-color: #2a4a2a;
        }

        [data-theme="dark"] .event-card .status-badge.upcoming {
            background: #2a2a1a;
            color: #fbbf24;
            border-color: #4a4a2a;
        }

        [data-theme="dark"] .event-card .status-badge.registration-closed {
            background: #2a1a1a;
            color: #f87171;
            border-color: #4a2a2a;
        }

        .event-card h3 {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        .event-card .event-meta {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 12px;
        }

        .event-card .event-meta .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        .event-card .event-meta .meta-item .meta-icon {
            font-size: 14px;
            width: 20px;
            text-align: center;
        }

        .event-card .description {
            color: var(--text-secondary);
            font-size: 0.88rem;
            line-height: 1.6;
            flex: 1;
            margin-bottom: 16px;
        }

        .event-card .card-footer {
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

        .event-card .card-footer .arrow {
            color: var(--accent);
            font-weight: 700;
            transition: transform var(--transition);
            font-size: 1rem;
        }

        .event-card:hover .card-footer .arrow {
            transform: translateX(5px);
        }

        .event-card .prize-pool {
            color: var(--accent);
            font-weight: 700;
            font-size: 0.9rem;
            margin: 8px 0;
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
                padding: 0 16px;
            }

            .page-header {
                flex-direction: column;
                align-items: stretch;
            }

            .page-header .header-left h1 {
                font-size: 1.8rem;
            }

            .events-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .event-card {
                padding: 18px 16px;
            }

            .event-card .event-icon {
                width: 48px;
                height: 48px;
                font-size: 1.8rem;
            }
        }

        @media (max-width: 480px) {
            .page-wrapper {
                padding: 0 12px;
            }

            .page-header .header-left h1 {
                font-size: 1.5rem;
            }

            .event-card h3 {
                font-size: 1rem;
            }

            .event-card .description {
                font-size: 0.8rem;
            }

            .event-card .event-meta .meta-item {
                font-size: 0.75rem;
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
                    <i class="fas fa-calendar-alt" style="margin-right:6px;"></i> EXPLORE
                </span>
                <h1>Explore Events</h1>
                <p>Discover upcoming sports events, tournaments, and competitions.</p>
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
        EVENTS GRID
        ================================================================ -->
        <section class="events-grid-section">
            <div class="events-grid" id="eventsGrid">
                <!-- Cards rendered by JavaScript -->
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
            // EVENTS DATA
            // ============================================================
            const eventsData = [
                { id: 1, name: 'NexArena Cricket Championship', sport: '🏏 Cricket', date: '15 Sep 2026', location: 'Nanded, Maharashtra', prize: '₹1,50,000', status: 'upcoming', description: 'The biggest cricket tournament of the year. Compete with the best teams.' },
                { id: 2, name: 'NexArena Football League', sport: '⚽ Football', date: '28 Sep 2026', location: 'Pune, Maharashtra', prize: '₹2,00,000', status: 'open', description: 'Professional football league with teams from across the country.' },
                { id: 3, name: 'NexArena Open Badminton Cup', sport: '🏸 Badminton', date: '05 Oct 2026', location: 'Mumbai, Maharashtra', prize: '₹75,000', status: 'open', description: 'Open badminton tournament for all skill levels.' },
                { id: 4, name: 'NexArena Basketball Showdown', sport: '🏀 Basketball', date: '12 Oct 2026', location: 'Delhi, India', prize: '₹1,00,000', status: 'upcoming', description: 'High-energy basketball competition with teams from across India.' },
                { id: 5, name: 'NexArena Esports Championship', sport: '🎮 Esports', date: '20 Oct 2026', location: 'Online', prize: '₹5,00,000', description: 'The ultimate gaming competition. CS:GO, Valorant, and more.', status: 'registration-closed' },
                { id: 6, name: 'NexArena Tennis Open', sport: '🎾 Tennis', date: '01 Nov 2026', location: 'Chennai, Tamil Nadu', prize: '₹80,000', description: 'Premier tennis tournament with singles and doubles categories.', status: 'upcoming' }
            ];

            // ============================================================
            // RENDER EVENTS
            // ============================================================
            const grid = document.getElementById('eventsGrid');

            function renderEvents(events) {
                grid.innerHTML = '';

                events.forEach(function(event) {
                    const card = document.createElement('article');
                    card.className = 'event-card';

                    const statusClass = event.status === 'open' ? 'open' : 
                                      event.status === 'upcoming' ? 'upcoming' : 'registration-closed';

                    card.innerHTML = `
                        <div class="card-top">
                            <div class="event-icon">${event.sport.split(' ')[0]}</div>
                            <span class="status-badge ${statusClass}">${event.status.toUpperCase()}</span>
                        </div>
                        <h3>${event.name}</h3>
                        <div class="event-meta">
                            <div class="meta-item">
                                <span class="meta-icon">📅</span>
                                <span>${event.date}</span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-icon">📍</span>
                                <span>${event.location}</span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-icon">🏆</span>
                                <span>${event.sport}</span>
                            </div>
                        </div>
                        <div class="prize-pool">🏆 Prize Pool: ${event.prize}</div>
                        <p class="description">${event.description}</p>
                        <div class="card-footer">
                            <span>NexArena Event</span>
                            <span class="arrow">→</span>
                        </div>
                    `;
                    grid.appendChild(card);
                });
            }

            renderEvents(eventsData);

        });
    </script>

    <!-- Theme JavaScript -->
    <script src="assets/js/theme.js"></script>

</body>
</html>