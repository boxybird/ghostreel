/**
 * Ghost Click Debounce Module
 *
 * Prevents multiple rapid clicks on ghost action buttons by debouncing them.
 * Each button can only be clicked once within a specified cooldown period.
 *
 * Note: Debouncing is handled server-side via unique constraint.
 * This module is kept for potential future client-side improvements.
 */

const COOLDOWN_MS = 500;
const disabledAttribute = 'data-ghost-disabled';

/**
 * Initialize ghost button debouncing
 */
export function initGhostButtons() {
    const ghostButtons = document.querySelectorAll('.ghost-action-btn');

    ghostButtons.forEach((button) => {
        button.addEventListener('htmx:beforeRequest', handleHtmxBeforeRequest);
    });
}

/**
 * Handle HTMX before request to add cooldown
 */
function handleHtmxBeforeRequest(event) {
    const button = event.target;

    if (button.hasAttribute(disabledAttribute)) {
        event.preventDefault();
        return false;
    }

    button.setAttribute(disabledAttribute, 'true');

    setTimeout(() => {
        button.removeAttribute(disabledAttribute);
    }, COOLDOWN_MS);
}

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initGhostButtons);
} else {
    initGhostButtons();
}
