(function () {
  function getButton() {
    return document.querySelector(".theme-toggle-btn");
  }

  function applyTheme(theme) {
    const isDark = theme === "dark";
    document.body.classList.toggle("dark", isDark);
    document.body.classList.toggle("light", !isDark);

    const btn = getButton();
    if (btn) {
      btn.setAttribute("aria-pressed", isDark ? "true" : "false");
      btn.setAttribute("title", isDark ? "Switch to light mode" : "Switch to dark mode");
    }
  }

  window.applySavedTheme = function () {
    const savedTheme = localStorage.getItem("theme") === "dark" ? "dark" : "light";
    applyTheme(savedTheme);
  };

  window.toggleTheme = function () {
    const nextTheme = document.body.classList.contains("dark") ? "light" : "dark";
    localStorage.setItem("theme", nextTheme);
    applyTheme(nextTheme);
  };

  document.addEventListener("DOMContentLoaded", window.applySavedTheme);
})();
