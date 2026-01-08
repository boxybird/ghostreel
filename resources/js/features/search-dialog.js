/**
 * Search Dialog Module
 *
 * Handles opening, closing, and keyboard interactions for the movie search dialog.
 */

const DIALOG_ID = 'search-dialog';
const INPUT_ID = 'search-input';
const RESULTS_ID = 'search-results';

/**
 * Default empty state HTML for search results
 */
const EMPTY_STATE_HTML = `
    <div class="flex flex-col items-center justify-center py-16 text-text-muted">
        <svg class="w-16 h-16 mb-4 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <p class="text-lg font-medium mb-1">Search for movies</p>
        <p class="text-sm opacity-70">Start typing to find movies to add to your grid</p>
    </div>
`;

/**
 * Open the search dialog and focus the input
 */
export function openSearchDialog() {
    const dialog = document.getElementById(DIALOG_ID);
    if (!dialog) return;

    dialog.showModal();

    // Focus and select input after animation
    setTimeout(() => {
        const input = document.getElementById(INPUT_ID);
        if (input) {
            input.focus();
            input.select();
        }
    }, 50);
}

/**
 * Close the search dialog and reset its state
 */
export function closeSearchDialog() {
    const dialog = document.getElementById(DIALOG_ID);
    if (!dialog) return;

    dialog.close();

    // Reset input and results
    const input = document.getElementById(INPUT_ID);
    if (input) {
        input.value = '';
    }

    const results = document.getElementById(RESULTS_ID);
    if (results) {
        results.innerHTML = EMPTY_STATE_HTML;
    }
}

/**
 * Initialize search dialog event listeners
 * Call this on DOMContentLoaded
 */
export function initSearchDialog() {
    const dialog = document.getElementById(DIALOG_ID);
    if (!dialog) return;

    // Close dialog when clicking on backdrop
    dialog.addEventListener('click', function(e) {
        if (e.target === this) {
            closeSearchDialog();
        }
    });

    // Keyboard shortcut: Cmd/Ctrl + K to open search
    document.addEventListener('keydown', function(e) {
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault();
            openSearchDialog();
        }
    });
}

// Make functions available globally for inline onclick handlers
window.openSearchDialog = openSearchDialog;
window.closeSearchDialog = closeSearchDialog;

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSearchDialog);
} else {
    initSearchDialog();
}
