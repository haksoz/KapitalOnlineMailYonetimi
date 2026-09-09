import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('appShell', () => ({
    sidebarOpen: false,
    navGroups: {
        master: false,
        subscriptions: false,
        admin: false,
        integration: false,
    },
    init() {
        try {
            const saved = JSON.parse(sessionStorage.getItem('navGroups') || 'null');
            if (! saved || typeof saved !== 'object') {
                return;
            }

            for (const key of Object.keys(this.navGroups)) {
                if (typeof saved[key] === 'boolean') {
                    this.navGroups[key] = saved[key];
                }
            }
        } catch (e) {
            // Keep default collapsed groups if storage is unreadable.
        }
    },
    toggleNavGroup(key) {
        if (! Object.prototype.hasOwnProperty.call(this.navGroups, key)) {
            return;
        }

        this.navGroups[key] = ! this.navGroups[key];
        sessionStorage.setItem('navGroups', JSON.stringify(this.navGroups));
    },
}));

Alpine.start();
