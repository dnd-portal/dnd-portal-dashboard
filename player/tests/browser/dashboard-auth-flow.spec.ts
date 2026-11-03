import { expect, test } from '@playwright/test';

const user = { id: 1, display_name: 'Player', roles: ['campaign_member'] };

test.describe('dashboard auth failure handling (mocked API)', () => {
    test('late 401 navigates once to login with the current returnTo', async ({ page }) => {
        let authRequests = 0;
        await page.route('**/api/v1/auth/me', (route) => {
            authRequests += 1;
            if (authRequests === 1) {
                return route.fulfill({ contentType: 'application/json', body: JSON.stringify({ data: user }) });
            }
            return route.fulfill({
                status: 401,
                contentType: 'application/json',
                body: JSON.stringify({ error: { code: 'unauthenticated', message: 'Sign in required.' } })
            });
        });

        await page.goto('/dashboard');
        await expect(page).toHaveURL(/\/login\?returnTo=%2Fdashboard$/);
        await expect(page.getByRole('heading', { name: 'Sign in' })).toBeVisible();
        expect(authRequests).toBe(3);
    });

    test('non-401 dashboard error stays visible without navigation', async ({ page }) => {
        await page.route('**/api/v1/auth/me', (route) =>
            route.fulfill({
                status: 500,
                contentType: 'application/json',
                body: JSON.stringify({ error: { message: 'Down' } })
            })
        );

        await page.goto('/dashboard');
        await expect(page).toHaveURL(/\/dashboard$/);
        await expect(page.getByText('Unable to verify your session.')).toBeVisible();
    });
});
