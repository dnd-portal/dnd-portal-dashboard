import { expect, test } from '@playwright/test';

const user = {
    id: 1,
    display_name: 'A very long player name that should wrap safely',
    username: 'player',
    email: 'a-very-long-email-address-that-should-wrap@example.test',
    roles: ['campaign_member', 'portal_admin']
};

function mockPlayerApi(page: import('@playwright/test').Page, poll: unknown) {
    let saved = false;
    return {
        get saved() {
            return saved;
        },
        setup: async () => {
            await page.route('**/api/v1/auth/me', (route) =>
                route.fulfill({ contentType: 'application/json', body: JSON.stringify({ data: user }) })
            );
            await page.route('**/api/v1/auth/csrf', (route) =>
                route.fulfill({
                    contentType: 'application/json',
                    body: JSON.stringify({ data: { token: 'test-token' } })
                })
            );
            await page.route('**/api/v1/global-polls/current', (route) =>
                route.fulfill({ contentType: 'application/json', body: JSON.stringify({ data: poll }) })
            );
            await page.route('**/api/v1/global-polls/7/response', async (route) => {
                saved = true;
                const request = route.request().postDataJSON() as { option_ids: number[] };
                route.fulfill({
                    contentType: 'application/json',
                    body: JSON.stringify({ data: { ...(poll as object), selected_option_ids: request.option_ids } })
                });
            });
        }
    };
}

for (const viewport of [
    { width: 1440, height: 1000 },
    { width: 390, height: 844 }
]) {
    test(`open poll is usable at ${viewport.width}px`, async ({ page }) => {
        await page.setViewportSize(viewport);
        const state = mockPlayerApi(page, {
            id: 7,
            title: 'Choose a date',
            explanation: 'Select the date that works best.',
            choice_type: 'single',
            status: 'open',
            options: [
                { id: 1, label: 'Saturday 14 November 2026' },
                { id: 2, label: 'Saturday 21 November 2026' }
            ],
            selected_option_ids: []
        });
        await state.setup();
        await page.goto('/dashboard/polls/current');
        await expect(page.getByRole('radio')).toHaveCount(2);
        await page.getByRole('radio').nth(1).check();
        await expect(page.getByRole('radio').nth(1)).toBeChecked();
        await expect(page.getByRole('button', { name: 'Save answer' })).toBeEnabled();
        await page.getByRole('button', { name: 'Save answer' }).click();
        await expect(page.getByRole('status')).toContainText('saved');
        expect(state.saved).toBe(true);
        expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(viewport.width);
    });
}

test('poll closed and empty states stay readable', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    const closed = mockPlayerApi(page, {
        id: 7,
        title: 'Closed poll',
        explanation: null,
        choice_type: 'single',
        status: 'closed',
        options: [{ id: 1, label: 'Saturday 14 November 2026' }],
        selected_option_ids: [1]
    });
    await closed.setup();
    await page.goto('/dashboard/polls/current');
    await expect(page.getByText('This poll is closed')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Save answer' })).toHaveCount(0);
    await page.unroute('**/api/v1/global-polls/current');
    await page.route('**/api/v1/global-polls/current', (route) =>
        route.fulfill({ contentType: 'application/json', body: JSON.stringify({ data: null }) })
    );
    await page.reload();
    await expect(page.getByRole('heading', { name: 'No open poll' })).toBeVisible();
});

test('profile uses friendly role labels without duplicate sign out content', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    const state = mockPlayerApi(page, null);
    await state.setup();
    await page.goto('/dashboard/profile');
    await expect(page.getByRole('heading', { name: user.display_name })).toBeVisible();
    await expect(page.getByText('Player', { exact: true })).toBeVisible();
    await expect(page.getByText('Portal administrator', { exact: true })).toBeVisible();
    await expect(page.getByText('campaign_member')).toHaveCount(0);
    await expect(page.getByText('campaign memberships')).toHaveCount(0);
    await expect(page.getByRole('main').getByRole('button', { name: /sign out/i })).toHaveCount(0);
    expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(390);
});
