import { expect, test } from '@playwright/test';

for (const viewport of [
    { width: 1440, height: 1000 },
    { width: 390, height: 844 }
]) {
    test(`registration layout remains usable at ${viewport.width}px`, async ({ page }) => {
        await page.setViewportSize(viewport);
        let registerCalled = false;
        await page.route('**/api/v1/auth/csrf', async (route) =>
            route.fulfill({ contentType: 'application/json', body: JSON.stringify({ data: { token: 'test-token' } }) })
        );
        await page.route('**/api/v1/auth/invite/validate', async (route) =>
            route.fulfill({
                contentType: 'application/json',
                body: JSON.stringify({
                    data: {
                        has_pending_user: viewport.width !== 390,
                        display_name: 'Invited Player',
                        username: 'invited'
                    }
                })
            })
        );
        await page.route('**/api/v1/auth/register', async (route) =>
            (() => {
                registerCalled = true;
                return route.fulfill({ contentType: 'application/json', body: JSON.stringify({ data: { id: 42 } }) });
            })()
        );

        await page.goto('/register?code=test-code-1234');
        await expect(page.getByRole('heading', { name: 'Create your account' })).toBeVisible();
        await expect(page.getByText('test-code-1234')).toHaveCount(0);
        await expect(page.getByRole('button', { name: 'Use a different invite' })).toBeVisible();
        expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(viewport.width);
        await page.getByLabel('Email').fill('player@example.test');
        if (viewport.width === 390) {
            await page.getByLabel('Display name').fill('New Player');
            await page.getByLabel('Username').fill('newplayer');
        }
        await page.getByLabel('Password').fill('password-2026');
        await expect(page.getByRole('button', { name: 'Create account' })).toBeEnabled();
        await page.getByLabel('Email').focus();
        await expect(page.getByLabel('Email')).toBeFocused();
        await page.getByRole('button', { name: 'Create account' }).click();
        await expect.poll(() => registerCalled).toBe(true);
    });
}
