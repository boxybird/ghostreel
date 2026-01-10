import './bootstrap';
import htmx from 'htmx.org';

// Feature modules - import to make functions available globally
import './features/search-dialog.js';
import './features/ghost-buttons.js';

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

// Handle htmx:oobErrorNoTarget events gracefully
document.addEventListener('htmx:oobErrorNoTarget', (event) => {
    console.warn('HTMX OOB target not found:', event.detail.content);
    // Optional: Show user-friendly message
    const errorMsg = document.getElementById('htmx-error-message');
    if (errorMsg) {
        errorMsg.textContent = 'Some updates could not be applied.';
        errorMsg.style.display = 'block';
        setTimeout(() => {
            errorMsg.style.display = 'none';
        }, 3000);
    }
});
