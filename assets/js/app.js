document.addEventListener("DOMContentLoaded", function () {

    const sidebar = document.getElementById("sidebar");
    const sidebarToggle = document.getElementById("sidebarToggle");

    /*
    |--------------------------------------------------------------------------
    | SIDEBAR TOGGLE
    |--------------------------------------------------------------------------
    */

    if (sidebar && sidebarToggle) {

        sidebarToggle.addEventListener("click", function (event) {

            event.stopPropagation();

            sidebar.classList.toggle("show");

        });

    }


    /*
    |--------------------------------------------------------------------------
    | CLOSE SIDEBAR WHEN CLICKING OUTSIDE
    |--------------------------------------------------------------------------
    */

    document.addEventListener("click", function (event) {

        if (!sidebar || !sidebarToggle) {
            return;
        }

        if (window.innerWidth > 768) {
            return;
        }

        const clickedSidebar =
            sidebar.contains(event.target);

        const clickedToggle =
            sidebarToggle.contains(event.target);

        if (!clickedSidebar && !clickedToggle) {

            sidebar.classList.remove("show");

        }

    });


    /*
    |--------------------------------------------------------------------------
    | CLOSE SIDEBAR AFTER CLICKING A LINK
    |--------------------------------------------------------------------------
    */

    if (sidebar) {

        const links =
            sidebar.querySelectorAll("a");

        links.forEach(function (link) {

            link.addEventListener("click", function () {

                if (window.innerWidth <= 768) {

                    sidebar.classList.remove("show");

                }

            });

        });

    }


    /*
    |--------------------------------------------------------------------------
    | RESET SIDEBAR ON DESKTOP
    |--------------------------------------------------------------------------
    */

    window.addEventListener("resize", function () {

        if (
            window.innerWidth > 768 &&
            sidebar
        ) {

            sidebar.classList.remove("show");

        }

    });

});