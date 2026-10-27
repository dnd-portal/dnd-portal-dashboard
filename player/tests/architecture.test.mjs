import assert from 'node:assert/strict';
import { test } from 'node:test';
import { readdirSync, readFileSync } from 'node:fs';
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
