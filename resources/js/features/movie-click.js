/**
 * Movie Click Tracking Module
 *
 * Handles logging movie views to the heatmap, updating click count badges,
 * and refreshing the recent views sidebar.
 */

/**
 * Log a movie click/view to the heatmap API
 * @param {HTMLElement} card - The movie card element with data attributes
 */
export async function logMovieClick(card) {
    const movieId = card.dataset.movieId;
    const movieTitle = card.dataset.movieTitle;
    const posterPath = card.dataset.posterPath;

    // Visual feedback on the ghost button
    const ghostButton = card.querySelector('.ghost-action-btn');
    if (ghostButton) {
        ghostButton.classList.add('ghost-pulse-active');
        setTimeout(() => {
            ghostButton.classList.remove('ghost-pulse-active');
        }, 800);
    }

    try {
        const response = await fetch('/clicks', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                tmdb_movie_id: parseInt(movieId),
                movie_title: movieTitle,
                poster_path: posterPath || null,
            }),
        });

        if (response.ok) {
            const data = await response.json();
            updateRecentViews(data.recent_views);
            updateClickCount(card);
        }
    } catch (error) {
        console.error('Failed to log click:', error);
    }
}

/**
 * Log a movie click from search results (doesn't update badge visually)
 * @param {HTMLElement} card - The search result card element
 */
export async function logMovieClickFromSearch(card) {
    const movieId = card.dataset.movieId;
    const movieTitle = card.dataset.movieTitle;
    const posterPath = card.dataset.posterPath;

    // Visual feedback on the ghost button
    const ghostButton = card.querySelector('.ghost-action-btn');
    if (ghostButton) {
        ghostButton.classList.add('ghost-pulse-active');
        setTimeout(() => {
            ghostButton.classList.remove('ghost-pulse-active');
        }, 800);
    }

    try {
        const response = await fetch('/clicks', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                tmdb_movie_id: parseInt(movieId),
                movie_title: movieTitle,
                poster_path: posterPath || null,
            }),
        });

        if (response.ok) {
            const data = await response.json();
            updateRecentViews(data.recent_views);
        }
    } catch (error) {
        console.error('Failed to log click:', error);
    }
}

/**
 * Update the recent views sidebar with new data
 * @param {Array} recentViews - Array of recent view objects
 */
export function updateRecentViews(recentViews) {
    const sidebar = document.getElementById('recent-views-sidebar');
    if (!sidebar || !recentViews) return;

    sidebar.innerHTML = recentViews.map(view => `
        <div class="flex items-center gap-2 px-2 py-1.5 rounded-lg bg-white/5">
            ${view.poster_url
                ? `<img src="${view.poster_url}" alt="${view.movie_title}" class="w-8 h-12 rounded object-cover">`
                : `<div class="w-8 h-12 rounded bg-dark-card flex items-center justify-center">
                    <svg class="w-4 h-4 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                   </div>`
            }
            <div class="hidden lg:block flex-1 min-w-0">
                <p class="text-xs font-medium truncate">${view.movie_title}</p>
                <p class="text-xs text-text-muted">${view.clicked_at}</p>
            </div>
        </div>
    `).join('');
}

/**
 * Update the click count badge on a movie card
 * @param {HTMLElement} card - The movie card element
 */
export function updateClickCount(card) {
    // Badge is now top-left
    let badge = card.querySelector('[class*="absolute top-2 left-2"]');
    if (badge && badge.classList.contains('z-20')) {
        const currentCount = parseInt(badge.textContent) || 0;
        const newCount = currentCount + 1;
        badge.textContent = `${newCount} ${newCount === 1 ? 'view' : 'views'}`;

        // Update badge color based on count
        badge.className = badge.className.replace(/bg-neon-\w+/g, '');
        if (newCount > 5) {
            badge.classList.add('bg-neon-pink', 'text-white');
        } else if (newCount > 2) {
            badge.classList.add('bg-neon-orange', 'text-dark-bg');
        } else {
            badge.classList.add('bg-neon-cyan', 'text-dark-bg');
        }
    } else {
        // Create new badge (top-left position)
        badge = document.createElement('div');
        badge.className = 'absolute top-2 left-2 z-20 px-2 py-1 rounded-full text-xs font-bold bg-neon-cyan text-dark-bg';
        badge.textContent = '1 view';
        card.appendChild(badge);
    }
}

// Make functions available globally for inline onclick handlers
window.logMovieClick = logMovieClick;
window.logMovieClickFromSearch = logMovieClickFromSearch;
window.updateRecentViews = updateRecentViews;
window.updateClickCount = updateClickCount;
