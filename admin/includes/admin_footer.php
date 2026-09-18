<?php
/**
 * NetworkTrouble - Admin Layout Footer
 */
?>
        </div><!-- End admin-main-content -->
    </div><!-- End admin-layout-wrapper -->

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Admin Interactive Script -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mobile Sidebar Toggle
        const sidebarToggle = document.getElementById('sidebarToggle');
        const adminSidebar = document.querySelector('.admin-sidebar');
        if (sidebarToggle && adminSidebar) {
            sidebarToggle.addEventListener('click', function() {
                adminSidebar.classList.toggle('show');
            });

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(e) {
                if (window.innerWidth < 992) {
                    if (!adminSidebar.contains(e.target) && !sidebarToggle.contains(e.target) && adminSidebar.classList.contains('show')) {
                        adminSidebar.classList.remove('show');
                    }
                }
            });
        }

        // Auto-dismiss alert messages after 5 seconds
        const autoAlerts = document.querySelectorAll('.alert-dismissible');
        autoAlerts.forEach(function(alert) {
            setTimeout(function() {
                try {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                } catch(e) {}
            }, 5000);
        });
    });
    </script>
</body>
</html>
