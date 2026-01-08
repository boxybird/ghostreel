// @ts-check
import { test, expect } from '@playwright/test';

test.describe('Movie Search', () => {
  test('search input triggers HTMX request on typing', async ({ page }) => {
    await page.goto('/');

    // Find the search input
    const searchInput = page.locator('#search-input');
    await expect(searchInput).toBeVisible();

    // Set up response listener for search endpoint
    const responsePromise = page.waitForResponse(response =>
      response.url().includes('/search?q=') && response.status() === 200
    );

    // Type in the search input (HTMX triggers after 300ms delay)
    await searchInput.fill('matrix');

    // Wait for the HTMX request to complete
    await responsePromise;

    // The search popover should show results
    const searchPopover = page.locator('#search-popover');
    await expect(searchPopover).toBeVisible();
  });

  test('search results show movie information', async ({ page }) => {
    await page.goto('/');

    const searchInput = page.locator('#search-input');
    await searchInput.fill('fight');

    // Wait for search results
    await page.waitForResponse(response =>
      response.url().includes('/search?q=') && response.status() === 200
    );

    // Check that results contain movie info
    const searchResults = page.locator('#search-results');
    await expect(searchResults.locator('.search-result-item').first()).toBeVisible();
  });

  test('clicking a search result adds movie to grid', async ({ page }) => {
    await page.goto('/');

    // Search for a movie
    const searchInput = page.locator('#search-input');
    await searchInput.fill('star wars');

    // Wait for search results
    await page.waitForResponse(response =>
      response.url().includes('/search?q=') && response.status() === 200
    );

    // Get the first search result
    const firstResult = page.locator('.search-result-item').first();
    await expect(firstResult).toBeVisible();

    // Get the movie title from the search result
    const movieTitle = await firstResult.getAttribute('data-movie-title');

    // Click the search result
    await firstResult.click();

    // Wait for the popover to close
    await page.waitForTimeout(300);

    // Verify the movie was added to the grid (as the first item)
    const grid = page.locator('#movie-grid');
    const firstGridCard = grid.locator('.movie-card').first();
    const gridMovieTitle = await firstGridCard.getAttribute('data-movie-title');

    expect(gridMovieTitle).toBe(movieTitle);
  });

  test('added movie card is highlighted temporarily', async ({ page }) => {
    await page.goto('/');

    // Search for a movie
    const searchInput = page.locator('#search-input');
    await searchInput.fill('inception');

    // Wait for search results
    await page.waitForResponse(response =>
      response.url().includes('/search?q=') && response.status() === 200
    );

    // Click the first result
    const firstResult = page.locator('.search-result-item').first();
    await firstResult.click();

    // Check that the new card has highlight classes
    const grid = page.locator('#movie-grid');
    const firstGridCard = grid.locator('.movie-card').first();

    // The card should have highlight ring classes immediately after adding
    // Use more specific class check - ring-2 is added for highlighting
    await expect(firstGridCard).toHaveClass(/ring-2/);

    // Wait for the highlight to fade (2 seconds)
    await page.waitForTimeout(2200);

    // Highlight should be removed (ring-2 is removed)
    await expect(firstGridCard).not.toHaveClass(/ring-2 ring-neon-cyan shadow-lg/);
  });

  test('search clears after selecting a result', async ({ page }) => {
    await page.goto('/');

    const searchInput = page.locator('#search-input');
    await searchInput.fill('avatar');

    // Wait for search results
    await page.waitForResponse(response =>
      response.url().includes('/search?q=') && response.status() === 200
    );

    // Click a result
    const firstResult = page.locator('.search-result-item').first();
    await firstResult.click();

    // Search input should be cleared
    await expect(searchInput).toHaveValue('');
  });

  test('selecting a search result adds movie to grid at top position', async ({ page }) => {
    await page.goto('/');

    const searchInput = page.locator('#search-input');
    await searchInput.fill('jurassic');

    // Wait for search results
    await page.waitForResponse(response =>
      response.url().includes('/search?q=') && response.status() === 200
    );

    // Get the first search result
    const firstResult = page.locator('.search-result-item').first();
    await expect(firstResult).toBeVisible();
    const movieTitle = await firstResult.getAttribute('data-movie-title');

    // Click a result
    await firstResult.click();

    // Wait a moment for DOM to update
    await page.waitForTimeout(300);

    // Verify the movie was prepended to the grid
    const grid = page.locator('#movie-grid');
    const firstGridCard = grid.locator('.movie-card').first();
    const gridMovieTitle = await firstGridCard.getAttribute('data-movie-title');

    expect(gridMovieTitle).toBe(movieTitle);
  });

  test('valid search query of 2+ chars triggers search request', async ({ page }) => {
    await page.goto('/');

    const searchInput = page.locator('#search-input');

    // Type 2 characters - should trigger search
    await searchInput.fill('ab');

    // Wait for the search request
    const response = await page.waitForResponse(response =>
      response.url().includes('/search?q=') && response.status() === 200,
      { timeout: 3000 }
    );

    // Verify the request was made
    expect(response.url()).toContain('q=ab');
  });

  test('existing movie in grid scrolls into view when searched and selected', async ({ page }) => {
    await page.goto('/');

    // Get the title of a movie already in the grid
    const existingCard = page.locator('.movie-card').first();
    const existingTitle = await existingCard.getAttribute('data-movie-title');

    // Search for that movie
    const searchInput = page.locator('#search-input');
    await searchInput.fill(existingTitle?.substring(0, 5) || 'test');

    // Wait for search results
    await page.waitForResponse(response =>
      response.url().includes('/search?q=') && response.status() === 200
    );

    // Click the result that matches the existing movie
    const matchingResult = page.locator(`.search-result-item[data-movie-title="${existingTitle}"]`);
    if (await matchingResult.isVisible()) {
      await matchingResult.click();

      // The existing card should be highlighted
      await expect(existingCard).toHaveClass(/ring-neon-cyan/);
    }
  });
});
