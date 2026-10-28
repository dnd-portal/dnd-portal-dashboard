import { expect, test } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import path from 'node:path';

test('new Type A and Type B invitations validate and register over HTTP', async ({ playwright }) => {
    const database = process.env.TEST_DB_NAME ?? '';
    if (!/^dnd_portal_test_run_[0-9]+$/.test(database) || database === 'dnd_portal_v4_development') {
        throw new Error('Refusing invitation test outside a dedicated test database.');
    }
    const adminPassword = process.env.INITIAL_ADMIN_PASSWORD ?? process.env.TEST_ADMIN_PASSWORD;
    if (!adminPassword || !process.env.TEST_ADMIN_USERNAME)
        throw new Error('Dedicated admin credentials are required.');
    const origin = 'http://127.0.0.1:5174';
    const baseURL = process.env.TEST_BACKEND_URL ?? 'http://127.0.0.1:8099';
    const context = await playwright.request.newContext({ baseURL, extraHTTPHeaders: { Origin: origin } });
    const csrf = async () => (await context.get('/api/v1/auth/csrf')).json().then((body) => body.data.token as string);
    const adminLogin = await context.fetch('/api/v1/auth/login', {
        method: 'POST',
        headers: { Origin: origin, 'X-CSRF-Token': await csrf(), 'Content-Type': 'application/json' },
        data: { identifier: process.env.TEST_ADMIN_USERNAME, password: adminPassword }
    });
    expect(adminLogin.status()).toBe(200);
    const suffix = Date.now();
    const pending = await context.fetch('/api/v1/admin/users/pending', {
        method: 'POST',
        headers: { Origin: origin, 'X-CSRF-Token': await csrf(), 'Content-Type': 'application/json' },
        data: { display_name: 'Invitation Type A', username: `typea${suffix}`, role: 'campaign_member' }
    });
    expect(pending.status()).toBe(201);
    const pendingData = (await pending.json()).data;
    const invitation = await context.fetch('/api/v1/admin/invites', {
        method: 'POST',
        headers: { Origin: origin, 'X-CSRF-Token': await csrf(), 'Content-Type': 'application/json' },
        data: { user_id: pendingData.id }
    });
    expect(invitation.status()).toBe(201);
    const invitationData = (await invitation.json()).data;
    const typeA = await playwright.request.newContext({ baseURL, extraHTTPHeaders: { Origin: origin } });
    const typeACsrf = async () =>
        (await typeA.get('/api/v1/auth/csrf')).json().then((body) => body.data.token as string);
    const validate = await typeA.fetch('/api/v1/auth/invite/validate', {
        method: 'POST',
        headers: { Origin: origin, 'X-CSRF-Token': await typeACsrf(), 'Content-Type': 'application/json' },
        data: { invite_code: invitationData.code }
    });
    expect(validate.status()).toBe(200);
    expect((await validate.json()).data).toMatchObject({
        has_pending_user: true,
        display_name: 'Invitation Type A',
        username: `typea${suffix}`
    });
    const register = await typeA.fetch('/api/v1/auth/register', {
        method: 'POST',
        headers: { Origin: origin, 'X-CSRF-Token': await typeACsrf(), 'Content-Type': 'application/json' },
        data: {
            invite_code: invitationData.code,
            email: `typea${suffix}@example.test`,
            username: `ignored${suffix}`,
            display_name: 'Ignored',
            password: 'TypeA-password-2026!',
            password_confirmation: 'TypeA-password-2026!'
        }
    });
    expect(register.status()).toBe(201);
    expect((await register.json()).data.id).toBe(pendingData.id);
    const reuse = await typeA.fetch('/api/v1/auth/register', {
        method: 'POST',
        headers: { Origin: origin, 'X-CSRF-Token': await typeACsrf(), 'Content-Type': 'application/json' },
        data: {
            invite_code: invitationData.code,
            email: `reuse${suffix}@example.test`,
            username: `reuse${suffix}`,
            password: 'TypeA-password-2026!',
            password_confirmation: 'TypeA-password-2026!'
        }
    });
    expect(reuse.status()).toBe(422);
    const typeALogin = await typeA.fetch('/api/v1/auth/login', {
        method: 'POST',
        headers: { Origin: origin, 'X-CSRF-Token': await typeACsrf(), 'Content-Type': 'application/json' },
        data: { identifier: `typea${suffix}`, password: 'TypeA-password-2026!' }
    });
    expect(typeALogin.status()).toBe(200);
    const typeBInvite = await context.fetch('/api/v1/admin/invites', {
        method: 'POST',
        headers: { Origin: origin, 'X-CSRF-Token': await csrf(), 'Content-Type': 'application/json' },
        data: {}
    });
    expect(typeBInvite.status()).toBe(201);
    const typeBData = (await typeBInvite.json()).data;
    const typeB = await playwright.request.newContext({ baseURL, extraHTTPHeaders: { Origin: origin } });
    const typeBCsrf = async () =>
        (await typeB.get('/api/v1/auth/csrf')).json().then((body) => body.data.token as string);
    const typeBValidate = await typeB.fetch('/api/v1/auth/invite/validate', {
        method: 'POST',
        headers: { Origin: origin, 'X-CSRF-Token': await typeBCsrf(), 'Content-Type': 'application/json' },
        data: { invite_code: typeBData.code }
    });
    expect((await typeBValidate.json()).data.has_pending_user).toBe(false);
    const typeBRegister = await typeB.fetch('/api/v1/auth/register', {
        method: 'POST',
        headers: { Origin: origin, 'X-CSRF-Token': await typeBCsrf(), 'Content-Type': 'application/json' },
        data: {
            invite_code: typeBData.code,
            email: `typeb${suffix}@example.test`,
            username: `typeb${suffix}`,
            display_name: 'Invitation Type B',
            password: 'TypeB-password-2026!',
            password_confirmation: 'TypeB-password-2026!'
        }
    });
    expect(typeBRegister.status()).toBe(201);
    const typeBLogin = await typeB.fetch('/api/v1/auth/login', {
        method: 'POST',
        headers: { Origin: origin, 'X-CSRF-Token': await typeBCsrf(), 'Content-Type': 'application/json' },
        data: { identifier: `typeb${suffix}`, password: 'TypeB-password-2026!' }
    });
    expect(typeBLogin.status()).toBe(200);
    const failedInvite = await context.fetch('/api/v1/admin/invites', {
        method: 'POST',
        headers: { Origin: origin, 'X-CSRF-Token': await csrf(), 'Content-Type': 'application/json' },
        data: {}
    });
    const failedInviteData = (await failedInvite.json()).data;
    const failedRegistration = await typeB.fetch('/api/v1/auth/register', {
        method: 'POST',
        headers: { Origin: origin, 'X-CSRF-Token': await typeBCsrf(), 'Content-Type': 'application/json' },
        data: {
            invite_code: failedInviteData.code,
            email: `failed${suffix}@example.test`,
            username: `failed${suffix}`,
            display_name: 'Failed',
            password: 'one-password-2026!',
            password_confirmation: 'different-password-2026!'
        }
    });
    expect(failedRegistration.status()).toBe(422);
    expect(
        (
            await typeB.fetch('/api/v1/auth/invite/validate', {
                method: 'POST',
                headers: { Origin: origin, 'X-CSRF-Token': await typeBCsrf(), 'Content-Type': 'application/json' },
                data: { invite_code: failedInviteData.code }
            })
        ).status()
    ).toBe(200);
    const revokedInvite = await context.fetch('/api/v1/admin/invites', {
        method: 'POST',
        headers: { Origin: origin, 'X-CSRF-Token': await csrf(), 'Content-Type': 'application/json' },
        data: {}
    });
    const revokedData = (await revokedInvite.json()).data;
    expect(
        (
            await context.fetch('/api/v1/admin/invites/revoke', {
                method: 'POST',
                headers: { Origin: origin, 'X-CSRF-Token': await csrf(), 'Content-Type': 'application/json' },
                data: { id: revokedData.id }
            })
        ).status()
    ).toBe(200);
    expect(
        (
            await typeB.fetch('/api/v1/auth/invite/validate', {
                method: 'POST',
                headers: { Origin: origin, 'X-CSRF-Token': await typeBCsrf(), 'Content-Type': 'application/json' },
                data: { invite_code: revokedData.code }
            })
        ).status()
    ).toBe(422);
    const expiredInvite = await context.fetch('/api/v1/admin/invites', {
        method: 'POST',
        headers: { Origin: origin, 'X-CSRF-Token': await csrf(), 'Content-Type': 'application/json' },
        data: {}
    });
    const expiredData = (await expiredInvite.json()).data;
    execFileSync(
        process.env.TEST_PHP_EXE ?? 'php',
        [
            '-r',
            "$pdo=new PDO('mysql:host='.getenv('DB_HOST').';port='.getenv('DB_PORT').';dbname='.getenv('DB_NAME'),getenv('DB_USER'),getenv('DB_PASSWORD')); $q=$pdo->prepare('UPDATE registration_invites SET expires_at=? WHERE code_hash=?'); $q->execute(['2000-01-01 00:00:00', hash('sha256', getenv('TEST_INVITE_CODE'), true)]);"
        ],
        {
            cwd: path.resolve(process.cwd(), '..'),
            env: { ...process.env, TEST_INVITE_CODE: expiredData.code },
            stdio: 'ignore'
        }
    );
    expect(
        (
            await typeB.fetch('/api/v1/auth/invite/validate', {
                method: 'POST',
                headers: { Origin: origin, 'X-CSRF-Token': await typeBCsrf(), 'Content-Type': 'application/json' },
                data: { invite_code: expiredData.code }
            })
        ).status()
    ).toBe(422);
    expect(
        (
            await typeB.fetch('/api/v1/auth/invite/validate', {
                method: 'POST',
                headers: { Origin: origin, 'X-CSRF-Token': await typeBCsrf(), 'Content-Type': 'application/json' },
                data: { invite_code: 'unknown-code-2026' }
            })
        ).status()
    ).toBe(422);
    await context.dispose();
    await typeA.dispose();
    await typeB.dispose();
});
