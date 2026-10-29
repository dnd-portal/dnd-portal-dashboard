import { expect, test } from '@playwright/test';

for (const width of [390, 517]) {
    test(`mobile menu fills viewport and restores focus at ${width}px`, async ({ page }) => {
        await page.setViewportSize({ width, height: 844 });
        await page.route('**/api/v1/auth/me', (route) =>
            route.fulfill({
                contentType: 'application/json',
                body: JSON.stringify({ data: { roles: ['campaign_member'] } })
            })
        );
        await page.goto('/dashboard');
        const menu = page.getByRole('button', { name: 'Open navigation' });
        await menu.click();
        const panel = page.locator('.p-shell__sidebar');
        await expect(panel).toBeVisible();
        await expect(page.getByRole('button', { name: 'Close menu' })).toBeFocused();
        expect(await panel.boundingBox()).toMatchObject({ x: 0, y: 0, width, height: 844 });
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
        await page.keyboard.press('Escape');
        await expect(menu).toBeFocused();
        await menu.click();
        await page
            .getByRole('navigation', { name: 'Dashboard' })
            .getByRole('link', { name: 'Polls', exact: true })
            .click();
        await expect(page).toHaveURL(/\/dashboard\/polls/);
    });
}
