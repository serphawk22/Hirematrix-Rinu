/*
|--------------------------------------------------------------------------
| PREVENT BACK BUTTON
|--------------------------------------------------------------------------
*/

(function preventBack() {

    // Push current page into history
    window.history.pushState(
        null,
        "",
        window.location.href
    );

    // Prevent back navigation
    window.onpopstate = function () {

        window.history.pushState(
            null,
            "",
            window.location.href
        );

        /*
        |--------------------------------------------------------------------------
        | OPTIONAL WARNING
        |--------------------------------------------------------------------------
        */

        alert(
            "Back navigation is disabled during interview."
        );
    };

})();

/*
|--------------------------------------------------------------------------
| PREVENT PAGE REFRESH
|--------------------------------------------------------------------------
*/
 

/*
|--------------------------------------------------------------------------
| PREVENT KEYBOARD SHORTCUTS
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "keydown",
    function (e) {

        // Alt + Left Arrow
        if (
            e.altKey &&
            e.key === "ArrowLeft"
        ) {
            e.preventDefault();
        }

        // Backspace outside input
        if (
            e.key === "Backspace" &&
            ![
                "INPUT",
                "TEXTAREA"
            ].includes(
                document.activeElement.tagName
            )
        ) {
            e.preventDefault();
        }

        // F5 / Ctrl+R
        if (
            e.key === "F5" ||
            (
                e.ctrlKey &&
                e.key.toLowerCase() === "r"
            )
        ) {
            e.preventDefault();
        }

    }
);