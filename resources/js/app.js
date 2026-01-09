import './bootstrap';
import htmx from 'htmx.org';

// Feature modules - import to make functions available globally
import './features/movie-click.js';
import './features/search-dialog.js';

// Make htmx available globally
window.htmx = htmx;

// Configure HTMX to include CSRF token in all requests
document.addEventListener('DOMContentLoaded', () => {
    document.body.addEventListener('htmx:configRequest', (event) => {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        if (csrfToken) {
            event.detail.headers['X-CSRF-TOKEN'] = csrfToken;
        }
    });
});
