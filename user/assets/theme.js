// =========================================================
// NEXARENA THEME MANAGER
// Handles dark/light mode switching with persistence
// =========================================================

(function() {
    'use strict';

    const ThemeManager = {
        // Storage key
        STORAGE_KEY: 'nexarena_theme',
        
        // Current theme
        currentTheme: 'light',
        
        // Initialize
        init: function() {
            // Load saved theme or system preference
            this.loadTheme();
            
            // Listen for theme toggle events
            this.bindEvents();
            
            // Update UI elements that show theme state
            this.updateUI();
        },
        
        // Load theme from storage or system
        loadTheme: function() {
            // Check localStorage first
            const saved = localStorage.getItem(this.STORAGE_KEY);
            
            if (saved === 'dark' || saved === 'light') {
                this.currentTheme = saved;
            } else {
                // Check system preference
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                this.currentTheme = prefersDark ? 'dark' : 'light';
            }
            
            this.applyTheme(this.currentTheme);
        },
        
        // Apply theme to document
        applyTheme: function(theme) {
            const html = document.documentElement;
            
            if (theme === 'dark') {
                html.setAttribute('data-theme', 'dark');
                html.classList.add('dark-mode');
                html.classList.remove('light-mode');
            } else {
                html.removeAttribute('data-theme');
                html.classList.add('light-mode');
                html.classList.remove('dark-mode');
            }
            
            this.currentTheme = theme;
            
            // Save to localStorage
            localStorage.setItem(this.STORAGE_KEY, theme);
            
            // Update UI elements
            this.updateUI();
            
            // Dispatch custom event for other scripts
            document.dispatchEvent(new CustomEvent('themeChanged', {
                detail: { theme: theme }
            }));
        },
        
        // Toggle theme
        toggleTheme: function() {
            const newTheme = this.currentTheme === 'light' ? 'dark' : 'light';
            this.applyTheme(newTheme);
            
            // Update toggle switches on page
            this.updateToggleSwitches();
            
            // If we're on settings page, save to database via AJAX
            this.saveToDatabase(newTheme);
        },
        
        // Update UI elements that reflect current theme
        updateUI: function() {
            const isDark = this.currentTheme === 'dark';
            
            // Update toggle switches
            const toggles = document.querySelectorAll('[data-theme-toggle]');
            toggles.forEach(toggle => {
                if (toggle.type === 'checkbox') {
                    toggle.checked = isDark;
                }
            });
            
            // Update any theme indicator text
            const indicators = document.querySelectorAll('[data-theme-indicator]');
            indicators.forEach(el => {
                el.textContent = isDark ? '🌙 Dark' : '☀️ Light';
            });
        },
        
        // Update toggle switches based on current theme
        updateToggleSwitches: function() {
            const isDark = this.currentTheme === 'dark';
            const toggles = document.querySelectorAll('[data-theme-toggle]');
            toggles.forEach(toggle => {
                if (toggle.type === 'checkbox') {
                    toggle.checked = isDark;
                }
            });
        },
        
        // Save theme preference to database via AJAX
        saveToDatabase: function(theme) {
            // Only attempt if we're on a page with user session
            const themeValue = theme === 'dark' ? 1 : 0;
            
            fetch('ajax/save_theme.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'dark_mode=' + themeValue
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Theme saved to database');
                }
            })
            .catch(error => {
                console.log('Could not save theme to database:', error);
            });
        },
        
        // Bind events
        bindEvents: function() {
            // Listen for theme toggle changes
            document.addEventListener('change', function(e) {
                const target = e.target;
                
                // Check if it's a theme toggle
                if (target.matches('[data-theme-toggle]') || 
                    (target.id === 'dark_mode' && target.closest('form'))) {
                    ThemeManager.toggleTheme();
                }
            });
            
            // Also listen for clicks on theme toggle buttons
            document.addEventListener('click', function(e) {
                const target = e.target.closest('[data-theme-toggle-btn]');
                if (target) {
                    e.preventDefault();
                    ThemeManager.toggleTheme();
                }
            });
            
            // Listen for system theme changes
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
                // Only change if user hasn't manually set a preference
                if (!localStorage.getItem(ThemeManager.STORAGE_KEY)) {
                    const newTheme = e.matches ? 'dark' : 'light';
                    ThemeManager.applyTheme(newTheme);
                }
            });
        }
    };
    
    // Initialize theme manager when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            ThemeManager.init();
        });
    } else {
        ThemeManager.init();
    }
    
    // Expose ThemeManager globally
    window.ThemeManager = ThemeManager;
    
})();