// @ts-check
import { test, expect } from '@playwright/test';

test.describe('Movie Search Dialog', () => {
  test('clicking search button opens the search dialog', async ({ page }) => {
    await page.goto('/');

    // Find and click the search trigger button
    const searchTrigger = page.locator('#search-trigger');
    await expect(searchTrigger).toBeVisible();
    await searchTrigger.click();

    // Dialog should be open
    const dialog = page.locator('dialog#search-dialog');
    await expect(dialog).toBeVisible();

    // Search input inside dialog should be focused
    const searchInput = page.locator('#search-input');
    await expect(searchInput).toBeFocused();
  });

  test('keyboard shortcut Cmd/Ctrl+K opens search dialog', async ({ page }) => {
    await page.goto('/');

    // Press Cmd+K (Mac) or Ctrl+K (Windows/Linux)
    await page.keyboard.press('Meta+k');

    // Dialog should be open
    const dialog = page.locator('dialog#search-dialog');
    await expect(dialog).toBeVisible();
  });

  test('search input triggers HTMX request on typing', async ({ page }) => {
    await page.goto('/');

    // Open dialog
    await page.locator('#search-trigger').click();

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

    // Results should be visible in the dialog
    const searchResults = page.locator('#search-results');
    await expect(searchResults.locator('.search-result-card').first()).toBeVisible();
  });

  test('search results show movie poster grid', async ({ page }) => {
    await page.goto('/');

    // Open dialog and search
    await page.locator('#search-trigger').click();
    const searchInput = page.locator('#search-input');
    await searchInput.fill('fight');

    // Wait for search results
    await page.waitForResponse(response =>
      response.url().includes('/search?q=') && response.status() === 200
    );

    // Check that results are displayed as a grid of cards
    const searchResults = page.locator('#search-results');
    const resultCards = searchResults.locator('.search-result-card');
    
    // Should have multiple results
    await expect(resultCards.first()).toBeVisible();
    
    // Cards should have poster images
    const firstCard = resultCards.first();
    await expect(firstCard.locator('img').first()).toBeVisible();
  });

  test('dialog closes and resets when clicking close button', async ({ page }) => {
    await page.goto('/');

    // Open dialog and type something
    await page.locator('#search-trigger').click();
    const searchInput = page.locator('#search-input');
    await searchInput.fill('test search');

    // Click close button
    const closeButton = page.locator('dialog#search-dialog button[aria-label="Close search"]');
    await closeButton.click();

    // Dialog should close
    const dialog = page.locator('dialog#search-dialog');
    await expect(dialog).not.toBeVisible();

    // Re-open dialog
    await page.locator('#search-trigger').click();

    // Search input should be cleared
    await expect(searchInput).toHaveValue('');
  });

  test('dialog closes when clicking backdrop', async ({ page }) => {
    await page.goto('/');

    // Open dialog
    await page.locator('#search-trigger').click();
    const dialog = page.locator('dialog#search-dialog');
    await expect(dialog).toBeVisible();

    // Click on the backdrop (outside the dialog content)
    // We click at a position that's definitely on the backdrop
    await page.mouse.click(10, 10);

    // Dialog should close
    await expect(dialog).not.toBeVisible();
  });

  test('dialog closes when pressing Escape', async ({ page }) => {
    await page.goto('/');

    // Open dialog
    await page.locator('#search-trigger').click();
    const dialog = page.locator('dialog#search-dialog');
    await expect(dialog).toBeVisible();

    // Press Escape
    await page.keyboard.press('Escape');

    // Dialog should close
    await expect(dialog).not.toBeVisible();
  });

  test('valid search query of 2+ chars triggers search request', async ({ page }) => {
    await page.goto('/');

    // Open dialog
    await page.locator('#search-trigger').click();
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
});
