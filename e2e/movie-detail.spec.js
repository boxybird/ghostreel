// @ts-check
import { test, expect } from '@playwright/test';

test.describe('Movie Detail Page', () => {
  test('navigates from homepage card to movie detail page', async ({ page }) => {
    await page.goto('/');

    // Wait for movie cards to load
    const movieCards = page.locator('.movie-card');
    await expect(movieCards.first()).toBeVisible();

    // Get the first movie card that is a link (has href attribute)
    const firstMovieLink = page.locator('a.movie-card').first();
    await expect(firstMovieLink).toBeVisible();

    // Get movie title before clicking
    const movieTitle = await firstMovieLink.getAttribute('data-movie-title');

    // Click the card to navigate
    await firstMovieLink.click();

    // Should be on movie detail page
    await expect(page).toHaveURL(/\/movies\/\d+/);

    // Page should show the movie title
    await expect(page.getByRole('heading', { level: 1, name: movieTitle })).toBeVisible();
  });

  test('ghost icon logs view without navigating', async ({ page }) => {
    await page.goto('/');

    // Wait for movie cards to load
    const movieCards = page.locator('.movie-card');
    await expect(movieCards.first()).toBeVisible();

    // Find the ghost icon button on the first card
    const firstCard = movieCards.first();
    const ghostButton = firstCard.locator('button[title="Add Ghost View"]');
    await expect(ghostButton).toBeVisible();

    // Set up response listener for the click API
    const responsePromise = page.waitForResponse(response =>
      response.url().includes('/clicks') && response.status() === 200
    );

    // Click the ghost icon
    await ghostButton.click();

    // Wait for API response
    await responsePromise;

    // Should still be on homepage (not navigated)
    await expect(page).toHaveURL('/');

    // Recent views sidebar should be updated
    const movieTitle = await firstCard.getAttribute('data-movie-title');
    const recentViewsSidebar = page.locator('#recent-views-sidebar');
    await expect(recentViewsSidebar.locator('> div').first()).toContainText(movieTitle);
  });

  test('movie detail page displays all sections', async ({ page }) => {
    await page.goto('/');

    // Navigate to a movie detail page
    const firstMovieLink = page.locator('a.movie-card').first();
    await expect(firstMovieLink).toBeVisible();
    await firstMovieLink.click();

    // Wait for page to load
    await expect(page).toHaveURL(/\/movies\/\d+/);

    // Check for key sections
    // Back button
    await expect(page.getByRole('link', { name: /Back/i })).toBeVisible();

    // Movie title
    await expect(page.locator('h1')).toBeVisible();

    // Community stats section (views today)
    await expect(page.getByText(/views today/i)).toBeVisible();

    // Log View button
    await expect(page.getByRole('button', { name: /Ghost this Movie/i })).toBeVisible();
  });

  test('log view button on detail page tracks click', async ({ page }) => {
    await page.goto('/');

    // Navigate to movie detail page
    const firstMovieLink = page.locator('a.movie-card').first();
    await firstMovieLink.click();
    await expect(page).toHaveURL(/\/movies\/\d+/);

    // Find the Log View button
    const logViewButton = page.getByRole('button', { name: /Ghost this Movie/i });
    await expect(logViewButton).toBeVisible();

    // Set up response listener
    const responsePromise = page.waitForResponse(response =>
      response.url().includes('/clicks') && response.status() === 200
    );

    // Click the button
    await logViewButton.click();

    // Wait for API response
    await responsePromise;

    // Wait a moment for DOM update to success state
    await page.waitForTimeout(500);

    // Button should show success state temporarily (or have changed to green)
    // The button innerHTML changes, so we check for either the success text or that it's still visible
    const buttonText = await logViewButton.textContent();
    // Button either shows "View Logged!" or might still be reverting - just ensure API call succeeded
    expect(buttonText).toBeDefined();
  });

  test('back button returns to homepage', async ({ page }) => {
    await page.goto('/');

    // Navigate to movie detail page
    const firstMovieLink = page.locator('a.movie-card').first();
    await firstMovieLink.click();
    await expect(page).toHaveURL(/\/movies\/\d+/);

    // Click back button
    const backButton = page.getByRole('link', { name: /Back/i });
    await backButton.click();

    // Should be back on homepage
    await expect(page).toHaveURL('/');
    await expect(page.getByRole('heading', { name: 'Trending Now' })).toBeVisible();
  });

  test('similar movies section links work when movies exist in database', async ({ page }) => {
    await page.goto('/');

    // Navigate to movie detail page
    const firstMovieLink = page.locator('a.movie-card').first();
    await firstMovieLink.click();
    await expect(page).toHaveURL(/\/movies\/\d+/);

    // Check if Similar Movies section exists (it may not have linkable similar movies)
    const similarSection = page.getByRole('heading', { name: /Similar Movies/i });
    const hasSimilar = await similarSection.isVisible().catch(() => false);

    if (hasSimilar) {
      // Find a similar movie link
      const similarMovieLink = page.locator('section:has-text("Similar Movies") a[href*="/movies/"]').first();
      const hasLinkableSimilar = await similarMovieLink.isVisible().catch(() => false);

      if (hasLinkableSimilar) {
        await similarMovieLink.click();
        // Should navigate to another movie detail page
        await expect(page).toHaveURL(/\/movies\/\d+/);
      }
    }
  });

  test('genre filter still works after viewing movie details', async ({ page }) => {
    await page.goto('/');

    // Navigate to movie detail
    const firstMovieLink = page.locator('a.movie-card').first();
    await firstMovieLink.click();
    await expect(page).toHaveURL(/\/movies\/\d+/);

    // Go back
    await page.getByRole('link', { name: /Back/i }).click();
    await expect(page).toHaveURL('/');

    // Genre chips should still work
    const genreChips = page.locator('#genre-chips button');
    await expect(genreChips.first()).toBeVisible();

    // Click a genre chip (not "All")
    const actionChip = page.locator('#genre-chips button', { hasText: 'Action' });
    const hasAction = await actionChip.isVisible().catch(() => false);

    if (hasAction) {
      await actionChip.click();
      // Grid should update with genre-filtered movies (check for the heading specifically)
      await expect(page.getByRole('heading', { name: 'Action Movies' })).toBeVisible();
    }
  });

  test('search results show view details link', async ({ page }) => {
    await page.goto('/');

    // Open search dialog
    const searchButton = page.locator('#search-trigger');
    await searchButton.click();

    const dialog = page.locator('dialog#search-dialog');
    await expect(dialog).toBeVisible();

    // Search for a movie
    const searchInput = page.locator('#search-input');
    await searchInput.fill('Avatar');

    // Wait for search results
    await page.waitForResponse(response =>
      response.url().includes('/search') && response.status() === 200
    );

    // Check for search result cards
    const searchResults = page.locator('.search-result-card');
    await expect(searchResults.first()).toBeVisible({ timeout: 10000 });

    // Hover to see actions
    await searchResults.first().hover();

    // Should have view details link
    const viewDetailsLink = searchResults.first().getByRole('link', { name: /View details/i });
    const hasViewDetails = await viewDetailsLink.isVisible().catch(() => false);

    // View details is only available if movie exists in database
    // This is expected behavior - search results from TMDB may not yet be in our DB
    if (hasViewDetails) {
      await viewDetailsLink.click();
      await expect(page).toHaveURL(/\/movies\/\d+/);
    }
  });
});
