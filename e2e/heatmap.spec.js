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

  test('clicking ghost icon updates the Recent Views sidebar', async ({ page }) => {
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

    // Find the ghost icon button
    const ghostButton = firstMovieCard.locator('button[title="Add Ghost View"]');
    await expect(ghostButton).toBeVisible();

    // Click the ghost icon (not the card, which now navigates)
    await ghostButton.click();

    // Wait for the observable side effect: sidebar shows the movie
    await expect(recentViewsSidebar.locator('> div').first()).toContainText(movieTitle, { timeout: 10000 });

    // Verify the view count increased (or was added)
    const newRecentViewsCount = await recentViewsSidebar.locator('> div').count();
    expect(newRecentViewsCount).toBeGreaterThanOrEqual(initialRecentViewsCount);
  });

  test('movie card shows click count badge after clicking ghost icon', async ({ page }) => {
    await page.goto('/');

    // Wait for movie cards to load
    const movieCards = page.locator('.movie-card');
    await expect(movieCards.first()).toBeVisible();

    // Find a movie card
    const firstMovieCard = movieCards.first();

    // Find the ghost icon button
    const ghostButton = firstMovieCard.locator('button[title="Add Ghost View"]');
    await expect(ghostButton).toBeVisible();

    // Click the ghost icon (not the card itself, which now navigates)
    await ghostButton.click();

    // Wait for the observable side effect: badge appears with view count
    const badge = firstMovieCard.locator('[class*="absolute top-2 left-2"]');
    await expect(badge).toBeVisible({ timeout: 10000 });
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

});
