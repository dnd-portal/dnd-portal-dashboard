import { expect, test } from '@playwright/test';

test('admin can import an existing hash without creating an invitation', async ({ playwright }) => {
    const password = process.env.TEST_IMPORT_PASSWORD;
    const hash = process.env.TEST_IMPORT_HASH;
    const adminPassword = process.env.INITIAL_ADMIN_PASSWORD ?? process.env.TEST_ADMIN_PASSWORD;
    if (!password || !hash || !adminPassword) throw new Error('Dedicated import-test credentials are required.');
    const origin = 'http://127.0.0.1:5174';
    const api = await playwright.request.newContext({
        baseURL: process.env.TEST_BACKEND_URL ?? 'http://127.0.0.1:8099',
        extraHTTPHeaders: { Origin: origin }
    });
    const csrf = async () => (await api.get('/api/v1/auth/csrf')).json().then((body) => body.data.token as string);
    const adminLogin = await api.fetch('/api/v1/auth/login', {
        method: 'POST',
        headers: { Origin: origin, 'X-CSRF-Token': await csrf(), 'Content-Type': 'application/json' },
        data: { identifier: process.env.TEST_ADMIN_USERNAME, password: adminPassword }
    });
    if (adminLogin.status() !== 200)
        throw new Error(`Admin login failed for ${process.env.TEST_ADMIN_USERNAME}: ${await adminLogin.text()}`);
    const post = async (path: string, body: unknown) =>
        api.fetch(path, {
            method: 'POST',
            headers: { Origin: origin, 'X-CSRF-Token': await csrf(), 'Content-Type': 'application/json' },
            data: body
        });
    const before = await api.get('/api/v1/admin/invites');
    const suffix = Date.now();
    const imported = await post('/api/v1/admin/users/import', {
        display_name: 'Imported User',
        username: `imported-${suffix}`,
        email: `imported-${suffix}@example.test`,
        role: 'campaign_member',
        password_hash: hash
    });
    expect(imported.status()).toBe(201);
    const importedData = (await imported.json()).data;
    expect(importedData.account_status).toBe('active');
    expect(importedData.roles).toContain('campaign_member');
    const duplicateUsername = await post('/api/v1/admin/users/import', {
        display_name: 'Duplicate Username',
        username: importedData.username,
        email: `other-${suffix}@example.test`,
        role: 'campaign_member',
        password_hash: hash
    });
    expect(duplicateUsername.status()).toBe(409);
    const duplicateEmail = await post('/api/v1/admin/users/import', {
        display_name: 'Duplicate Email',
        username: `other-${suffix}`,
        email: importedData.email,
        role: 'campaign_member',
        password_hash: hash
    });
    expect(duplicateEmail.status()).toBe(409);
    const invalidHash = await post('/api/v1/admin/users/import', {
        display_name: 'Invalid Hash',
        username: `invalid-${suffix}`,
        email: `invalid-${suffix}@example.test`,
        role: 'campaign_member',
        password_hash: 'not-a-hash'
    });
    expect(invalidHash.status()).toBe(422);
    const missingCsrf = await api.fetch('/api/v1/admin/users/import', {
        method: 'POST',
        headers: { Origin: origin, 'Content-Type': 'application/json' },
        data: {
            display_name: 'No CSRF',
            username: `csrf-${suffix}`,
            email: `csrf-${suffix}@example.test`,
            role: 'campaign_member',
            password_hash: hash
        }
    });
    expect(missingCsrf.status()).toBe(419);
    const userApi = await playwright.request.newContext({
        baseURL: process.env.TEST_BACKEND_URL ?? 'http://127.0.0.1:8099',
        extraHTTPHeaders: { Origin: origin }
    });
    const userCsrf = async () =>
        (await userApi.get('/api/v1/auth/csrf')).json().then((body) => body.data.token as string);
    const userLogin = await userApi.fetch('/api/v1/auth/login', {
        method: 'POST',
        headers: { Origin: origin, 'X-CSRF-Token': await userCsrf(), 'Content-Type': 'application/json' },
        data: { identifier: importedData.username, password }
    });
    expect(userLogin.status()).toBe(200);
    const me = await userApi.get('/api/v1/auth/me');
    expect((await me.json()).data.id).toBe(importedData.id);
    expect((await userApi.get('/api/v1/admin/invites')).status()).toBe(403);
    const after = await api.get('/api/v1/admin/invites');
    expect((await after.json()).data.length).toBe((await before.json()).data.length);
    await api.dispose();
    await userApi.dispose();
});
