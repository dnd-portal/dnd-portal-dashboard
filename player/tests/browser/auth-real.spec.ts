import { expect, test } from '@playwright/test';

const enabled = Boolean(process.env.REAL_BACKEND_EMAIL && process.env.REAL_BACKEND_PASSWORD);
async function waitForApp(page: import('@playwright/test').Page) {
    await expect(page.locator('.admin-app')).toHaveAttribute('data-hydrated', 'true');
}

test.describe('real PHP auth flow', () => {
    test.skip(!enabled, 'Set REAL_BACKEND_EMAIL and REAL_BACKEND_PASSWORD for the isolated backend test environment.');

    test('login, current user and logout use the PHP backend', async ({ page }) => {
        await page.goto('/');
        await page.waitForLoadState('networkidle');
        await page.getByLabel('Email or username').fill(process.env.REAL_BACKEND_EMAIL!);
        await page.getByLabel('Password').fill(process.env.REAL_BACKEND_PASSWORD!);
        const loginButton = page.getByRole('button', { name: /Continue with email/ });
        await expect(loginButton).toBeEnabled();
        await loginButton.click();
        await expect(page.getByText('Organizer online')).toBeVisible();
        await page.getByRole('button', { name: 'Sign out' }).click();
        await expect(page.getByLabel('Email or username')).toBeVisible();
    });

    test('invite generation, replacement and activation use the real forms', async ({ page }) => {
        const apiTrace: string[] = [];
        page.on('request', (request) => {
            if (request.url().includes('/api/v1/admin/invites'))
                apiTrace.push(
                    `REQUEST ${request.method()} ${request.url()} body=${JSON.stringify(request.postDataJSON?.() ?? {})}`
                );
        });
        page.on('response', async (response) => {
            if (response.url().includes('/api/v1/admin/invites'))
                apiTrace.push(
                    `RESPONSE ${response.status()} ${JSON.stringify(await response.json().catch(() => ({})))}`
                );
        });
        const suffix = Date.now();
        const invitedEmail = `invite-${suffix}@example.test`;
        const invitedUsername = `invite${suffix}`;
        await page.goto('/');
        await page.waitForLoadState('networkidle');
        await page.getByLabel('Email or username').fill(process.env.REAL_BACKEND_EMAIL!);
        await page.getByLabel('Password').fill(process.env.REAL_BACKEND_PASSWORD!);
        await page.getByRole('button', { name: /Continue with email/ }).click();
        await expect(page.getByText('Organizer online')).toBeVisible();
        await page.goto('/admin/invite-codes');
        await waitForApp(page);
        await page.getByLabel('Email').fill(invitedEmail);
        const inviteButton = page.getByRole('button', { name: 'Generate invite' });
        apiTrace.push(
            `UI email=${await page.getByLabel('Email').inputValue()} disabled=${await inviteButton.isDisabled()}`
        );
        await inviteButton.click();
        try {
            await expect(page.getByText(/Code:/)).toBeVisible({ timeout: 10_000 });
        } catch {
            throw new Error(`${apiTrace.join(' | ')} UI=${await page.locator('[role=alert]').allTextContents()}`);
        }
        const code = (await page.getByText(/Code:/).innerText()).replace(/^Code:\s*/, '').trim();
        expect(code.length).toBeGreaterThan(20);
        await page.getByRole('button', { name: 'Revoke' }).click();
        await page.goto('/register');
        await page.getByLabel('Invite code').fill(code);
        await page.getByRole('button', { name: 'Continue' }).click();
        await expect(page.getByText(/invalid|expired|failed/i)).toBeVisible();
        await page.goto('/admin/invite-codes');
        await page.getByLabel('Email').fill(invitedEmail);
        await page.getByRole('button', { name: 'Generate invite' }).click();
        const replacement = (await page.getByText(/Code:/).innerText()).replace(/^Code:\s*/, '').trim();
        await page.goto('/register');
        await page.getByLabel('Invite code').fill(replacement);
        await page.getByRole('button', { name: 'Continue' }).click();
        await expect(page.getByText('Create your account')).toBeVisible();
        await page.getByLabel('Email').fill(invitedEmail);
        await page.getByLabel('Username').fill(invitedUsername);
        await page.getByLabel('Password').fill('InvitePassword2026!');
        await page.getByRole('button', { name: 'Create account' }).click();
        await expect(page.getByText(/Your testing account has been created/)).toBeVisible();
    });

    test('full user edit and password reset flow uses forms and buttons', async ({ page }) => {
        const userTrace: string[] = [];
        page.on('request', (request) => {
            if (request.url().includes('/api/v1/users')) userTrace.push(`REQUEST ${request.method()} ${request.url()}`);
        });
        page.on('response', async (response) => {
            if (response.url().includes('/api/v1/users'))
                userTrace.push(
                    `RESPONSE ${response.status()} ${JSON.stringify(await response.json().catch(() => ({})))}`
                );
        });
        const email = process.env.REAL_BACKEND_EMAIL!;
        const oldPassword = process.env.REAL_BACKEND_PASSWORD!;
        const newPassword = `${oldPassword}-reset`;
        const meBeforeUsers = { status: -1, body: {} };
        const cookieMeta = (await page.context().cookies()).map((cookie) => ({
            name: cookie.name,
            domain: cookie.domain,
            path: cookie.path,
            httpOnly: cookie.httpOnly,
            sameSite: cookie.sameSite
        }));
        await page.goto('/');
        await page.waitForLoadState('networkidle');
        await page.getByLabel('Email or username').fill(email);
        await page.getByLabel('Password').fill(oldPassword);
        const loginButton = page.getByRole('button', { name: /Continue with email/ });
        await expect(loginButton).toBeEnabled();
        await loginButton.click();
        await expect(page.getByText('Organizer online')).toBeVisible();
        const meAfterLogin = await page.evaluate(async () => {
            const response = await fetch('/api/v1/auth/me', { credentials: 'include' });
            return { status: response.status, body: await response.json() };
        });

        await page.goto('/admin/users');
        await page.waitForLoadState('networkidle');
        await waitForApp(page);
        const userRows = await page.locator('tbody tr').count();
        const userErrors = await page.locator('[role=alert]').allTextContents();
        if (!userRows || !(await page.locator('tbody a').count()))
            throw new Error(
                `meBefore=${JSON.stringify(meBeforeUsers)} meAfter=${JSON.stringify(meAfterLogin)} cookies=${JSON.stringify(cookieMeta)} user rows=${userRows} errors=${userErrors.join('|')} trace=${userTrace.join(' | ')}`
            );
        const userDetailHref = await page.locator('tbody a').first().getAttribute('href');
        expect(userDetailHref).toMatch(/^\/admin\/users\/\d+$/);
        await page.goto(userDetailHref!);
        await page.waitForLoadState('networkidle');
        await page.getByRole('button', { name: 'Edit user' }).click();
        await page.getByRole('textbox', { name: 'Name', exact: true }).fill('Integration Admin Edited');
        await page.getByRole('button', { name: 'Save changes' }).click();
        await expect(page.getByRole('button', { name: 'Edit user' })).toBeVisible();

        await page.getByRole('button', { name: 'Generate reset code' }).click();
        const codeText = page.getByText(/Code:/).first();
        await expect(codeText).toBeVisible();
        const code = (await codeText.innerText()).replace(/^Code:\s*/, '').trim();
        expect(code.length).toBeGreaterThan(20);

        await page.goto('/?role=player');
        await page.waitForLoadState('networkidle');
        await page.getByRole('button', { name: 'Sign out' }).click();
        await expect(page.getByLabel('Email or username')).toBeVisible();

        await page.goto(`/reset-password?code=${encodeURIComponent(code)}`);
        await page.getByLabel('Email or username').fill(email);
        await page.getByLabel('Reset code').fill(code);
        await page.getByRole('button', { name: 'Validate code' }).click();
        await page.getByLabel('New password').fill(newPassword);
        await page.getByLabel('Confirm password').fill(newPassword);
        await page.getByRole('button', { name: 'Set password' }).click();
        await expect(page.getByText('Your password was changed.')).toBeVisible();

        await page.goto('/');
        await page.getByLabel('Email or username').fill(email);
        await page.getByLabel('Password').fill(newPassword);
        await page.getByRole('button', { name: /Continue with email/ }).click();
        await expect(page.getByText('Organizer online')).toBeVisible({
            timeout: 5000
        }).catch(async (error) => {
            const alerts = await page.locator('[role=alert]').allTextContents();
            throw new Error(`${error.message}\npost-reset alerts=${alerts.join('|')}`);
        });

        // Restore the isolated runner fixture so the following independent
        // browser test does not inherit a changed password.
        await page.goto('/admin/users');
        const restoreUserHref = await page.locator('tbody a').first().getAttribute('href');
        await page.goto(restoreUserHref!);
        await page.getByRole('button', { name: 'Generate reset code' }).click();
        const restoreCode = (await page.getByText(/Code:/).first().innerText()).replace(/^Code:\s*/, '').trim();
        await page.goto('/?role=player');
        await page.getByRole('button', { name: 'Sign out' }).click();
        await page.goto(`/reset-password?code=${encodeURIComponent(restoreCode)}`);
        await page.getByLabel('Email or username').fill(email);
        await page.getByLabel('Reset code').fill(restoreCode);
        await page.getByRole('button', { name: 'Validate code' }).click();
        await page.getByLabel('New password').fill(oldPassword);
        await page.getByLabel('Confirm password').fill(oldPassword);
        await page.getByRole('button', { name: 'Set password' }).click();
        await expect(page.getByText('Your password was changed.')).toBeVisible();
    });

    test('membership add, role change, end and reactivation persist after reload', async ({ page }) => {
        const membershipTrace: string[] = [];
        page.on('request', (request) => {
            if (request.url().includes('/members'))
                membershipTrace.push(`REQUEST ${request.method()} ${request.url()}`);
        });
        page.on('response', async (response) => {
            if (response.url().includes('/members'))
                membershipTrace.push(
                    `RESPONSE ${response.status()} ${JSON.stringify(await response.json().catch(() => ({})))}`
                );
        });
        await page.goto('/');
        await page.waitForLoadState('networkidle');
        await page.getByLabel('Email or username').fill(process.env.REAL_BACKEND_EMAIL!);
        await page.getByLabel('Password').fill(process.env.REAL_BACKEND_PASSWORD!);
        await page.getByRole('button', { name: /Continue with email/ }).click();
        await expect(page.getByText('Organizer online')).toBeVisible();
        const me = await page.evaluate(async () => (await fetch('/api/v1/auth/me')).json());
        const userId = Number(me.data.id);

        await page.goto('/admin/campaigns');
        await waitForApp(page);
        const campaignLink = page.locator('tbody a[href^="/admin/campaigns/"]').first();
        await expect(campaignLink).toBeVisible();
        const campaignId = Number((await campaignLink.getAttribute('href'))?.split('/').pop());
        expect(campaignId).toBeGreaterThan(0);

        await page.goto('/admin/memberships');
        await waitForApp(page);
        await page.getByLabel('Campaign ID').fill(String(campaignId));
        await page.getByLabel('User ID').fill(String(userId));
        await page.getByLabel('Role').selectOption('member');
        const membershipButton = page.getByRole('button', { name: 'Add or reactivate' });
        await membershipButton.click();
        await expect(membershipButton).toBeEnabled();
        await page.getByRole('button', { name: 'Load members' }).click();
        await expect(page.locator('tbody tr').first())
            .toBeVisible({ timeout: 10_000 })
            .catch(() => {
                throw new Error(`Membership flow produced no row. ${membershipTrace.join(' | ')}`);
            });

        await page.reload();
        await waitForApp(page);
        await page.getByLabel('Campaign ID').fill(String(campaignId));
        await page.getByRole('button', { name: 'Load members' }).click();
        await expect(page.getByText('Active', { exact: true })).toBeVisible();

        await page.getByLabel('User ID').fill(String(userId));
        await page.getByLabel('Role', { exact: true }).selectOption('dm');
        await membershipButton.click();
        await expect(membershipButton).toBeEnabled();
        await page.getByRole('button', { name: 'Load members' }).click();
        await expect(page.getByText('dm', { exact: true })).toBeVisible();

        await page.getByRole('button', { name: 'End membership' }).click();
        await page.getByRole('button', { name: 'Load members' }).click();
        await expect(page.getByText('Ended', { exact: true })).toBeVisible();

        await page.getByLabel('Role', { exact: true }).selectOption('member');
        await membershipButton.click();
        await expect(membershipButton).toBeEnabled();
        await page.reload();
        await waitForApp(page);
        await page.getByLabel('Campaign ID').fill(String(campaignId));
        await page.getByRole('button', { name: 'Load members' }).click();
        await expect(page.getByText('Active', { exact: true })).toBeVisible();
    });
});
