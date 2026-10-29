import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: './tests/browser',
    fullyParallel: false,
    workers: 1,
    use: {
        baseURL: process.env.TEST_FRONTEND_URL ?? 'http://127.0.0.1:5173',
        ignoreHTTPSErrors: (process.env.TEST_FRONTEND_URL ?? '').startsWith('https://'),
        viewport: { width: 1440, height: 1000 },
        launchOptions: process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE
            ? { executablePath: process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE }
            : {}
    },
    webServer: process.env.TEST_HARNESS_EXTERNAL
        ? undefined
        : {
              command: 'node node_modules/vite/bin/vite.js --host 127.0.0.1 --port 5173',
              url: 'http://127.0.0.1:5173',
              reuseExistingServer: !process.env.CI
          }
});
