/**
 * Movie Grid Module
 *
 * Handles adding movies to the grid from search results,
 * highlighting cards, and creating movie card HTML.
 */

import { closeSearchDialog } from './search-dialog.js';

/**
 * Escape HTML special characters to prevent XSS
 * @param {string} text - The text to escape
 * @returns {string} The escaped text
 */
export function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Highlight a movie card temporarily
 * @param {HTMLElement} card - The card element to highlight
 */
export function highlightCard(card) {
    card.classList.add('ring-2', 'ring-neon-cyan', 'shadow-lg', 'shadow-neon-cyan/40');
    setTimeout(() => {
        card.classList.remove('ring-2', 'ring-neon-cyan', 'shadow-lg', 'shadow-neon-cyan/40');
    }, 2000);
}

/**
 * Create HTML for a movie card
 * @param {string|number} movieId - TMDB movie ID
 * @param {string} movieTitle - Movie title
 * @param {string|null} posterPath - TMDB poster path
 * @param {string|null} posterUrl - Full poster URL
 * @param {number} voteAverage - Movie rating
 * @param {string|number|null} dbId - Database ID for linking
 * @returns {string} HTML string for the movie card
 */
export function createMovieCardHtml(movieId, movieTitle, posterPath, posterUrl, voteAverage, dbId) {
    const posterContent = posterUrl
        ? `<img src="${posterUrl}" alt="${escapeHtml(movieTitle)}" class="w-full aspect-[2/3] object-cover" loading="lazy">`
        : `<div class="w-full aspect-[2/3] bg-dark-surface flex items-center justify-center">
            <svg class="w-12 h-12 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
           </div>`;

    const ratingContent = voteAverage > 0
        ? `<div class="flex items-center gap-1 mt-1">
            <svg class="w-3.5 h-3.5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
            </svg>
            <span class="text-xs text-text-muted">${voteAverage.toFixed(1)}</span>
           </div>`
        : '';

    const eyeButton = `
        <button
            type="button"
            onclick="event.preventDefault(); event.stopPropagation(); logMovieClick(this.closest('.movie-card'));"
            class="absolute top-2 right-2 z-30 p-2 rounded-full bg-dark-bg/70 backdrop-blur-sm border border-white/10 text-text-muted hover:bg-neon-cyan hover:text-dark-bg hover:border-neon-cyan transition-all duration-200"
            title="Log view to heatmap"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
        </button>`;

    // If we have a db_id, wrap in a link; otherwise just a div
    const wrapperTag = dbId ? 'a' : 'div';
    const hrefAttr = dbId ? `href="/movies/${dbId}"` : '';

    return `
        <${wrapperTag}
            ${hrefAttr}
            class="movie-card group relative rounded-xl overflow-hidden bg-dark-card transition-all duration-300 hover:scale-105 hover:shadow-xl hover:shadow-neon-cyan/20 focus:outline-none focus:ring-2 focus:ring-neon-cyan/50"
            data-movie-id="${movieId}"
            data-movie-title="${escapeHtml(movieTitle)}"
            data-poster-path="${posterPath || ''}"
        >
            ${posterContent}
            ${eyeButton}
            <div class="absolute inset-0 bg-gradient-to-t from-dark-bg via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none">
                <div class="absolute bottom-0 left-0 right-0 p-3">
                    <h3 class="text-sm font-semibold line-clamp-2">${escapeHtml(movieTitle)}</h3>
                    ${ratingContent}
                </div>
            </div>
        </${wrapperTag}>
    `;
}

/**
 * Add a movie to the grid from search results
 * @param {HTMLElement} card - The search result card element with data attributes
 */
export function addMovieToGrid(card) {
    const movieId = card.dataset.movieId;
    const movieTitle = card.dataset.movieTitle;
    const posterPath = card.dataset.posterPath;
    const posterUrl = card.dataset.posterUrl;
    const voteAverage = parseFloat(card.dataset.voteAverage) || 0;
    const dbId = card.dataset.dbId || null;

    // Close dialog first
    closeSearchDialog();

    // Check if movie already exists in grid
    const existingCard = document.querySelector(`#movie-grid [data-movie-id="${movieId}"]`);
    if (existingCard) {
        // Scroll to existing card and highlight it
        setTimeout(() => {
            existingCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
            highlightCard(existingCard);
        }, 200);
        return;
    }

    // Create new movie card HTML
    const cardHtml = createMovieCardHtml(movieId, movieTitle, posterPath, posterUrl, voteAverage, dbId);

    // Prepend to grid
    const grid = document.getElementById('movie-grid');
    if (!grid) return;

    grid.insertAdjacentHTML('afterbegin', cardHtml);

    // Get the newly added card and highlight
    const newCard = grid.firstElementChild;
    setTimeout(() => {
        newCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
        highlightCard(newCard);
    }, 200);
}

// Make functions available globally for inline onclick handlers
window.addMovieToGrid = addMovieToGrid;
window.highlightCard = highlightCard;
window.createMovieCardHtml = createMovieCardHtml;
window.escapeHtml = escapeHtml;
