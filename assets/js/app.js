/**
 * NexusGaming Plain JavaScript Helpers
 * No external JS frameworks - Plain ES6
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Navigation Menu Toggle
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');

    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', () => {
            const isHidden = mobileMenu.classList.contains('hidden');
            if (isHidden) {
                mobileMenu.classList.remove('hidden');
                mobileMenuBtn.setAttribute('aria-expanded', 'true');
            } else {
                mobileMenu.classList.add('hidden');
                mobileMenuBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // 2. Modals (data-modal-target & data-modal-close)
    document.querySelectorAll('[data-modal-target]').forEach(trigger => {
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            const targetId = trigger.getAttribute('data-modal-target');
            const modal = document.getElementById(targetId);
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach(closer => {
        closer.addEventListener('click', () => {
            const modal = closer.closest('.modal-overlay');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        });
    });

    // Close modal when clicking backdrop
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                overlay.classList.add('hidden');
                overlay.classList.remove('flex');
            }
        });
    });

    // 3. Tab switching (data-tab-group & data-tab-target)
    document.querySelectorAll('[data-tab-target]').forEach(tabBtn => {
        tabBtn.addEventListener('click', () => {
            const group = tabBtn.getAttribute('data-tab-group');
            const target = tabBtn.getAttribute('data-tab-target');

            // Deactivate all buttons in group
            document.querySelectorAll(`[data-tab-group="${group}"]`).forEach(btn => {
                btn.classList.remove('bg-blue-600', 'text-white', 'border-blue-500');
                btn.classList.add('bg-slate-800/60', 'text-slate-400', 'border-slate-700/60');
            });

            // Activate clicked button
            tabBtn.classList.add('bg-blue-600', 'text-white', 'border-blue-500');
            tabBtn.classList.remove('bg-slate-800/60', 'text-slate-400', 'border-slate-700/60');

            // Hide all tab panes in group
            document.querySelectorAll(`[data-tab-pane="${group}"]`).forEach(pane => {
                pane.classList.add('hidden');
            });

            // Show selected pane
            const activePane = document.getElementById(target);
            if (activePane) {
                activePane.classList.remove('hidden');
            }
        });
    });

    // 4. Quick confirmation prompts for destructive actions
    document.querySelectorAll('[data-confirm]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const message = btn.getAttribute('data-confirm') || 'Are you sure you want to proceed?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
});
