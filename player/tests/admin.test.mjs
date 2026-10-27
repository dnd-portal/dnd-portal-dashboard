import assert from 'node:assert/strict';
import { after, before, test } from 'node:test';
import { createServer } from 'vite';

let server;
let database;
let entities;
let navigation;
let details;
let selection;
let sorting;
before(async () => {
    server = await createServer({ server: { middlewareMode: true }, appType: 'custom' });
    const [
        users,
        campaigns,
        oneShots,
        memberships,
        sessions,
        polls,
        pollResponses,
        paymentRequests,
        paymentRequestUsers,
        activity,
        availability,
        user,
        inviteCodes,
        organizerDashboard
    ] = await Promise.all([
        server.ssrLoadModule('/src/lib/database/tables/users.ts'),
        server.ssrLoadModule('/src/lib/database/tables/campaigns.ts'),
        server.ssrLoadModule('/src/lib/database/tables/oneShots.ts'),
        server.ssrLoadModule('/src/lib/database/tables/memberships.ts'),
        server.ssrLoadModule('/src/lib/database/tables/sessions.ts'),
        server.ssrLoadModule('/src/lib/database/tables/polls.ts'),
        server.ssrLoadModule('/src/lib/database/tables/pollResponses.ts'),
        server.ssrLoadModule('/src/lib/database/tables/paymentRequests.ts'),
        server.ssrLoadModule('/src/lib/database/tables/paymentRequestUsers.ts'),
        server.ssrLoadModule('/src/lib/database/tables/activity.ts'),
        server.ssrLoadModule('/src/lib/database/tables/availability.ts'),
        server.ssrLoadModule('/src/lib/database/tables/user.ts'),
        server.ssrLoadModule('/src/lib/database/tables/inviteCodes.ts'),
        server.ssrLoadModule('/src/lib/database/tables/organizerDashboard.ts')
    ]);
    database = {
        users: users.users,
        campaigns: campaigns.campaigns,
        oneShots: oneShots.oneShots,
        memberships: memberships.memberships,
        sessions: sessions.sessions,
        polls: polls.polls,
        pollResponses: pollResponses.pollResponses,
        paymentRequests: paymentRequests.paymentRequests,
        paymentRequestUsers: paymentRequestUsers.paymentRequestUsers,
        activity: activity.activity,
        availability: availability.availability,
        user: user.user,
        inviteCodes: inviteCodes.inviteCodes,
        organizerDashboard: organizerDashboard.organizerDashboard
    };
    entities = await server.ssrLoadModule('/src/lib/admin/entities.ts');
    navigation = await server.ssrLoadModule('/src/lib/admin/navigation.ts');
    details = await server.ssrLoadModule('/src/lib/admin/details.ts');
    selection = await server.ssrLoadModule('/src/lib/admin/selection.ts');
    sorting = await server.ssrLoadModule('/src/lib/admin/sorting.ts');
});
after(async () => {
    await server?.close();
});

test('filtered select-all retains hidden selections and deselects only visible rows', () => {
    assert.deepEqual(selection.toggleVisible(['1', '2'], ['9', '1']), ['9', '1', '2']);
    assert.deepEqual(selection.toggleVisible(['1', '2'], ['9', '1', '2']), ['9']);
    assert.deepEqual(selection.selectionState(['1', '2'], ['9']), { all: false, some: false });
    assert.deepEqual(selection.selectionState(['1', '2'], ['1']), { all: false, some: true });
    assert.deepEqual(selection.selectionState([], ['9']), { all: false, some: false });
});

test('every table adapter supplies complete columns, unique IDs and only existing detail actions', () => {
    for (const [key, definition] of Object.entries(entities.entities)) {
        const rows = definition.rows(database);
        assert.equal(new Set(rows.map((row) => row.id)).size, rows.length, key);
        for (const row of rows) {
            for (const column of definition.columns)
                assert.equal(typeof row.cells[column.key].text, 'string', `${key}.${column.key}`);
            for (const action of row.actions) {
                assert.equal(action.label, 'View details');
                const parts = action.href.split('/');
                assert.ok(details.detailFor(parts[2], database, Number(parts[3])), action.href);
            }
        }
    }
});

