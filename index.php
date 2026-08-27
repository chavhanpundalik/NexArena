<?php

if (isset($_GET["feedback"]) && $_GET["feedback"] == "success") {

    echo '<div class="success-message">
            Feedback submitted successfully!
          </div>';

}

?>

<?php
session_start();
?>

<?php
$successMessage = '';

if (isset($_GET['login']) && $_GET['login'] === 'success') {
    $successMessage = 'Login successful! Welcome back.';
}
?>

<?php if ($successMessage): ?>
    <div class="success-message">
        <?= htmlspecialchars($successMessage) ?>
    </div>
<?php endif; ?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>NexArena - Sports Management Platform</title>

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/theme.css">
</head>

<body>

<!-- ================= NAVBAR – PREMIUM ================= -->
<?php include 'assets/css/nav_bar.php'; ?>

</header>
<?php if (isset($_GET['login']) && $_GET['login'] === 'success'): ?>
    <div class="success-message">
        Login successful! Welcome back.
    </div>
<?php endif; ?>


<script>
document.addEventListener("DOMContentLoaded", function () {

    const menuToggle = document.getElementById("menuToggle");
    const mainNav = document.getElementById("mainNav");

    if (menuToggle) {
        menuToggle.addEventListener("click", function () {
            mainNav.classList.toggle("active");
            menuToggle.innerHTML = mainNav.classList.contains("active") ? "✕" : "☰";
        });
    }

    const navLinks = mainNav.querySelectorAll("a");
    navLinks.forEach(function (link) {
        link.addEventListener("click", function () {
            mainNav.classList.remove("active");
            if (menuToggle) menuToggle.innerHTML = "☰";
        });
    });

});
</script>

<section class="hero" id="home">
    <div class="hero-overlay"></div>

    <div class="hero-content">
        <p class="hero-label">WELCOME TO NEXARENA</p>

        <h1>
            Where <span>Sports</span><br>
            Come Alive.
        </h1>

        <p class="hero-text">
            Discover sports, explore events, connect with teams,
            and experience a smarter way to manage your sports journey.
        </p>

        <div class="hero-buttons">
            <a href="events.php" class="btn btn-primary">
                Explore Events →
            </a>

            <a href="sports.php" class="btn btn-outline">
                Explore Sports
            </a>
        </div>

        <div class="hero-stats">
            <div>
                <strong>10+</strong>
                <span>Sport Disciplines</span>
            </div>

            <div>
                <strong>1,245+</strong>
                <span>Active Athletes</span>
            </div>

            <div>
                <strong>210+</strong>
                <span>Registered Teams</span>
            </div>
        </div>
    </div>
</section>

<!-- ================= STATS ================= -->
<section class="stats-strip">

    <div class="stats-grid">

        <div class="stat-item">
            <strong>10+</strong>
            <span>Sport Disciplines</span>
        </div>

        <div class="stat-item">
            <strong>1,245+</strong>
            <span>Active Athletes</span>
        </div>

        <div class="stat-item">
            <strong>210+</strong>
            <span>Registered Teams</span>
        </div>

        <div class="stat-item">
            <strong>₹5,00,000+</strong>
            <span>Prize Pools</span>
        </div>

    </div>

</section>


<!-- ================= SPORTS ================= -->
<section class="section sports-section" id="sports">

    <div class="section-heading">
        <p>EXPLORE</p>
        <h2>Popular Sports</h2>
        <span>Choose your game. Follow your passion.</span>
    </div>

    <div class="sports-grid">

        <article class="sport-card">
            <div class="sport-icon">⚽</div>
            <h3>Football</h3>
            <p>Follow football events, teams and competitions.</p>
            <a href="login.php">Explore →</a>
        </article>

        <article class="sport-card">
            <div class="sport-icon">🏏</div>
            <h3>Cricket</h3>
            <p>Discover cricket tournaments and competitive events.</p>
            <a href="login.php">Explore →</a>
        </article>

        <article class="sport-card">
            <div class="sport-icon">🏀</div>
            <h3>Basketball</h3>
            <p>Discover basketball competitions and teams.</p>
            <a href="login.php">Explore →</a>
        </article>

        <article class="sport-card">
            <div class="sport-icon">🏸</div>
            <h3>Badminton</h3>
            <p>Track badminton tournaments and matches.</p>
            <a href="login.php">Explore →</a>
        </article>

        <article class="sport-card">
            <div class="sport-icon">🤼</div>
            <h3>Kabaddi</h3>
            <p>Manage kabaddi events, teams and competitions.</p>
            <a href="login.php">Explore →</a>
        </article>

        <article class="sport-card">
            <div class="sport-icon">🏐</div>
            <h3>Volleyball</h3>
            <p>Discover volleyball events and tournament action.</p>
            <a href="login.php">Explore →</a>
        </article>

    </div>

