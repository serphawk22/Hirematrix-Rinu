// APPLY SAVED THEME
function applySavedTheme() {
    const savedTheme = localStorage.getItem("theme");
    const btn = document.querySelector(".theme-toggle-btn");

    if (savedTheme === "dark") {
        document.body.classList.remove("light");
        if (btn) btn.innerHTML = "☀️"; // show sun in dark mode
    } else {
        document.body.classList.add("light"); // default
        if (btn) btn.innerHTML = "🌙"; // show moon in light mode
    }
}

// TOGGLE THEME
function toggleTheme() {
    const isLight = document.body.classList.contains("light");
    const btn = document.querySelector(".theme-toggle-btn");

    if (isLight) {
        document.body.classList.remove("light");
        localStorage.setItem("theme", "dark");
        if (btn) btn.innerHTML = "☀️";
    } else {
        document.body.classList.add("light");
        localStorage.setItem("theme", "light");
        if (btn) btn.innerHTML = "🌙";
    }
}
 
// RUN ON PAGE LOAD
document.addEventListener("DOMContentLoaded", function () {
    applySavedTheme();
});

 