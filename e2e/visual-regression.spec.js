// @ts-check
import { test, expect } from '@playwright/test';

/**
 * Visual Regression Tests
 *
 * These tests capture screenshots of key views and compare them against baselines.
 * Dynamic content (movie posters, timestamps, click counts) is masked to prevent
 * false failures from TMDB's daily trending changes.
 *
 * To update baselines after intentional UI changes:
 *   npx playwright test visual-regression --update-snapshots
 */

// Selectors for dynamic content to mask
const DYNAMIC_CONTENT_SELECTORS = [
  '.movie-card img', // Movie poster images (change daily)
  '[class*="absolute top-2 right-2"]', // Click count badges
  '#recent-views-sidebar', // Recent views section (timestamps + posters)
];

// Allow small pixel variance for font rendering differences
const SCREENSHOT_OPTIONS = {
  maxDiffPixelRatio: 0.02, // Allow up to 2% pixel difference
};

test.describe('Visual Regression', () => {
  test('homepage desktop viewport', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 720 });
    await page.goto('/');

    // Wait for movies to load
    await expect(page.locator('.movie-card').first()).toBeVisible();

    // Take screenshot with dynamic content masked
    await expect(page).toHaveScreenshot('homepage-desktop.png', {
      fullPage: true,
      mask: DYNAMIC_CONTENT_SELECTORS.map((sel) => page.locator(sel)),
      ...SCREENSHOT_OPTIONS,
    });
  });

  test('homepage mobile viewport', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 667 });
    await page.goto('/');

    // Wait for movies to load
    await expect(page.locator('.movie-card').first()).toBeVisible();

    // Take screenshot with dynamic content masked
    await expect(page).toHaveScreenshot('homepage-mobile.png', {
      fullPage: true,
      mask: DYNAMIC_CONTENT_SELECTORS.map((sel) => page.locator(sel)),
      ...SCREENSHOT_OPTIONS,
    });
  });

  test('search dialog with results', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 720 });
    await page.goto('/');

    // Open search dialog
    await page.locator('#search-trigger').click();
    await expect(page.locator('dialog#search-dialog')).toBeVisible();

    // Search for something consistent
    await page.locator('#search-input').fill('matrix');

    // Wait for results to load
    await page.waitForResponse(
      (resp) => resp.url().includes('/search?q=') && resp.status() === 200
    );
    await expect(page.locator('.search-result-card').first()).toBeVisible();

    // Screenshot the dialog with results (mask search result images)
    await expect(page).toHaveScreenshot('search-dialog-with-results.png', {
      mask: [
        page.locator('.search-result-card img'), // Search result posters
        page.locator('#search-spinner'), // Loading spinner
        ...DYNAMIC_CONTENT_SELECTORS.map((sel) => page.locator(sel)), // Background content
      ],
      ...SCREENSHOT_OPTIONS,
    });
  });
});
