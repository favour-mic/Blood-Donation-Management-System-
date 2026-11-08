            </div>
        </div>
    </div>
    <div class="app-sidebar-backdrop" id="appSidebarBackdrop" hidden></div>
    <script>
        (function () {
            var button = document.getElementById("appMenuToggle");
            var sidebar = document.getElementById("appSidebar");
            var backdrop = document.getElementById("appSidebarBackdrop");
            if (!button || !sidebar || !backdrop) return;
            function closeMenu() {
                sidebar.classList.remove("is-open");
                backdrop.hidden = true;
                button.setAttribute("aria-expanded", "false");
            }
            button.addEventListener("click", function () {
                var open = sidebar.classList.toggle("is-open");
                backdrop.hidden = !open;
                button.setAttribute("aria-expanded", String(open));
            });
            backdrop.addEventListener("click", closeMenu);
            document.addEventListener("keydown", function (event) {
                if (event.key === "Escape") closeMenu();
            });
        }());
    </script>
</body>
</html>
