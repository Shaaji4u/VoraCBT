/**
 * CBT Enterprise Platform - Dark Mode Manager
 * Handles theme switching and persistence
 */

(function() {
  'use strict';

  // Theme configuration
  const THEME_KEY = 'cbt-theme-preference';
  const THEME_LIGHT = 'light';
  const THEME_DARK = 'dark';
  const THEME_AUTO = 'auto';

  // Get the root element
  const root = document.documentElement;

  /**
   * Get the current theme preference
   * @returns {string} The current theme ('light', 'dark', or 'auto')
   */
  function getThemePreference() {
    try {
      const stored = localStorage.getItem(THEME_KEY);
      if (stored && [THEME_LIGHT, THEME_DARK, THEME_AUTO].includes(stored)) {
        return stored;
      }
    } catch (e) {
      console.warn('LocalStorage not available');
    }
    return THEME_AUTO;
  }

  /**
   * Set the theme preference
   * @param {string} theme - The theme to set ('light', 'dark', or 'auto')
   */
  function setThemePreference(theme) {
    try {
      localStorage.setItem(THEME_KEY, theme);
    } catch (e) {
      console.warn('LocalStorage not available');
    }
    applyTheme(theme);
  }

  /**
   * Check if system prefers dark mode
   * @returns {boolean}
   */
  function systemPrefersDark() {
    return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
  }

  /**
   * Apply the theme to the document
   * @param {string} theme - The theme to apply
   */
  function applyTheme(theme) {
    let effectiveTheme = theme;
    
    if (theme === THEME_AUTO) {
      effectiveTheme = systemPrefersDark() ? THEME_DARK : THEME_LIGHT;
    }

    if (effectiveTheme === THEME_DARK) {
      root.setAttribute('data-theme', 'dark');
    } else {
      root.removeAttribute('data-theme');
    }

    // Update toggle button state if it exists
    updateToggleButton(effectiveTheme);

    // Dispatch custom event
    window.dispatchEvent(new CustomEvent('themechange', {
      detail: { theme: effectiveTheme }
    }));
  }

  /**
   * Update the toggle button visual state
   * @param {string} theme - The current effective theme
   */
  function updateToggleButton(theme) {
    const toggle = document.querySelector('.dark-mode-toggle');
    if (!toggle) return;

    const icon = toggle.querySelector('i');
    const text = toggle.querySelector('span');

    if (theme === THEME_DARK) {
      toggle.classList.add('active');
      if (icon) {
        icon.className = 'bi bi-moon-fill';
      }
      if (text) {
        text.textContent = 'Dark';
      }
    } else {
      toggle.classList.remove('active');
      if (icon) {
        icon.className = 'bi bi-sun-fill';
      }
      if (text) {
        text.textContent = 'Light';
      }
    }
  }

  /**
   * Initialize the dark mode manager
   */
  function init() {
    // Apply initial theme
    const preference = getThemePreference();
    applyTheme(preference);

    // Listen for system theme changes
    if (window.matchMedia) {
      const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
      mediaQuery.addEventListener('change', function(e) {
        if (getThemePreference() === THEME_AUTO) {
          applyTheme(THEME_AUTO);
        }
      });
    }

    // Setup toggle buttons
    document.querySelectorAll('.dark-mode-toggle').forEach(function(toggle) {
      toggle.addEventListener('click', function() {
        const currentTheme = getThemePreference();
        let newTheme;
        
        if (currentTheme === THEME_LIGHT) {
          newTheme = THEME_DARK;
        } else if (currentTheme === THEME_DARK) {
          newTheme = THEME_LIGHT;
        } else {
          // Auto mode - switch to opposite of current system preference
          newTheme = systemPrefersDark() ? THEME_LIGHT : THEME_DARK;
        }
        
        setThemePreference(newTheme);
      });
    });
  }

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // Expose API globally
  window.DarkMode = {
    getPreference: getThemePreference,
    setPreference: setThemePreference,
    apply: applyTheme,
    isDark: function() {
      return root.getAttribute('data-theme') === 'dark';
    },
    toggle: function() {
      const newTheme = this.isDark() ? THEME_LIGHT : THEME_DARK;
      setThemePreference(newTheme);
      return newTheme;
    }
  };

})();
