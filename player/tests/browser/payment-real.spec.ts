import { expect, test } from '@playwright/test';

async function login(page: import('@playwright/test').Page, id: string, password: string) {
    await page.goto('/');
    await page.getByLabel('Email or username').fill(id);
    await page.getByLabel('Password').fill(password);
    await page.getByRole('button', { name: /Continue with email/ }).click();
    await expect(page.getByRole('button', { name: 'Sign out' })).toBeVisible();
}

test('real payment request, partial/full payment, reversal and private overview', async ({ page }) => {
    test.setTimeout(60000);
    await login(page, process.env.POLL_ADMIN_USERNAME!, process.env.POLL_ADMIN_PASSWORD!);
    console.log('payment: admin logged in');
    const setup = await page.evaluate(async () => {
        const campaigns = await (await fetch('/api/v1/campaigns')).json();
        const csrf = await (await fetch('/api/v1/auth/csrf')).json();
        const response = await fetch(`/api/v1/campaigns/${campaigns.data[0].id}/payment-requests`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf.data.token },
            body: JSON.stringify({ title: `Browser payment ${Date.now()}`, amount_cents: 10000, currency: 'EUR' })
        });
        return { request: (await response.json()).data.id };
    });
    const users = await page.evaluate(async () => (await fetch('/api/v1/users')).json());
    const player = users.data.find((user: { username: string }) => user.username === process.env.PAYMENT_PLAYER_USERNAME);
    const obligation = await page.evaluate(async ({ request, user }) => {
        const csrf = await (await fetch('/api/v1/auth/csrf')).json();
        const response = await fetch(`/api/v1/payment-requests/${request}/obligations`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf.data.token }, body: JSON.stringify({ user_id: user, amount_cents: 10000, currency: 'EUR' }) });
        return (await response.json()).data.id;
    }, { request: setup.request, user: player.id });
    await page.goto(`/admin/payments/${setup.request}`);
    console.log('payment: request and obligation created');
    await page.getByLabel('Obligation ID').fill(String(obligation));
    await page.getByLabel('Amount in cents').fill('4000');
    await page.getByLabel('External reference').fill('browser-pay-1');
    await page.getByRole('button', { name: 'Record payment' }).click();
    await page.reload();
    await expect(page.getByText(/4000\/10000/)).toBeVisible();
    const entry = await page.evaluate(async (id) => (await (await fetch(`/api/v1/payment-requests/${id}`)).json()).data.obligations[0].last_payment_entry_id, setup.request);
    await page.getByLabel('Obligation ID').fill(String(obligation));
    await page.getByLabel('Amount in cents').fill('6000');
    await page.getByLabel('External reference').fill('browser-pay-2');
    await page.getByRole('button', { name: 'Record payment' }).click();
    await page.reload();
    await expect(page.getByText(/10000\/10000/)).toBeVisible();
    await page.getByLabel('Obligation ID').fill(String(obligation));
    await page.getByLabel('Amount in cents').fill('1000');
    await page.getByLabel('External reference').fill('browser-reversal');
    await page.getByLabel('Correction of entry (optional)').fill(String(entry));
    await page.getByRole('button', { name: 'Record payment' }).click();
    await page.reload();
    await expect(page.getByText(/9000\/10000/)).toBeVisible();
    console.log('payment: admin entries and reversal complete');
    const context = await page.context().browser()!.newContext();
    const playerPage = await context.newPage();
    await login(playerPage, process.env.PAYMENT_PLAYER_USERNAME!, process.env.PAYMENT_PLAYER_PASSWORD!);
    await playerPage.goto('/payments');
    await expect(playerPage.getByText(/9000\/10000/)).toBeVisible();
    console.log('payment: player overview complete');
    await context.close();
});