</section>


<!-- ================= EVENTS ================= -->
<section class="section events-section" id="events">

    <div class="section-heading">
        <p>DON'T MISS OUT</p>
        <h2>Featured Events</h2>
        <span>Discover upcoming competitions and your next challenge.</span>
    </div>

    <div class="events-grid">

        <article class="event-card">
            <div class="event-top">
                <span>🏏 Cricket</span>
                <b>UPCOMING</b>
            </div>

            <h3>NexArena Cricket Championship</h3>

            <p>🗓 15 Sep 2026</p>
            <p>📍 Nanded, Maharashtra</p>

            <div class="event-bottom">
                <strong>₹1,50,000 Prize Pool</strong>
                <a href="login.php">View Event →</a>
            </div>
        </article>

        <article class="event-card">
            <div class="event-top">
                <span>⚽ Football</span>
                <b>UPCOMING</b>
            </div>

            <h3>NexArena Football League</h3>

            <p>🗓 28 Sep 2026</p>
            <p>📍 Pune, Maharashtra</p>

            <div class="event-bottom">
                <strong>₹2,00,000 Prize Pool</strong>
                <a href="login.php">View Event →</a>
            </div>
        </article>

        <article class="event-card">
            <div class="event-top">
                <span>🏸 Badminton</span>
                <b>OPEN</b>
            </div>

            <h3>NexArena Open Badminton Cup</h3>

            <p>🗓 05 Oct 2026</p>
            <p>📍 Mumbai, Maharashtra</p>

            <div class="event-bottom">
                <strong>₹75,000 Prize Pool</strong>
                <a href="login.php">View Event →</a>
            </div>
        </article>

    </div>

</section>


<!-- ================= ABOUT ================= -->
<section class="about-section" id="about">    <div class="about-container">
        <div class="about-label">About NexArena</div>
        <div class="about-grid">
           <!-- LEFT: Sports Image Collage -->
<div class="about-left">

    <!-- Cricket - Main Image -->
    <img 
        class="main-img"
        src="https://images.unsplash.com/photo-1531415074968-036ba1b575da?auto=format&fit=crop&w=800&q=80"
        alt="Cricket"
    >

    <div class="img-collage">

        <!-- Esports -->
        <img 
            src="https://images.unsplash.com/photo-1542751371-adc38448a05e?auto=format&fit=crop&w=400&q=80"
            alt="Esports"
        >

        <!-- Kabaddi -->
        <img 
            src="https://images.unsplash.com/photo-1552674605-db6ffd4facb5?auto=format&fit=crop&w=400&q=80"
            alt="Kabaddi"
        >

    </div>

</div>

            <!-- RIGHT: Text & Stats -->
            <div class="about-right">
                <h2>Empowering <span>Athletes</span> &amp; <span>Fans</span> Worldwide</h2>
                <p class="about-desc">
                    NexArena bridges the gap between passion and performance. From amateur leagues 
                    to professional tournaments, we give you the tools to compete, connect, 
                    and celebrate every victory.
                </p>
                <div class="about-mini-stats">
                    <div>
                        <strong>5+</strong>
                        <span>Years Active</span>
                    </div>
                    <div>
                        <strong>200+</strong>
                        <span>Events Hosted</span>
                    </div>
                    <div>
                        <strong>50K+</strong>
                        <span>Users</span>
                    </div>
                </div>
                <a href="#" class="btn-about">Learn More →</a>
            </div>
        </div>
    </div>
</section>
<!-- ================= CONTACT ================= -->
<section class="section contact-section" id="contact">

    <div class="section-heading">
        <p>GET IN TOUCH</p>
        <h2>Contact Us</h2>
        <span>Have a question? We'd love to hear from you.</span>
    </div>

    <div class="contact-container">

        <div class="contact-info">
            <h3>Let's talk about <span>Sports.</span></h3>

            <p>
                Have questions about NexArena, sports events
                or tournament management? Send us a message.
            </p>

            <div class="contact-detail">
                <strong>NexArena</strong>
                <span>Sports Management Platform</span>
            </div>

            <div class="contact-detail">
                <strong>🏆 Events</strong>
                <span>Tournaments & competitions</span>
            </div>
        </div>

        <div class="contact-box">

            <form action="#" method="post">

                <input
                    type="text"
                    name="name"
                    placeholder="Your Name"
                    required
                >

                <input
                    type="email"
                    name="email"
                    placeholder="Your Email"
                    required
                >

                <input
                    type="text"
                    name="subject"
                    placeholder="Subject"
                >

                <textarea
                    name="message"
                    rows="5"
                    placeholder="Your Message"
                    required
                ></textarea>

                <button type="submit">
                    Send Message →
                </button>

            </form>

        </div>

    </div>

