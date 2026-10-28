import { expect, test, type Page } from '@playwright/test';

async function ready(page: Page) {
    await expect(page.locator('.admin-app')).toHaveAttribute('data-hydrated', 'true');
}

test.beforeEach(async ({ page }) => {
    const identifier = process.env.REAL_BACKEND_EMAIL;
    const password = process.env.REAL_BACKEND_PASSWORD;
    if (!identifier || !password) throw new Error('REAL_BACKEND_EMAIL and REAL_BACKEND_PASSWORD are required.');
    await page.goto('/login');
    await page.getByLabel('Email or username').fill(identifier);
    await page.getByLabel('Password').fill(password);
    await page.getByRole('button', { name: /Continue with email/ }).click();
    await expect(page.getByRole('button', { name: 'Sign out' })).toBeVisible();
});

const routes = [
    '/admin',
    '/admin/users',
    '/admin/campaigns',
    '/admin/sessions',
    '/admin/polls',
    '/admin/one-shots',
    '/admin/memberships',
    '/admin/invite-codes',
    '/admin/payments',
    '/admin/activity',
    '/admin/attendance',
    '/admin/users/13',
    '/admin/campaigns/1',
    '/admin/sessions/1',
    '/admin/polls/1',
    '/admin/one-shots/1',
    '/admin/payments/1'
];

test('admin routes share aligned containers and do not overflow on desktop or mobile', async ({ page }) => {
    const errors: string[] = [];
    page.on('pageerror', (error) => errors.push(error.message));
    for (const width of [1440, 390]) {
        await page.setViewportSize({ width, height: 1000 });
        for (const route of routes) {
            const response = await page.goto(route);
            expect(response?.status(), route).toBe(200);
            await ready(page);
            await expect(page.locator('.admin-header__title')).toBeVisible();
            const geometry = await page.evaluate(() => {
                const header = document.querySelector('.admin-header')!.getBoundingClientRect();
                const content = document.querySelector('.admin-shell')!.getBoundingClientRect();
                return {
                    left: header.left - content.left,
                    right: header.right - content.right,
                    overflow: document.documentElement.scrollWidth - innerWidth
                };
            });
            expect(Math.abs(geometry.left), route).toBeLessThan(1);
            expect(Math.abs(geometry.right), route).toBeLessThan(1);
            expect(geometry.overflow, route).toBeLessThanOrEqual(1);
        }
    }
    expect(errors).toEqual([]);
});

test('combined filters survive refresh and browser back', async ({ page }) => {
    await page.goto('/admin/users');
    await ready(page);
    const registeredRow = page
        .locator('tbody tr')
        .filter({ has: page.getByText('Registered', { exact: true }) })
        .first();
    const registeredName = (await registeredRow.getByRole('link').innerText()).trim();
    const registeredSearch = registeredName.split(/\s+/)[0];
    const totalUsers = await page.locator('tbody tr').count();
    await page.getByLabel('Search users').fill(registeredSearch);
    await expect(page.locator('tbody tr')).toHaveCount(1);
    const status = page.getByRole('button', { name: /Status/ });
    await status.focus();
    await page.keyboard.press('ArrowDown');
    await expect(page.getByRole('listbox')).toBeVisible();
    await page.keyboard.press('Escape');
    await expect(page.getByRole('listbox')).not.toBeVisible();
    await status.click();
    await expect(page.getByRole('listbox')).toBeVisible();
    await page.getByRole('option', { name: 'Not registered', exact: true }).click();
    await expect(page.getByText('No records match the current search and filters.')).toBeVisible();
    await expect(page).toHaveURL(/q=[^&]+&status=unregistered|status=unregistered&q=/);
    await page.reload();
    await ready(page);
    await expect(page.getByLabel('Search users')).toHaveValue(registeredSearch);
    await expect(page.getByRole('button', { name: /Status/ })).toContainText('Not registered');
    await page
        .locator('.admin-sidebar')
        .getByRole('link', { name: /Campaigns/ })
        .click();
    await expect(page).toHaveURL(/\/admin\/campaigns$/);
    await expect(page.getByRole('heading', { name: 'Campaigns', exact: true })).toBeVisible();
    await page.goBack();
    await ready(page);
    await expect(page.getByLabel('Search users')).toHaveValue(registeredSearch);
    await page.getByRole('button', { name: 'Clear filters' }).click();
    await expect(page.locator('tbody tr')).toHaveCount(totalUsers);
});

