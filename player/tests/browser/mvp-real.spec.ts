import { expect, test } from '@playwright/test';

test('V4 MVP invitation and global poll persistence', async ({ playwright }) => {
    const api = await playwright.request.newContext({
        baseURL: 'http://127.0.0.1:8110',
        extraHTTPHeaders: { Origin: 'http://127.0.0.1:5174', Accept: 'application/json' }
    });
    const adminPassword = process.env.LOCAL_ADMIN_PASSWORD;
    if (!adminPassword) throw new Error('LOCAL_ADMIN_PASSWORD is required for this real-stack test.');
    const suffix = Date.now().toString(36);
    const csrf = async () => (await api.get('/api/v1/auth/csrf')).json().then((x) => x.data.token as string);
    const call = async (method: string, path: string, body?: unknown) =>
        api.fetch(path, {
            method,
            headers: method === 'GET' ? {} : { 'X-CSRF-Token': await csrf(), 'Content-Type': 'application/json' },
            data: body
        });
    const adminLogin = await call('POST', '/api/v1/auth/login', {
        identifier: 'v4admin',
        password: adminPassword
    });
    expect(adminLogin.status()).toBe(200);

    const typeAEmail = `mvp-a-${suffix}@example.test`;
    const typeAInvite = await call('POST', '/api/v1/admin/invites', { email: typeAEmail });
    expect(typeAInvite.status()).toBe(201);
    const typeAData = (await typeAInvite.json()).data;
    const typeA = await playwright.request.newContext({
        baseURL: 'http://127.0.0.1:8110',
        extraHTTPHeaders: { Origin: 'http://127.0.0.1:5174', Accept: 'application/json' }
    });
    const typeACsrf = async () => (await typeA.get('/api/v1/auth/csrf')).json().then((x) => x.data.token as string);
    const typeACall = async (method: string, path: string, body?: unknown) =>
        typeA.fetch(path, {
            method,
            headers: method === 'GET' ? {} : { 'X-CSRF-Token': await typeACsrf(), 'Content-Type': 'application/json' },
            data: body
        });
    const typeARegister = await typeACall('POST', '/api/v1/auth/register', {
        invite_code: typeAData.code,
        email: typeAEmail,
        username: `mvpa${suffix}`,
        display_name: 'MVP Type A',
        password: 'MvpTypeA2026!',
        password_confirmation: 'MvpTypeA2026!'
    });
    expect(typeARegister.status()).toBe(201);
    expect((await typeARegister.json()).data.id).toBe(typeAData.user_id);
    const typeAReuse = await typeACall('POST', '/api/v1/auth/register', {
        invite_code: typeAData.code,
        email: `reuse-a-${suffix}@example.test`,
        username: `reusea${suffix}`,
        password: 'MvpTypeA2026!',
        password_confirmation: 'MvpTypeA2026!'
    });
    expect(typeAReuse.status()).toBe(422);

    const typeBInvite = await call('POST', '/api/v1/admin/invites', {});
    expect(typeBInvite.status()).toBe(201);
    const typeBData = (await typeBInvite.json()).data;
    const typeB = await playwright.request.newContext({
        baseURL: 'http://127.0.0.1:8110',
        extraHTTPHeaders: { Origin: 'http://127.0.0.1:5174', Accept: 'application/json' }
    });
    const typeBCsrf = async () => (await typeB.get('/api/v1/auth/csrf')).json().then((x) => x.data.token as string);
    const typeBCall = async (method: string, path: string, body?: unknown) =>
        typeB.fetch(path, {
            method,
            headers: method === 'GET' ? {} : { 'X-CSRF-Token': await typeBCsrf(), 'Content-Type': 'application/json' },
            data: body
        });
    const typeBRegister = await typeBCall('POST', '/api/v1/auth/register', {
        invite_code: typeBData.code,
        email: `mvp-b-${suffix}@example.test`,
        username: `mvpb${suffix}`,
        display_name: 'MVP Type B',
        password: 'MvpTypeB2026!',
        password_confirmation: 'MvpTypeB2026!'
    });
    expect(typeBRegister.status()).toBe(201);
    expect((await typeBRegister.json()).data.id).not.toBe(typeAData.user_id);
    const typeBReuse = await typeBCall('POST', '/api/v1/auth/register', {
        invite_code: typeBData.code,
        email: `reuse-b-${suffix}@example.test`,
        username: `reuseb${suffix}`,
        password: 'MvpTypeB2026!',
        password_confirmation: 'MvpTypeB2026!'
    });
    expect(typeBReuse.status()).toBe(422);

    const pollCreate = await call('POST', '/api/v1/admin/global-polls', {
        title: `MVP dates ${suffix}`,
        explanation: 'MVP',
        choice_type: 'single',
        options: ['Thursday', 'Friday', 'Saturday']
    });
    expect(pollCreate.status()).toBe(200);
    const poll = (await pollCreate.json()).data;
    expect((await typeACall('GET', '/api/v1/global-polls/current')).status()).toBe(200);
    expect((await typeBCall('GET', '/api/v1/global-polls/current')).status()).toBe(200);
    const first = poll.options[0].id;
    const second = poll.options[1].id;
    expect((await typeACall('PUT', `/api/v1/global-polls/${poll.id}/response`, { option_ids: [first] })).status()).toBe(
        200
    );
    expect((await typeACall('GET', '/api/v1/global-polls/current')).json()).resolves.toMatchObject({
        data: { selected_option_ids: [first] }
    });
    expect(
        (await typeBCall('PUT', `/api/v1/global-polls/${poll.id}/response`, { option_ids: [second] })).status()
    ).toBe(200);
    expect((await typeBCall('POST', '/api/v1/auth/logout', {})).status()).toBe(200);
    expect(
        (
            await typeBCall('POST', '/api/v1/auth/login', { identifier: `mvpb${suffix}`, password: 'MvpTypeB2026!' })
        ).status()
    ).toBe(200);
    expect((await typeBCall('GET', '/api/v1/global-polls/current')).json()).resolves.toMatchObject({
        data: { selected_option_ids: [second] }
    });
    expect(
        (await typeACall('PUT', `/api/v1/global-polls/${poll.id}/response`, { option_ids: [second] })).status()
    ).toBe(200);
    expect((await call('PATCH', `/api/v1/admin/global-polls/${poll.id}`, { status: 'closed' })).status()).toBe(200);
    const closed = await typeACall('PUT', `/api/v1/global-polls/${poll.id}/response`, { option_ids: [first] });
    expect(closed.status()).toBe(409);
    expect((await call('GET', `/api/v1/admin/global-polls/${poll.id}/results`)).status()).toBe(200);
    expect((await typeBCall('GET', '/api/v1/admin/global-polls')).status()).toBe(403);
    await api.dispose();
    await typeA.dispose();
    await typeB.dispose();
});
