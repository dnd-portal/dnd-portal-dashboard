import { expect, test } from '@playwright/test';
import fs from 'node:fs';

const adminEmail = process.env.TEST_ADMIN_EMAIL ?? 'admin@example.test';
const password = process.env.TEST_ADMIN_PASSWORD ?? '';
const artifactDir = 'test-artifacts/password-reset-admin';
fs.mkdirSync(artifactDir, { recursive: true });

test('admin can generate and review a password reset code', async ({ page }) => {
    test.skip(!password, 'Set TEST_ADMIN_PASSWORD for the local authenticated admin flow.');
    await page.goto('/login');
    await page.getByLabel('Email or username').fill(adminEmail);
    await page.getByLabel('Password').fill(password);
    await page.getByRole('button', { name: /Continue with email/ }).click();
    await expect(page.getByRole('button', { name: 'Sign out' })).toBeVisible();

    for (const width of [390, 517, 1440]) {
        await page.setViewportSize({ width, height: 900 });
        await page.goto('/admin/users');
        const trigger = page.getByRole('button', { name: /Actions for/ }).first();
        await expect(trigger).toBeVisible();
        await page.screenshot({ path: `${artifactDir}/users-action-${width}.png`, fullPage: true });
        await trigger.click();
        await page.getByRole('menuitem', { name: 'Reset password' }).click();
        await expect(page.getByRole('heading', { name: 'Reset password' })).toBeVisible();
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
        await page.screenshot({ path: `${artifactDir}/reset-confirm-${width}.png`, fullPage: true });
        await page.getByRole('button', { name: 'Generate reset code' }).click();
        await expect(page.getByRole('button', { name: 'Copy reset link' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Copy reset code' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Revoke reset code' })).toBeVisible();
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
        await page.screenshot({ path: `${artifactDir}/reset-result-${width}.png`, fullPage: true });
        await page.getByRole('button', { name: 'Close' }).last().click();
        await page
            .getByRole('button', { name: /Actions for/ })
            .first()
            .click();
        await page.getByRole('menuitem', { name: 'Reset password' }).click();
        await expect(page.getByRole('heading', { name: 'Reset password' })).toBeVisible();
        await page.getByRole('button', { name: 'Cancel' }).click();
    }
});