test('selection stays correct when the visible subset changes', async ({ page }) => {
    await page.goto('/admin/users');
    await ready(page);
    const all = page.getByRole('checkbox', { name: 'Select all visible rows' });
    const rowCheckboxes = page.locator('tbody tr input[type="checkbox"]');
    await rowCheckboxes.nth(1).check();
    await expect(all).toHaveJSProperty('indeterminate', true);
    const registeredRow = page
        .locator('tbody tr')
        .filter({ has: page.locator('.status-label--accent') })
        .first();
    const registeredName = (
        await registeredRow.locator('.user-identity > span:not(.user-identity__avatar)').innerText()
    ).trim();
    await page.getByLabel('Search users').fill(registeredName.split(/\s+/)[0]);
    await expect(page.locator('tbody tr')).toHaveCount(1);
    await expect(all).toHaveJSProperty('indeterminate', false);
    await all.check();
    await expect(page.getByText('2 selected')).toBeVisible();
    await all.uncheck();
    await expect(page.getByText('1 selected')).toBeVisible();
    await page.getByRole('button', { name: 'Clear selection' }).click();
    await expect(page.locator('.admin-table__selection')).toHaveCount(0);
});

test('row actions use keyboard focus, stay in the viewport and return focus on Escape', async ({ page }) => {
    await page.goto('/admin/users');
    await ready(page);
    const firstUserName = (
        await page.locator('tbody tr').first().locator('.user-identity > span:not(.user-identity__avatar)').innerText()
    ).trim();
    const trigger = page.getByRole('button', { name: `Actions for ${firstUserName}` });
    await trigger.focus();
    await page.keyboard.press('ArrowDown');
    await expect(page.getByRole('menuitem', { name: 'View details' })).toBeFocused();
    const rect = await page.getByRole('menu').boundingBox();
    expect(rect).not.toBeNull();
    expect(rect!.x).toBeGreaterThanOrEqual(0);
    expect(rect!.x + rect!.width).toBeLessThanOrEqual(1440);
    await page.keyboard.press('Escape');
    await expect(trigger).toBeFocused();
    await trigger.click();
    await page.getByRole('heading', { name: 'Users', exact: true }).click();
    await expect(page.getByRole('menu')).not.toBeVisible();
});

test('detail breadcrumbs and missing records remain explicit', async ({ page }) => {
    await page.goto('/admin/users');
    await ready(page);
    const userLink = page.locator('tbody tr').first().locator('a.user-identity');
    const userName = (await userLink.locator('> span:not(.user-identity__avatar)').innerText()).trim();
    const userHref = await userLink.getAttribute('href');
    expect(userHref).toMatch(/^\/admin\/users\/\d+$/);
    await page.goto(userHref!);
    await ready(page);
    await expect(page.getByRole('navigation', { name: 'Breadcrumb' })).toContainText(userName);
    await expect(page.locator('.admin-sidebar__link--active')).toContainText('Users');
    await expect(page).toHaveTitle(`${userName} | Users Admin | D&D Portal Player Dashboard`);
    await page.goto('/admin/users/99999');
    await ready(page);
    await expect(page.getByRole('heading', { name: 'Record not found' })).toBeVisible();
});

test('calendar navigation, response popovers and mobile sidebar work', async ({ page }) => {
    await page.goto('/admin/attendance');
    await ready(page);
    await page.getByRole('button', { name: 'Next month' }).click();
    await expect(page.getByRole('heading', { name: 'November 2026' })).toBeVisible();
    await page.getByRole('button', { name: 'Previous month' }).click();
    await page
        .getByRole('button', { name: /unavailable/ })
        .first()
        .click();
    await expect(page.locator('.popover__content:popover-open')).toBeVisible();
    await page.keyboard.press('Escape');
    await page.setViewportSize({ width: 390, height: 844 });
    await page.getByRole('button', { name: 'Open admin navigation' }).click();
    await expect(page.locator('.admin-sidebar a').first()).toBeFocused();
    await page.keyboard.press('Escape');
    await expect(page.getByRole('button', { name: 'Open admin navigation' })).toBeFocused();
});

test('registration and player routes do not leak their typography into admin', async ({ page }) => {
    for (const route of ['/register', '/', '/?role=player', '/?role=dm']) {
        await page.goto(route);
        await page.setViewportSize({ width: 390, height: 844 });
        await expect
            .poll(() => page.evaluate(() => document.documentElement.scrollWidth - innerWidth))
            .toBeLessThanOrEqual(1);
    }
    await page.goto('/admin/users');
    await ready(page);
    await expect(page.locator('.admin-header__title')).toHaveCSS('text-align', 'start');
    const typography = await page.locator('.admin-header__title').evaluate((element) => {
        const style = getComputedStyle(element);
        return Number.parseFloat(style.lineHeight) / Number.parseFloat(style.fontSize);
    });
    expect(typography).toBeCloseTo(0.95, 2);
});
