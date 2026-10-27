import { expect, test } from '@playwright/test';

const enabled = Boolean(
    process.env.POLL_ADMIN_USERNAME && process.env.POLL_ADMIN_PASSWORD && process.env.POLL_PLAYER_PASSWORD
);

async function login(page: import('@playwright/test').Page, identifier: string, password: string) {
    await page.goto('/');
    await page.waitForLoadState('networkidle');
    await page.getByLabel('Email or username').fill(identifier);
    await page.getByLabel('Password').fill(password);
    await page.getByRole('button', { name: /Continue with email/ }).click();
    await expect(page.getByRole('button', { name: 'Sign out' })).toBeVisible();
}

async function newIsolatedPage(page: import('@playwright/test').Page) {
    const browser = page.context().browser();
    if (!browser) throw new Error('A real browser is required for the isolated player context.');
    const context = await browser.newContext();
    return { context, page: await context.newPage() };
}

async function createPoll(
    adminPage: import('@playwright/test').Page,
    choiceType: 'single' | 'multiple',
    title: string
) {
    await adminPage.goto('/admin/polls');
    const campaignId = await adminPage.evaluate(async () => {
        const campaigns = await fetch('/api/v1/campaigns').then((response) => response.json());
        for (const campaign of campaigns.data) {
            const members = await fetch(`/api/v1/campaigns/${campaign.id}/members`).then((response) => response.json());
            if (members.data.some((member: { username: string }) => member.username === 'pollplayer'))
                return Number(campaign.id);
        }
        throw new Error('Poll browser fixture campaign membership was not found.');
    });
    await adminPage.getByLabel('Campaign ID').fill(String(campaignId));
    await adminPage.getByLabel('Poll title').fill(title);
    await adminPage.getByLabel('Choice type').selectOption(choiceType);
    await adminPage.getByLabel('Options, one per line').fill('First\nSecond\nThird');
    await adminPage.getByRole('button', { name: 'Create' }).click();
    const link = adminPage.getByRole('link', { name: title });
    await expect(link).toBeVisible();
    const href = await link.getAttribute('href');
    const pollId = Number(href!.split('/').pop());
    const users = await adminPage.evaluate(async () => (await fetch('/api/v1/users')).json());
    const player = users.data.find((user: { username: string }) => user.username === process.env.POLL_PLAYER_USERNAME);
    expect(player).toBeTruthy();
    const assignment = await adminPage.evaluate(
        async ({ pollId, playerId }) => {
            const csrf = await fetch('/api/v1/auth/csrf').then((response) => response.json());
            const response = await fetch(`/api/v1/polls/${pollId}/participants/${playerId}`, {
                method: 'POST',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf.data.token },
                body: JSON.stringify({ active: true })
            });
            return response.status;
        },
        { pollId, playerId: player.id }
    );
    expect(assignment).toBe(200);
    return pollId;
}

test.describe('real poll player flows', () => {
    test.skip(!enabled, 'The isolated runner supplies real admin and player credentials.');

    test('single-choice vote, change, reload, results and close', async ({ page }) => {
        await login(page, process.env.POLL_ADMIN_USERNAME!, process.env.POLL_ADMIN_PASSWORD!);
        const pollId = await createPoll(page, 'single', `Browser single ${Date.now()}`);
        const player = await newIsolatedPage(page);
        const playerPage = player.page;
        await login(playerPage, process.env.POLL_PLAYER_USERNAME!, process.env.POLL_PLAYER_PASSWORD!);
        await playerPage.goto(`/polls/${pollId}`);
        await playerPage.getByRole('button', { name: 'No option fits' }).click();
        await expect(playerPage.getByText('Response saved.', { exact: true })).toBeVisible();
        await playerPage.reload();
        await expect(playerPage.getByText('No option fits.', { exact: true })).toBeVisible();
        await playerPage.locator('input[name="poll-option"]').nth(0).check();
        await playerPage.getByRole('button', { name: 'Save vote' }).click();
        await expect(playerPage.getByText('Vote saved.', { exact: true }))
            .toBeVisible()
            .catch(async (error) => {
                throw new Error(
                    `${error.message}\nalerts=${(await playerPage.locator('[role=alert]').allTextContents()).join('|')}`
                );
            });
        await playerPage.reload();
        await expect(playerPage.locator('input[name="poll-option"]').nth(0)).toBeChecked();
        await playerPage.locator('input[name="poll-option"]').nth(1).check();
        await playerPage.getByRole('button', { name: 'Save vote' }).click();
        await playerPage.getByRole('button', { name: 'View results' }).click();
        await expect(playerPage.getByText(/Second — 1/)).toBeVisible();
        await page.goto(`/admin/polls/${pollId}`);
        await page.getByRole('button', { name: 'Close poll' }).click();
        await playerPage.reload();
        await expect(playerPage.getByText('This poll is closed or expired.')).toBeVisible();
        await expect(playerPage.getByRole('button', { name: 'Save vote' })).toHaveCount(0);
        await player.context.close();
    });

    test('multiple-choice vote, reload and answer change', async ({ page }) => {
        await login(page, process.env.POLL_ADMIN_USERNAME!, process.env.POLL_ADMIN_PASSWORD!);
        const pollId = await createPoll(page, 'multiple', `Browser multiple ${Date.now()}`);
        const player = await newIsolatedPage(page);
        const playerPage = player.page;
        await login(playerPage, process.env.POLL_PLAYER_USERNAME!, process.env.POLL_PLAYER_PASSWORD!);
        await playerPage.goto(`/polls/${pollId}`);
        await playerPage.getByRole('button', { name: 'No option fits' }).click();
        await expect(playerPage.getByText('Response saved.', { exact: true })).toBeVisible();
        await playerPage.reload();
        await expect(playerPage.getByText('No option fits.', { exact: true })).toBeVisible();
        await playerPage.locator('input[name="poll-option"]').nth(0).check();
        await playerPage.locator('input[name="poll-option"]').nth(1).check();
        await playerPage.getByRole('button', { name: 'Save vote' }).click();
        await expect(playerPage.getByText('Vote saved.', { exact: true })).toBeVisible();
        await playerPage.reload();
        await expect(playerPage.locator('input[name="poll-option"]').nth(0)).toBeChecked();
        await expect(playerPage.locator('input[name="poll-option"]').nth(1)).toBeChecked();
        await playerPage.locator('input[name="poll-option"]').nth(0).uncheck();
        await playerPage.locator('input[name="poll-option"]').nth(2).check();
        await playerPage.getByRole('button', { name: 'Save vote' }).click();
        await playerPage.getByRole('button', { name: 'View results' }).click();
        await expect(playerPage.getByText(/Third — 1/)).toBeVisible();
        await player.context.close();
    });
});
