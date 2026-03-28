// ================================================================
// MOBILE MENU MANAGEMENT
// ================================================================

class MobileMenuManager {
    constructor() {
        this.isOpen = false;
        this.isMobile = window.innerWidth < 768;
        this.init();
    }

    init() {
        // Watch for window resize
        window.addEventListener('resize', () => {
            const wasMobile = this.isMobile;
            this.isMobile = window.innerWidth < 768;

            // Close menu when resizing to desktop
            if (wasMobile && !this.isMobile && this.isOpen) {
                this.close();
            }
        });

        // Close menu when clicking on a navigation link
        document.addEventListener('click', (e) => {
            const navLink = e.target.closest('[data-page]');
            if (navLink && this.isMobile && this.isOpen) {
                this.close();
            }

            // Toggle button
            const toggleBtn = e.target.closest('.app-sidebar-toggle');
            if (toggleBtn) {
                e.preventDefault();
                this.toggle();
            }

            // Close on backdrop click
            const backdrop = e.target.closest('.app-sidebar-backdrop');
            if (backdrop && this.isOpen) {
                this.close();
            }
        });

        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen && this.isMobile) {
                this.close();
            }
        });
    }

    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        const sidebar = document.querySelector('.app-sidebar');
        const backdrop = document.querySelector('.app-sidebar-backdrop');

        if (sidebar) {
            sidebar.classList.add('is-open');
        }
        if (backdrop) {
            backdrop.classList.add('is-open');
        }

        this.isOpen = true;

        // Prevent body scroll when menu is open
        document.body.style.overflow = 'hidden';
    }

    close() {
        const sidebar = document.querySelector('.app-sidebar');
        const backdrop = document.querySelector('.app-sidebar-backdrop');

        if (sidebar) {
            sidebar.classList.remove('is-open');
        }
        if (backdrop) {
            backdrop.classList.remove('is-open');
        }

        this.isOpen = false;

        // Restore body scroll
        document.body.style.overflow = '';
    }
}

// Initialize mobile menu when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.mobileMenu = new MobileMenuManager();
    });
} else {
    window.mobileMenu = new MobileMenuManager();
}
