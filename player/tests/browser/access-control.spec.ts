import { expect, test } from '@playwright/test';

test.describe('admin access control without a session', () => {
    test('direct admin navigation and reload show no private content', async ({ page }) => {
        await page.goto('/admin/users');
        await expect(page.getByRole('heading', { name: 'Admin access required' })).toBeVisible();
        await expect(page.getByText('No records match the current search and filters.')).toHaveCount(0);

        const unauthenticatedResponse = await page.evaluate(async () => {
            const response = await fetch('/api/v1/users', { credentials: 'include', cache: 'no-store' });
            return { status: response.status, cacheControl: response.headers.get('cache-control') };
        });
        expect(unauthenticatedResponse.status).toBe(401);
        expect(unauthenticatedResponse.cacheControl).toContain('no-store');

        await page.reload();
        await expect(page.getByRole('heading', { name: 'Admin access required' })).toBeVisible();
    });

    test('direct user detail navigation does not expose mock or private data', async ({ page }) => {
        await page.goto('/admin/users/1');
        await expect(page.getByRole('heading', { name: 'Admin access required' })).toBeVisible();
        await expect(page.locator('body')).not.toContainText('Administrator');
    });
});
