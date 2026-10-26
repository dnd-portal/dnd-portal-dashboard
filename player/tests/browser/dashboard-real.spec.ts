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
