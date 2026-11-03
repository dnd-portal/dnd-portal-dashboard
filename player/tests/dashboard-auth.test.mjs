import assert from 'node:assert/strict';
import test from 'node:test';

const { redirectToLogin } = await import('../src/lib/auth/redirect.ts');

test('late 401 navigates once with a safe returnTo', async () => {
    const navigations = [];
    const message = await redirectToLogin(
        async (href) => {
            navigations.push(href);
        },
        '/dashboard',
        '?tab=polls'
    );

    assert.equal(message, null);
    assert.deepEqual(navigations, ['/login?returnTo=%2Fdashboard%3Ftab%3Dpolls']);
});

test('failed login navigation returns a message without retrying', async () => {
    let attempts = 0;
    const message = await redirectToLogin(
        async () => {
            attempts += 1;
            throw new Error('navigation failed');
        },
        '/dashboard',
        ''
    );

    assert.equal(message, 'Your session has expired. Please sign in again.');
    assert.equal(attempts, 1);
});
