(function () {
    var sidebar = document.getElementById('sidebar');
    var toggleButton = document.getElementById('sidebar-toggle');
    var hoverZone = document.getElementById('sidebar-hover-zone');
    var backdrop = document.getElementById('sidebar-backdrop');
    var mobileQuery = window.matchMedia('(max-width: 991.98px)');
    var closeDesktopTimer = null;

    if (!sidebar || !toggleButton || !backdrop) {
        return;
    }

    function isMobileView() {
        return mobileQuery.matches;
    }

    function updateToggleA11y(isOpen) {
        toggleButton.setAttribute('aria-label', isOpen ? 'Cerrar menu' : 'Abrir menu');
        toggleButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }

    function closeMobileSidebar() {
        document.body.classList.remove('sidebar-visible');
        updateToggleA11y(false);
    }

    function closeDesktopSidebar() {
        document.body.classList.add('sidebar-hidden');
        updateToggleA11y(false);
    }

    function openDesktopSidebar() {
        document.body.classList.remove('sidebar-hidden');
        updateToggleA11y(true);
    }

    function openMobileSidebar() {
        document.body.classList.add('sidebar-visible');
        updateToggleA11y(true);
    }

    function clearDesktopCloseTimer() {
        if (closeDesktopTimer) {
            clearTimeout(closeDesktopTimer);
            closeDesktopTimer = null;
        }
    }

    function scheduleDesktopClose() {
        if (isMobileView()) {
            return;
        }

        clearDesktopCloseTimer();
        closeDesktopTimer = setTimeout(function () {
            closeDesktopSidebar();
        }, 150);
    }

    function toggleSidebar() {
        if (isMobileView()) {
            if (document.body.classList.contains('sidebar-visible')) {
                closeMobileSidebar();
                return;
            }

            openMobileSidebar();
            return;
        }

        if (document.body.classList.contains('sidebar-hidden')) {
            openDesktopSidebar();
            return;
        }

        closeDesktopSidebar();
    }

    function syncLayoutForViewport() {
        document.body.classList.remove('sidebar-visible');
        clearDesktopCloseTimer();

        if (isMobileView()) {
            closeMobileSidebar();
            return;
        }

        closeDesktopSidebar();
    }

    toggleButton.addEventListener('click', toggleSidebar);

    backdrop.addEventListener('click', closeMobileSidebar);

    if (hoverZone) {
        hoverZone.addEventListener('mouseenter', function () {
            if (isMobileView()) {
                return;
            }

            clearDesktopCloseTimer();
            openDesktopSidebar();
        });
    }

    sidebar.addEventListener('mouseenter', function () {
        if (isMobileView()) {
            return;
        }

        clearDesktopCloseTimer();
        openDesktopSidebar();
    });

    sidebar.addEventListener('mouseleave', function () {
        scheduleDesktopClose();
    });

    document.querySelectorAll('#sidebar a').forEach(function (link) {
        link.addEventListener('click', function () {
            if (isMobileView()) {
                closeMobileSidebar();
            }
        });
    });

    window.addEventListener('resize', syncLayoutForViewport);

    syncLayoutForViewport();
})();
