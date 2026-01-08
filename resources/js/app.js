import './bootstrap';
import 'htmx.org';

// Make htmx available globally
window.htmx = require('htmx.org');

// Configure HTMX to include CSRF token in all requests
document.addEventListener('DOMContentLoaded', () => {
    document.body.addEventListener('htmx:configRequest', (event) => {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        if (csrfToken) {
            event.detail.headers['X-CSRF-TOKEN'] = csrfToken;
        }
    });
});
