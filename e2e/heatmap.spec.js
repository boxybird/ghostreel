// @ts-check
import { test, expect } from '@playwright/test';

test.describe('Movie Heatmap', () => {
  test('displays trending movies on homepage', async ({ page }) => {
    await page.goto('/');

    // Check the page title contains "Who's Watching"
    await expect(page).toHaveTitle(/Who's Watching/);

    // Check for the main heading
    await expect(page.getByRole('heading', { name: 'Trending Now' })).toBeVisible();

    // Check for movie cards
    const movieCards = page.locator('.movie-card');
    await expect(movieCards.first()).toBeVisible();
  });

  test('clicking eye icon updates the Recent Views sidebar', async ({ page }) => {
    await page.goto('/');

    // Wait for movie cards to load
    const movieCards = page.locator('.movie-card');
    await expect(movieCards.first()).toBeVisible();

    // Get the movie title before clicking
    const firstMovieCard = movieCards.first();
    const movieTitle = await firstMovieCard.getAttribute('data-movie-title');

    // Count initial recent views
    const recentViewsSidebar = page.locator('#recent-views-sidebar');
    const initialRecentViewsCount = await recentViewsSidebar.locator('> div').count();

    // Find the eye icon button
    const eyeButton = firstMovieCard.locator('button[title="Log view to heatmap"]');
    await expect(eyeButton).toBeVisible();

    // Set up response listener BEFORE clicking
    const responsePromise = page.waitForResponse(response =>
      response.url().includes('/click') && response.status() === 200
    );

    // Click the eye icon (not the card, which now navigates)
    await eyeButton.click();

    // Wait for the API call to complete
    await responsePromise;

    // Verify the sidebar updated with the new view
    await expect(recentViewsSidebar.locator('> div').first()).toContainText(movieTitle);

    // Verify the view count increased (or was added)
    const newRecentViewsCount = await recentViewsSidebar.locator('> div').count();
    expect(newRecentViewsCount).toBeGreaterThanOrEqual(initialRecentViewsCount);
  });

  test('movie card shows click count badge after clicking eye icon', async ({ page }) => {
    await page.goto('/');

    // Wait for movie cards to load
    const movieCards = page.locator('.movie-card');
    await expect(movieCards.first()).toBeVisible();

    // Find a movie card
    const firstMovieCard = movieCards.first();

    // Find the eye icon button
    const eyeButton = firstMovieCard.locator('button[title="Log view to heatmap"]');
    await expect(eyeButton).toBeVisible();

    // Set up response listener before clicking
    const responsePromise = page.waitForResponse(response =>
      response.url().includes('/click') && response.status() === 200
    );

    // Click the eye icon (not the card itself, which now navigates)
    await eyeButton.click();

    // Wait for the API response
    await responsePromise;

    // Check that a view badge appears on the clicked card (now top-left)
    const badge = firstMovieCard.locator('[class*="absolute top-2 left-2"]');
    await expect(badge).toBeVisible({ timeout: 5000 });
    await expect(badge).toContainText(/view/);
  });

  test('sidebar navigation is visible', async ({ page }) => {
    await page.goto('/');

    // Check for sidebar navigation links
    await expect(page.getByRole('link', { name: /Trending/i })).toBeVisible();
    await expect(page.getByRole('link', { name: /Popular/i })).toBeVisible();
  });

  test('search button is present and opens dialog', async ({ page }) => {
    await page.goto('/');

    // Check for search trigger button (icon button with keyboard shortcut)
    const searchButton = page.locator('#search-trigger');
    await expect(searchButton).toBeVisible();
    await expect(searchButton).toHaveAttribute('aria-label', 'Search movies');
    await expect(searchButton).toContainText('K'); // keyboard shortcut badge

    // Click to open dialog
    await searchButton.click();

    // Dialog should open
    const dialog = page.locator('dialog#search-dialog');
    await expect(dialog).toBeVisible();
  });

  test('clicking eye icon increments the view count badge', async ({ page }) => {
    await page.goto('/');

    // Wait for movie cards to load
    const movieCards = page.locator('.movie-card');
    await expect(movieCards.first()).toBeVisible();

    const firstMovieCard = movieCards.first();

    // Badge is now top-left (top-right is the eye icon)
    const badge = firstMovieCard.locator('[class*="absolute top-2 left-2"]');
    const badgeExisted = await badge.isVisible();
    let initialCount = 0;
    if (badgeExisted) {
      const text = await badge.textContent();
      const match = text?.match(/(\d+)/);
      initialCount = match ? parseInt(match[1]) : 0;
    }

    // Find eye icon
    const eyeButton = firstMovieCard.locator('button[title="Log view to heatmap"]');
    await expect(eyeButton).toBeVisible();

    // Set up response listener BEFORE clicking
    const responsePromise = page.waitForResponse(response =>
      response.url().includes('/click') && response.status() === 200
    );

    // Click the eye icon
    await eyeButton.click();

    // Wait for API response
    await responsePromise;

    // Wait a moment for DOM update
    await page.waitForTimeout(200);

    // Verify the count incremented
    await expect(badge).toBeVisible();
    const newText = await badge.textContent();
    const newMatch = newText?.match(/(\d+)/);
    const newCount = newMatch ? parseInt(newMatch[1]) : 0;

    expect(newCount).toBe(initialCount + 1);
  });
});
