        </main>
        <footer class="admin-footer border-top bg-white">
        <div class="container-fluid text-center">
            <p class="text-muted mb-0 small">&copy; <?= date('Y') ?> HireMatrix AI Job Portal - Admin Control Center</p>
        </div>
        </footer>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            const sidebar = document.getElementById('adminSidebar');
            const toggle = document.getElementById('adminMenuToggle');
            const close = document.getElementById('adminSidebarClose');
            const overlay = document.getElementById('adminSidebarOverlay');
            if (!sidebar || !toggle || !overlay) return;
            const setOpen = function (open) {
                sidebar.classList.toggle('is-open', open);
                overlay.classList.toggle('is-open', open);
                document.body.classList.toggle('admin-nav-open', open);
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            };
            toggle.addEventListener('click', function () { setOpen(!sidebar.classList.contains('is-open')); });
            overlay.addEventListener('click', function () { setOpen(false); });
            if (close) close.addEventListener('click', function () { setOpen(false); });
            document.addEventListener('keydown', function (event) { if (event.key === 'Escape') setOpen(false); });
        })();
    </script>
</body>
</html>