</section>


<!-- ================= FOOTER – WHITE THEME ================= -->
<footer class="site-footer">

    <!-- Top decorative orange line -->
    <div class="footer-glow-line"></div>

    <div class="footer-container">

        <!-- ========== COLUMN 1: BRAND + SOCIAL ========== -->
        <div class="footer-col footer-col-brand">
            <div class="footer-brand">
                <span>Nex</span>Arena
                <span class="brand-badge">v2.0</span>
            </div>

            <p class="footer-description">
                The ultimate sports management ecosystem — connecting athletes, 
                teams, tournaments, and fans across the globe. Built for passion, 
                powered by innovation.
            </p>

            <!-- Social Icons -->
            <div class="footer-social">
                <a href="#" aria-label="Facebook" class="social-icon">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                </a>
                <a href="#" aria-label="Twitter" class="social-icon">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                    </svg>
                </a>
                <a href="#" aria-label="Instagram" class="social-icon">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                    </svg>
                </a>
                <a href="#" aria-label="YouTube" class="social-icon">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
                        <path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                    </svg>
                </a>
                <a href="#" aria-label="LinkedIn" class="social-icon">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
                        <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                    </svg>
                </a>
            </div>
        </div>

        <!-- ========== COLUMN 2: QUICK LINKS ========== -->
        <div class="footer-col footer-col-links">
            <div class="footer-title">
                <span class="title-icon">⚡</span> Quick Links
            </div>
            <div class="footer-links">
                <a href="#home"><span>›</span> Home</a>
                <a href="#sports"><span>›</span> Sports</a>
                <a href="#events"><span>›</span> Events</a>
                <a href="#about"><span>›</span> About Us</a>
                <a href="#contact"><span>›</span> Contact</a>
            </div>
            <div class="footer-links footer-links-secondary">
                <a href="login.php"><span>›</span> Login</a>
                <a href="registration.php"><span>›</span> Register</a>
                <a href="events.php"><span>›</span> View Events</a>
                <a href="sports.php"><span>›</span> Explore Sports</a>
            </div>
        </div>

        <!-- ========== COLUMN 3: NEWSLETTER + CONTACT ========== -->
        <div class="footer-col footer-col-newsletter">
            <div class="footer-title">
                <span class="title-icon">📬</span> Stay Updated
            </div>
            <p class="newsletter-text">
                Subscribe to get the latest sports events, tournaments, and updates right in your inbox.
            </p>

            <form class="newsletter-form" action="#" method="POST">
                <div class="newsletter-input-group">
                    <input type="email" placeholder="Enter your email" required>
                    <button type="submit" class="newsletter-btn">
                        Subscribe
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                            <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                        </svg>
                    </button>
                </div>
                <small class="newsletter-note">No spam. Unsubscribe anytime.</small>
            </form>

            <!-- Contact info -->
            <div class="footer-contact-info">
                <div class="contact-item">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                    </svg>
                    <span>123 Sports Avenue, City</span>
                </div>
                <div class="contact-item">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                        <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                    </svg>
                    <span>info@nexarena.com</span>
                </div>
                <div class="contact-item">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                        <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/>
                    </svg>
                    <span>+1 (555) 123-4567</span>
                </div>
            </div>
        </div>

    </div>

    <!-- ========== FOOTER BOTTOM ========== -->
    <div class="footer-bottom">
        <div class="footer-bottom-left">
            <p>© 2026 <strong>NexArena</strong>. All Rights Reserved.</p>
        </div>
        <div class="footer-bottom-right">
            <a href="#">Privacy Policy</a>
            <span class="divider">|</span>
            <a href="#">Terms of Service</a>
            <span class="divider">|</span>
            <a href="#">Cookie Policy</a>
        </div>
    </div>

</footer>

<script src="assets/js/theme.js"></script>
</body>
</html>