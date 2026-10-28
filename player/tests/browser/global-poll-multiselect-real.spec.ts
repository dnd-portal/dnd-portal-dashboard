import { expect, test } from '@playwright/test';

test('global poll supports isolated multi-select persistence and result counts', async ({ playwright }) => {
    const database = process.env.TEST_DB_NAME ?? '';
    if (!/^dnd_portal_test_run_[0-9]+$/.test(database) || database === 'dnd_portal_v4_development') {
        throw new Error('Refusing global poll test outside a dedicated test database.');
    }
    const origin = 'http://127.0.0.1:5174';
    const baseURL = process.env.TEST_BACKEND_URL ?? 'http://127.0.0.1:8099';
    const admin = await playwright.request.newContext({ baseURL, extraHTTPHeaders: { Origin: origin } });
    const csrf = async (context: typeof admin) =>
        (await context.get('/api/v1/auth/csrf')).json().then((x) => x.data.token as string);
    const call = async (context: typeof admin, method: string, path: string, data?: unknown) =>
        context.fetch(path, {
            method,
            headers:
                method === 'GET'
                    ? {}
                    : { Origin: origin, 'X-CSRF-Token': await csrf(context), 'Content-Type': 'application/json' },
            data
        });

    expect(
        (
            await call(admin, 'POST', '/api/v1/auth/login', {
                identifier: process.env.TEST_ADMIN_USERNAME,
                password: process.env.TEST_ADMIN_PASSWORD
            })
        ).status()
    ).toBe(200);
    const invite = await call(admin, 'POST', '/api/v1/admin/invites', {});
    expect(invite.status()).toBe(201);
    const inviteData = (await invite.json()).data;
    const player = await playwright.request.newContext({ baseURL, extraHTTPHeaders: { Origin: origin } });
    expect(
        (
            await call(player, 'POST', '/api/v1/auth/register', {
                invite_code: inviteData.code,
                email: `poll-player-${Date.now()}@example.test`,
                username: `pollplayer${Date.now()}`,
                display_name: 'Poll Player',
                password: 'PollPlayer-2026!',
                password_confirmation: 'PollPlayer-2026!'
            })
        ).status()
    ).toBe(201);

    const pollResponse = await call(admin, 'POST', '/api/v1/admin/global-polls', {
        title: `Availability ${Date.now()}`,
        explanation: 'Select all dates that work for you.',
        choice_type: 'single',
        options: ['Thursday 29 October 2026', 'Friday 30 October 2026', 'Saturday 31 October 2026']
    });
    expect(pollResponse.status()).toBe(200);
    const poll = (await pollResponse.json()).data;
    expect(poll.choice_type).toBe('multiple');
    const [first, second, third] = poll.options.map((option: { id: number }) => option.id);
    expect(
        (
            await call(admin, 'PUT', `/api/v1/global-polls/${poll.id}/response`, { option_ids: [first, first, second] })
        ).status()
    ).toBe(200);
    expect((await call(admin, 'GET', '/api/v1/global-polls/current')).json()).resolves.toMatchObject({
        data: { selected_option_ids: [first, second] }
    });
    expect(
        (await call(admin, 'PUT', `/api/v1/global-polls/${poll.id}/response`, { option_ids: [second, third] })).status()
    ).toBe(200);
    expect((await call(admin, 'GET', '/api/v1/global-polls/current')).json()).resolves.toMatchObject({
        data: { selected_option_ids: [second, third] }
    });
    await call(admin, 'POST', '/api/v1/auth/logout', {});
    expect(
        (
            await call(admin, 'POST', '/api/v1/auth/login', {
                identifier: process.env.TEST_ADMIN_USERNAME,
                password: process.env.TEST_ADMIN_PASSWORD
            })
        ).status()
    ).toBe(200);
    expect((await call(admin, 'GET', '/api/v1/global-polls/current')).json()).resolves.toMatchObject({
        data: { selected_option_ids: [second, third] }
    });
    expect(
        (
            await call(player, 'PUT', `/api/v1/global-polls/${poll.id}/response`, { option_ids: [first, second] })
        ).status()
    ).toBe(200);
    expect((await call(player, 'PUT', `/api/v1/global-polls/${poll.id}/response`, { option_ids: [] })).status()).toBe(
        422
    );
    expect(
        (await call(player, 'PUT', `/api/v1/global-polls/${poll.id}/response`, { option_ids: [999999] })).status()
    ).toBe(422);
    const other = await call(admin, 'POST', '/api/v1/admin/global-polls', {
        title: `Other ${Date.now()}`,
        options: ['Only', 'Other']
    });
    const otherId = (await other.json()).data.id;
    const otherOption = (await (await call(admin, 'GET', '/api/v1/admin/global-polls')).json()).data;
    const foreignOption = otherOption.find((item: { id: number }) => item.id === otherId).options[0].id;
    expect(
        (
            await call(player, 'PUT', `/api/v1/global-polls/${poll.id}/response`, { option_ids: [foreignOption] })
        ).status()
    ).toBe(422);
    expect((await call(player, 'GET', `/api/v1/admin/global-polls/${poll.id}/results`)).status()).toBe(403);
    expect((await call(admin, 'PATCH', `/api/v1/admin/global-polls/${poll.id}`, { status: 'closed' })).status()).toBe(
        200
    );
    expect(
        (await call(player, 'PUT', `/api/v1/global-polls/${poll.id}/response`, { option_ids: [third] })).status()
    ).toBe(409);
    const results = await call(admin, 'GET', `/api/v1/admin/global-polls/${poll.id}/results`);
    expect(results.status()).toBe(200);
    expect((await results.json()).data).toMatchObject({ total_respondents: 2 });
    await admin.dispose();
    await player.dispose();
});
