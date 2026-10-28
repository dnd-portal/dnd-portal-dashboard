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
            await page.route('**/api/v1/global-polls/7', (route) =>
                route.fulfill({ contentType: 'application/json', body: JSON.stringify({ data: poll }) })
            );
            await page.route('**/api/v1/global-polls/7/response', async (route) => {
                saved = true;
                const request = route.request().postDataJSON() as { option_ids: number[] };
                await new Promise((resolve) => setTimeout(resolve, 260));
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
            choice_type: 'multiple',
            status: 'open',
            options: [
                { id: 1, label: 'Saturday 14 November 2026' },
                { id: 2, label: 'Saturday 21 November 2026' }
            ],
            selected_option_ids: []
        });
        await state.setup();
        await page.goto('/dashboard/polls/7');
        await expect(page.getByRole('checkbox')).toHaveCount(2);
        await page.getByRole('checkbox').nth(0).check();
        await page.getByRole('checkbox').nth(1).check();
        await expect(page.getByRole('checkbox').nth(0)).toBeChecked();
        await expect(page.getByRole('checkbox').nth(1)).toBeChecked();
        await expect(page.getByRole('button', { name: 'Save answer' })).toHaveCount(0);
        await expect(page.getByText('Saving…')).toBeVisible();
        await expect(page.getByText('Saved')).toBeVisible();
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
    await page.goto('/dashboard/polls/7');
    await expect(page.getByText('This poll is closed')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Save answer' })).toHaveCount(0);
    await page.unroute('**/api/v1/global-polls/7');
    await page.route('**/api/v1/global-polls/7', (route) =>
        route.fulfill({
            status: 404,
            contentType: 'application/json',
            body: JSON.stringify({ error: { code: 'not_found', message: 'Poll not found.' } })
        })
    );
    await page.reload();
    await expect(page.getByRole('heading', { name: 'Poll unavailable' })).toBeVisible();
});

test('player navigation and breadcrumbs follow the current route', async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 1000 });
    const state = mockPlayerApi(page, null);
    await state.setup();
    await page.goto('/dashboard');
    const dashboardNav = page.getByRole('navigation', { name: 'Dashboard' });
    await expect(dashboardNav.getByRole('link', { name: 'Overview' })).toHaveAttribute('aria-current', 'page');
    await expect(dashboardNav.getByRole('link', { name: 'Polls' })).not.toHaveAttribute('aria-current', 'page');
    await page.goto('/dashboard/polls/7');
    await expect(dashboardNav.getByRole('link', { name: 'Polls' })).toHaveAttribute('aria-current', 'page');
    await expect(dashboardNav.getByRole('link', { name: 'Overview' })).not.toHaveAttribute('aria-current', 'page');
    await expect(page.locator('.p-shell__breadcrumb')).toContainText('Dashboard / Polls / Poll');
    await page.goto('/dashboard/profile');
    await expect(dashboardNav.getByRole('link', { name: 'Profile' })).toHaveAttribute('aria-current', 'page');
    await expect(page.locator('.p-shell__breadcrumb')).toContainText('Dashboard / Profile');
    expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(1440);
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

test('poll overview lists multiple open polls and opens the selected detail', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.route('**/api/v1/auth/me', (route) =>
        route.fulfill({ contentType: 'application/json', body: JSON.stringify({ data: user }) })
    );
    await page.route('**/api/v1/global-polls', (route) =>
        route.fulfill({
            contentType: 'application/json',
            body: JSON.stringify({
                data: [
                    { id: 7, title: 'First poll', explanation: 'First description', status: 'open', selected_count: 0 },
                    { id: 8, title: 'Second poll', explanation: null, status: 'open', selected_count: 2 }
                ]
            })
        })
    );
    await page.route('**/api/v1/global-polls/8', (route) =>
        route.fulfill({
            contentType: 'application/json',
            body: JSON.stringify({
                data: {
                    id: 8,
                    title: 'Second poll',
                    explanation: null,
                    choice_type: 'multiple',
                    status: 'open',
                    options: [{ id: 3, label: 'Friday' }],
                    selected_option_ids: [3]
                }
            })
        })
    );
    await page.goto('/dashboard/polls');
    await expect(page.getByRole('link', { name: /First poll/ })).toBeVisible();
    await expect(page.getByRole('link', { name: /Second poll/ })).toContainText('Answered');
    await expect(page.getByRole('link', { name: /Second poll/ })).toContainText('2 dates selected');
    await page.getByRole('link', { name: /Second poll/ }).click();
    await expect(page.getByRole('heading', { name: 'Second poll' })).toBeVisible();
});
