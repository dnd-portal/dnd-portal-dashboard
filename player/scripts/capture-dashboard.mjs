import { mkdir } from 'node:fs/promises';
import { chromium } from '@playwright/test';

const baseURL = 'http://127.0.0.1:5173';
const username = process.env.PLAYER_DASHBOARD_USERNAME;
const password = process.env.PLAYER_DASHBOARD_PASSWORD;
const outputDirectory = 'test-results/manual-dashboard';
const viewports = [
    { name: 'dashboard-1440x1000', width: 1440, height: 1000 },
    { name: 'dashboard-1920x1080', width: 1920, height: 1080 },
    { name: 'dashboard-390x844', width: 390, height: 844 }
];

if (!username || !password) {
    throw new Error(
        'Missing PLAYER_DASHBOARD_USERNAME or PLAYER_DASHBOARD_PASSWORD. Set both locally; do not commit them.'
    );
}

await mkdir(outputDirectory, { recursive: true });

let browser;
try {
    browser = await chromium.launch({
        headless: true,
        ...(process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE
            ? { executablePath: process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE }
            : {})
    });
    const context = await browser.newContext({ baseURL });
    const page = await context.newPage();

    let frontendResponse;
    try {
        frontendResponse = await page.goto('/', { waitUntil: 'networkidle' });
    } catch (error) {
        throw new Error(`Frontend unavailable at ${baseURL}: ${error.message}`);
    }
    if (!frontendResponse || !frontendResponse.ok()) {
        throw new Error(`Frontend unavailable at ${baseURL} (HTTP ${frontendResponse?.status() ?? 'unknown'}).`);
    }

    const healthResponse = await context.request.get('/api/v1/health');
    if (!healthResponse.ok()) {
        throw new Error(`Backend health check failed through the frontend proxy (HTTP ${healthResponse.status()}).`);
    }

    await page.getByLabel('Email or username').fill(username);
    await page.getByLabel('Password').fill(password);
    await page.getByRole('button', { name: /Continue with email/ }).click();

    try {
        await page.getByRole('button', { name: 'Sign out' }).waitFor({ state: 'visible', timeout: 10000 });
    } catch {
        const message = await page
            .getByRole('alert')
            .first()
            .textContent()
            .catch(() => null);
        throw new Error(`Dashboard login failed.${message ? ` Server message: ${message}` : ''}`);
    }

    for (const viewport of viewports) {
        await page.setViewportSize(viewport);
        const path = `${outputDirectory}/${viewport.name}.png`;
        await page.screenshot({ path, fullPage: true });
        console.log(`Saved ${path}`);
    }
} catch (error) {
    console.error(error instanceof Error ? error.message : error);
    process.exitCode = 1;
} finally {
    await browser?.close();
}
