import { expect, test } from '@playwright/test';

async function login(page: import('@playwright/test').Page, identifier: string, password: string) {
    await page.goto('/');
    await page.getByLabel('Email or username').fill(identifier);
    await page.getByLabel('Password').fill(password);
    await page.getByRole('button', { name: /Continue with email/ }).click();
    await expect(page.getByRole('button', { name: 'Sign out' })).toBeVisible();
}

test('dashboard API is private, role-scoped and reload-stable', async ({ page, browser }) => {
    await login(page, process.env.POLL_ADMIN_USERNAME!, process.env.POLL_ADMIN_PASSWORD!);
    const admin = await page.evaluate(async () => (await fetch('/api/v1/dashboard')).json());
    expect(admin.data.admin).toBeTruthy();
    expect(admin.data.admin.activity).toBeInstanceOf(Array);
    await page.reload();
    const adminReload = await page.evaluate(async () => (await fetch('/api/v1/dashboard')).json());
    expect(adminReload.data.admin).toBeTruthy();

    const playerContext = await browser.newContext();
    const player = await playerContext.newPage();
    await login(player, process.env.PAYMENT_PLAYER_USERNAME!, process.env.PAYMENT_PLAYER_PASSWORD!);
    const playerDashboard = await player.evaluate(async () => (await fetch('/api/v1/dashboard')).json());
    expect(playerDashboard.data.admin).toBeUndefined();
    expect(playerDashboard.data.open_contributions_cents).toEqual(expect.any(Number));
    await player.reload();
    const playerReload = await player.evaluate(async () => (await fetch('/api/v1/dashboard')).json());
    expect(playerReload.data.admin).toBeUndefined();
    await playerContext.close();
});

test('dashboard renders an API-backed empty state and a visible API outage state', async ({ page }) => {
    await login(page, process.env.POLL_ADMIN_USERNAME!, process.env.POLL_ADMIN_PASSWORD!);
    await page.route('**/api/v1/dashboard', async (route) => {
        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ data: { next_session: null, open_polls: 0, open_contributions_cents: 0 } })
        });
    });
    await page.route('**/api/v1/campaigns', async (route) => {
        await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ data: [] }) });
    });
    await page.reload();
    await expect(page.getByText('No upcoming sessions')).toBeVisible();
    await expect(page.getByText('00')).toBeVisible();

    await page.route('**/api/v1/dashboard', async (route) => route.abort('failed'));
    await page.reload();
    await expect(page.getByRole('heading', { name: 'Dashboard unavailable' })).toBeVisible();
});

test('player navigation replaces the main content and stays within the viewport', async ({ page }) => {
    await login(page, process.env.PAYMENT_PLAYER_USERNAME!, process.env.PAYMENT_PLAYER_PASSWORD!);
    await expect(page.getByText('Yori')).toHaveCount(0);
    await expect(page.getByText('The Sunken Archive')).toHaveCount(0);

    await page.getByRole('link', { name: /Sessions/ }).click();
    await expect(page).toHaveURL(/\/sessions$/);
    await expect(page.getByRole('heading', { name: 'Sessions' })).toBeVisible();
    await page.goBack();
    await page.getByRole('link', { name: /The party/ }).click();
    await expect(page).toHaveURL(/\/party$/);
    await expect(page.getByRole('heading', { name: 'Party roster' })).toBeVisible();
    await page.getByRole('link', { name: /Dashboard/ }).click();
    await page.getByRole('link', { name: /Treasury/ }).click();
    await expect(page).toHaveURL(/\/payments$/);
    await expect(page.getByRole('heading', { name: 'My payments' })).toBeVisible();

    await page.setViewportSize({ width: 390, height: 844 });
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
});
