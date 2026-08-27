<?php
// Dummy session check for testing
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        /* ============================================
           ALL CSS INCLUDED HERE FOR TESTING
           ============================================ */
        :root {
            --orange: #ff6600;
            --black: #111;
            --white: #fff;
            --light-bg: #f7f7f7;
            --border: #e5e5e5;
        }

        /* ---- SIDEBAR ---- */
        .admin-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 270px;
            height: 100vh;
            background: var(--black);
            color: #fff;
            overflow-y: auto;
            z-index: 1100;
            transition: left 0.3s ease;
        }
        .admin-sidebar ul {
            list-style: none;
            padding: 20px;
        }
        .admin-sidebar ul li {
            padding: 10px;
            border-bottom: 1px solid #333;
            color: #ccc;
        }

        /* ---- MAIN ---- */
        .admin-main {
            margin-left: 270px;
            min-height: 100vh;
            background: var(--light-bg);
        }

        /* ---- HEADER ---- */
        .admin-header {
            height: 80px;
            padding: 0 30px;
            background: var(--white);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 900;
        }
        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .sidebar-toggle {
            display: none;
            border: none;
            background: var(--black);
            color: var(--white);
            width: 38px;
            height: 38px;
            border-radius: 7px;
            cursor: pointer;
            font-size: 18px;
        }
        .header-right {
            display: flex;
            gap: 20px;
        }
        .header-admin-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--orange);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 800;
        }

        /* ---- CONTENT ---- */
        .admin-content { padding: 30px; }
        .dashboard-welcome {
            background: var(--white);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 20px;
        }
        .quick-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }
        .quick-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 8px;
            text-decoration: none;
            color: var(--black);
        }

        /* ---- OVERLAY ---- */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.4);
            z-index: 1050;
        }
        .sidebar-overlay.active { display: block; }

        /* ============================================
           RESPONSIVE
           ============================================ */
        @media (max-width: 1024px) {
            .admin-main { margin-left: 0; }
            .admin-sidebar {
                left: -280px;
            }
            .admin-sidebar.mobile-open {
                left: 0;
            }
            .sidebar-toggle {
                display: inline-flex !important;
            }
            body.sidebar-open .admin-main {
                display: none; /* HIDE MAIN CONTENT */
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .quick-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 600px) {
            .stats-grid { grid-template-columns: 1fr; }
            .quick-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- OVERLAY -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- SIDEBAR (dummy) -->
<div class="admin-sidebar" id="adminSidebar">
    <ul>
        <li>Dashboard</li>
        <li>Events</li>
        <li>Sports</li>
        <li>Users</li>
        <li>Settings</li>
    </ul>
</div>

<!-- MAIN -->
<div class="admin-main" id="adminMain">
    <header class="admin-header">
        <div class="header-left">
            <button class="sidebar-toggle" id="sidebarToggle">☰</button>
            <h1>Admin Dashboard</h1>
        </div>
        <div class="header-right">
            <div class="header-admin-avatar">A</div>
        </div>
    </header>

    <div class="admin-content">
        <div class="dashboard-welcome">
            <h2>Welcome back, Admin 👋</h2>
            <p>Here's what's happening.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card"><strong>Users</strong><br>0</div>
            <div class="stat-card"><strong>Sports</strong><br>0</div>
            <div class="stat-card"><strong>Events</strong><br>0</div>
            <div class="stat-card"><strong>Registrations</strong><br>0</div>
        </div>

        <div class="quick-grid">
            <a href="#" class="quick-card">📅 Manage Events</a>
            <a href="#" class="quick-card">🏆 Manage Sports</a>
            <a href="#" class="quick-card">👥 Manage Users</a>
            <a href="#" class="quick-card">🔔 Notifications</a>
        </div>
    </div>
</div>

<!-- SCRIPT -->
<script>
    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const body = document.body;

    if (toggle && sidebar && overlay) {
        toggle.addEventListener('click', function() {
            sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('active');
            body.classList.toggle('sidebar-open');
        });

        overlay.addEventListener('click', function() {
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('active');
            body.classList.remove('sidebar-open');
        });
    }
</script>

</body>
</html>