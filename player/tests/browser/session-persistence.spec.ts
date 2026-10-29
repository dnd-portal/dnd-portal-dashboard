import { expect, test } from '@playwright/test';

test('production-like session survives login, reload, reopen and supports poll creation', async ({ page }) => {
    const database = process.env.TEST_DB_NAME ?? '';
    test.skip(
        !/^dnd_portal_test_run_[a-z0-9_]+$/.test(database) || database === 'dnd_portal_v4_development',
        'Session persistence tests require a dedicated test database.'
    );

    const identifier = process.env.TEST_ADMIN_EMAIL ?? process.env.TEST_ADMIN_USERNAME;
    const password = process.env.TEST_ADMIN_PASSWORD;
    test.skip(!identifier || !password, 'Dedicated test admin credentials are required.');

    await page.goto('/login');
    await page.getByLabel('Email or username').fill(identifier!);
    await page.getByLabel('Password').fill(password!);
    await page.getByRole('button', { name: /Continue with email/ }).click();
    await expect(page.getByRole('button', { name: 'Sign out' })).toBeVisible();

    const cookies = await page.context().cookies();
    const sessionCookie = cookies.find((cookie) => cookie.name === 'dnd_session');
    expect(sessionCookie).toMatchObject({
        path: '/',
        secure: true,
        httpOnly: true,
        sameSite: 'Lax'
    });
    expect(sessionCookie?.expires).toBeGreaterThan(Date.now() / 1000);

    await page.goto('/admin/polls');
    await expect(page.getByRole('heading', { name: 'Global polls' })).toBeVisible();
    await page.reload();
    await expect(page.getByRole('heading', { name: 'Global polls' })).toBeVisible();

    const meAfterReload = await page.evaluate(async () => {
        const response = await fetch('/api/v1/auth/me', { credentials: 'include' });
        return { status: response.status, body: await response.json() };
    });
    expect(meAfterReload.status).toBe(200);
    expect(meAfterReload.body.data).toBeTruthy();

    const title = `Session persistence poll ${Date.now()}`;
    await page.getByLabel('Title').fill(title);
    await page.getByLabel('Options').fill('Thursday\nFriday\nSaturday');
    await page.getByRole('button', { name: 'Create poll' }).click();
    await expect(page.getByText(title)).toBeVisible();

    await Promise.all([
        page.waitForResponse(
            (response) => response.url().endsWith('/api/v1/auth/logout') && response.request().method() === 'POST'
        ),
        page.getByRole('button', { name: 'Sign out' }).click()
    ]);
    await page.waitForLoadState('networkidle');
    const unauthenticated = await page.evaluate(async () => {
        const response = await fetch('/api/v1/auth/me', { credentials: 'include' });
        return response.status;
    });
    expect(unauthenticated).toBe(401);
});
