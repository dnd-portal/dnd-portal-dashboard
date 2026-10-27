import { expect, test } from '@playwright/test';

test('real session reload and availability DATE flow', async ({ page }) => {
    await page.goto('/');
    await page.getByLabel('Email or username').fill(process.env.POLL_ADMIN_USERNAME!);
    await page.getByLabel('Password').fill(process.env.POLL_ADMIN_PASSWORD!);
    await page.getByRole('button', { name: /Continue with email/ }).click();
    await expect(page.getByRole('button', { name: 'Sign out' })).toBeVisible();
    const campaign = await page
        .evaluate(async () => (await fetch('/api/v1/campaigns')).json())
        .then((body) => body.data[0].id);
    await page.goto('/admin/sessions');
    await page.getByLabel('Campaign ID').fill(String(campaign));
    await page.getByLabel('Title').fill(`Browser session ${Date.now()}`);
    await page.getByLabel('Calendar date').fill('2026-12-31');
    await page.getByRole('button', { name: 'Create' }).click();
    await expect(page.getByText('2026-12-31')).toBeVisible();
    const link = page.locator('a[href^="/admin/sessions/"]').last();
    const href = await link.getAttribute('href');
    await page.goto(href!);
    await expect(page.getByText(/2026-12-31/)).toBeVisible();
    await page.reload();
    await expect(page).toHaveURL(/\/admin\/sessions\/\d+$/);
    const result = await page.evaluate(async () => {
        const csrf = await (await fetch('/api/v1/auth/csrf')).json();
        const headers = { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf.data.token };
        const save = await fetch('/api/v1/availability/2026-12-31', {
            method: 'PUT',
            headers,
            body: JSON.stringify({ status: 'available' })
        });
        const reload = await (await fetch('/api/v1/availability')).json();
        const clear = await fetch('/api/v1/availability/2026-12-31', { method: 'DELETE', headers });
        return {
            save: save.status,
            preserved: reload.data.some((row: { availability_date: string }) => row.availability_date === '2026-12-31'),
            clear: clear.status
        };
    });
    expect(result).toEqual({ save: 200, preserved: true, clear: 200 });
});
