document.addEventListener('DOMContentLoaded', function () {
    // ===== DELETE CONFIRMATION =====
    const confirmDeleteButtons = document.querySelectorAll('.confirm-delete');
    confirmDeleteButtons.forEach(function (button) {
        button.addEventListener('click', function (event) {
            if (!confirm('Are you sure you want to delete this task?')) {
                event.preventDefault();
            }
        });
    });

    // ===== SIDEBAR TOGGLE =====
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const body = document.body;
    const sidebar = document.querySelector('.sidebar');

    function setSidebarOpen(isOpen) {
        body.classList.toggle('sidebar-closed', !isOpen);
        if (sidebarOverlay) {
            sidebarOverlay.classList.toggle('active', !isOpen);
        }
        if (sidebarToggle) {
            sidebarToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        }
        localStorage.setItem('sidebarState', isOpen ? 'open' : 'closed');
    }

    const sidebarState = localStorage.getItem('sidebarState');
    if (sidebarState === 'closed') {
        setSidebarOpen(false);
    } else {
        setSidebarOpen(true);
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const isClosed = body.classList.contains('sidebar-closed');
            setSidebarOpen(!isClosed);
        });
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function () {
            setSidebarOpen(false);
        });
    }

    if (sidebar && window.innerWidth <= 768) {
        document.addEventListener('click', function (event) {
            if (!sidebar.contains(event.target) && !sidebarToggle?.contains(event.target)) {
                setSidebarOpen(false);
            }
        });
    }

    // ===== FORM ENHANCEMENTS =====
    const formInputs = document.querySelectorAll('input, textarea, select');
    formInputs.forEach(input => {
        input.addEventListener('focus', function () {
            this.parentElement.classList.add('focused');
        });
        input.addEventListener('blur', function () {
            this.parentElement.classList.remove('focused');
        });
    });

    // ===== NOTIFICATION HANDLER =====
    const notificationClose = document.querySelectorAll('.notification-close');
    notificationClose.forEach(closeBtn => {
        closeBtn.addEventListener('click', function () {
            this.parentElement.style.opacity = '0';
            setTimeout(() => {
                this.parentElement.style.display = 'none';
            }, 300);
        });
    });

    // Auto-close notifications after 5 seconds
    const notifications = document.querySelectorAll('.alert');
    notifications.forEach(notification => {
        if (notification.classList.contains('success')) {
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => {
                    notification.style.display = 'none';
                }, 300);
            }, 5000);
        }
    });
});