test('search and role/status filters combine and retain the existing unregistered URL value', () => {
    const rows = entities.entities.users.rows(database);
    const expected = database.users.filter((user) => !user.registered);
    assert.equal(entities.filterRows(rows, '', { status: 'unregistered' }).length, expected.length);
    assert.equal(entities.filterRows(rows, 'ORGANIZER', { role: 'organizer', status: 'registered' }).length, 1);
    assert.equal(entities.filterRows(rows, 'ORGANIZER', { status: 'unregistered' }).length, 0);
    assert.equal(entities.filterRows(rows, 'does-not-exist', {}).length, 0);
});

test('detail route breadcrumbs resolve their section rather than the final record ID', () => {
    assert.equal(navigation.adminSection('/admin'), 'overview');
    assert.equal(navigation.adminSection('/admin/users/13'), 'users');
    assert.equal(navigation.adminSection('/admin/one-shots/1'), 'one-shots');
    assert.equal(navigation.adminSection('/admin/campaigns/1/'), 'campaigns');
});

test('unknown detail IDs are explicit missing records and memberships link to campaigns', () => {
    assert.equal(details.detailFor('users', database, 999999), undefined);
    assert.equal(details.detailFor('polls', database, NaN), undefined);
    const user = database.memberships[0].userId;
    const detail = details.detailFor('users', database, user);
    for (const row of detail.panels[1].rows) assert.match(row.primary.href, /^\/admin\/campaigns\/\d+$/);
});

test('adapters and details never mutate the shared dataset', () => {
    const original = structuredClone(database);
    for (const definition of Object.values(entities.entities)) definition.rows(database);
    for (const key of ['users', 'campaigns', 'sessions', 'polls', 'one-shots', 'payments'])
        details.detailFor(key, database, 1);
    assert.deepEqual(database, original);
});

test('shared sorting compares typed values naturally and keeps missing values last', () => {
    const rows = [
        { id: 'a', label: '10', cells: { value: { text: '10', value: 10 } }, actions: [] },
        { id: 'b', label: '2', cells: { value: { text: '2', value: 2 } }, actions: [] },
        { id: 'c', label: 'missing', cells: { value: { text: '—', value: null } }, actions: [] }
    ];
    const column = { key: 'value', label: 'Value', sortType: 'number' };
    assert.deepEqual(
        sorting.sortRows(rows, [column], { key: 'value', direction: 'ascending' }).map((row) => row.id),
        ['b', 'a', 'c']
    );
    assert.deepEqual(
        sorting.sortRows(rows, [column], { key: 'value', direction: 'descending' }).map((row) => row.id),
        ['a', 'b', 'c']
    );
});

test('shared sorting handles dates, natural text and stable ties without mutation', () => {
    const rows = [
        {
            id: 'first',
            label: 'Item 10',
            cells: { value: { text: 'Item 10', value: 'Item 10' }, date: { text: '2026-10-02', value: '2026-10-02' } },
            actions: []
        },
        {
            id: 'second',
            label: 'Item 2',
            cells: { value: { text: 'Item 2', value: 'Item 2' }, date: { text: '2026-10-01', value: '2026-10-01' } },
            actions: []
        },
        {
            id: 'third',
            label: 'Item 2',
            cells: { value: { text: 'Item 2', value: 'Item 2' }, date: { text: '2026-10-03', value: '2026-10-03' } },
            actions: []
        }
    ];
    const original = rows.map((row) => row.id);
    assert.deepEqual(
        sorting
            .sortRows(rows, [{ key: 'value', label: 'Value', sortType: 'text' }], {
                key: 'value',
                direction: 'ascending'
            })
            .map((row) => row.id),
        ['second', 'third', 'first']
    );
    assert.deepEqual(
        sorting
            .sortRows(rows, [{ key: 'date', label: 'Date', sortType: 'date' }], { key: 'date', direction: 'ascending' })
            .map((row) => row.id),
        ['second', 'first', 'third']
    );
    assert.deepEqual(
        rows.map((row) => row.id),
        original
    );
});
