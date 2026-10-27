import assert from 'node:assert/strict';
import { test } from 'node:test';
import { existsSync, readdirSync, readFileSync } from 'node:fs';
import { join } from 'node:path';

function files(directory) {
    return readdirSync(directory, { withFileTypes: true }).flatMap((entry) =>
        entry.isDirectory() ? files(join(directory, entry.name)) : [join(directory, entry.name)]
    );
}

test('component styles cannot regress to style tags, static inline styles or multiple entrypoints', () => {
    const sources = files('src').filter((file) => file.endsWith('.svelte') || file.endsWith('.html'));
    const imports = [];
    for (const file of sources) {
        const source = readFileSync(file, 'utf8');
        assert.doesNotMatch(source, /<style(?:\s|>)/i, file);
        assert.doesNotMatch(source, /\sstyle\s*=\s*["']|\sstyle:/, file);
        if (/\sstyle\s*=/.test(source)) {
            assert.ok(file.endsWith('AttendanceCalendar.svelte'), `Review new dynamic styles in ${file}`);
            assert.match(source, /--unavailable/, file);
        }
        if (/import\s+['"][^'"]+\.scss['"]/.test(source)) imports.push(file);
    }
    assert.deepEqual(imports, [join('src', 'routes', '+layout.svelte')]);
});

test('external SCSS has no legacy catch-all, Svelte global syntax or important overrides', () => {
    for (const file of files('src/lib/styles')) {
        assert.ok(!file.includes('legacy'), file);
        if (!file.endsWith('.scss')) continue;
        assert.doesNotMatch(readFileSync(file, 'utf8'), /:global\(|!important/, file);
    }
});

test('route groups keep public, auth, player and admin ownership explicit', () => {
    const required = [
        'src/routes/(public)/+page.svelte',
        'src/routes/(auth)/+layout.svelte',
        'src/routes/(auth)/login/+page.svelte',
        'src/routes/(logged-in)/+layout.svelte',
        'src/routes/(logged-in)/dashboard/+page.svelte',
        'src/routes/(logged-in)/dashboard/sessions/+page.svelte',
        'src/routes/(logged-in)/dashboard/party/+page.svelte',
        'src/routes/(logged-in)/dashboard/payments/+page.svelte',
        'src/routes/(is-admin)/+layout.svelte',
        'src/routes/(is-admin)/admin/+page.svelte'
    ];
    for (const file of required) assert.equal(existsSync(file), true, file);

    const playerRoutes = files('src/routes/(logged-in)')
        .filter((file) => file.endsWith('.svelte'))
        .filter(
            (file) => !file.endsWith('(logged-in)\\+layout.svelte') && !file.endsWith('(logged-in)/+layout.svelte')
        );
    for (const file of playerRoutes) {
        const source = readFileSync(file, 'utf8');
        assert.equal((source.match(/<PlayerShell/g) ?? []).length, 0, file);
    }
    assert.doesNotMatch(readFileSync('src/routes/(public)/+page.svelte', 'utf8'), /PlayerShell|admin-sidebar/);
    assert.match(readFileSync('src/routes/(logged-in)/+layout.svelte', 'utf8'), /PlayerShell/);
    assert.match(readFileSync('src/routes/(is-admin)/+layout.svelte', 'utf8'), /AdminShell/);
});
