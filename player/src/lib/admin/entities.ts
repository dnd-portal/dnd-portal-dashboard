import type { AdminPageKey } from '#lib/adminPageMeta.js';
import type { Cell, Column, Database, EntityRow, Filter, Tone } from './types.js';

export type EntityKey = Exclude<AdminPageKey, 'overview' | 'attendance'>;
type Definition = { columns: Column[]; rows: (database: Database) => EntityRow[]; filterKeys?: string[] };
const text = (value: unknown, href?: string): Cell => ({ text: String(value ?? '—'), value, href });
const status = (value: string, tone: Tone = 'neutral', rawValue?: unknown): Cell => ({
    text: value,
    value: rawValue ?? value,
    kind: 'status',
    tone
});
const columns = (
    labels: Record<string, string>,
    metadata: Record<string, Pick<Column, 'sortType' | 'sortable'>> = {}
) => Object.entries(labels).map(([key, label]) => ({ key, label, ...metadata[key] }));
export function userName(db: Database, id: number | string) {
    return db.users.find((user) => user.id === Number(id))?.name ?? 'Unknown user';
}
export function campaignName(db: Database, id?: number) {
    return db.campaigns.find((campaign) => campaign.id === id)?.name ?? 'One-shot';
}
const memberNames = (db: Database, ids: (string | number)[]) =>
    ids.map((id) => `${id} (${userName(db, id)})`).join(', ') || '—';
const row = (id: string | number, label: string, cells: Record<string, Cell>, href?: string): EntityRow => ({
    id: String(id),
    label,
    cells,
    actions: href ? [{ label: 'View details', href }] : []
});
export const entities: Record<EntityKey, Definition> = {
    users: {
        columns: columns(
            {
                id: 'ID',
                name: 'Name',
                email: 'Email',
                username: 'Username',
                role: 'Role',
                status: 'Status'
            },
            { id: { sortType: 'number' }, status: { sortType: 'boolean' } }
        ),
        filterKeys: ['role', 'status'],
        rows: (db) =>
            db.users.map((user) =>
                row(
                    user.id,
                    user.name,
                    {
                        id: text(user.id),
                        name: { text: user.name, href: `/admin/users/${user.id}`, kind: 'user' },
                        email: text(user.email || '—'),
                        username: text(user.username || '—'),
                        role: text(user.role),
                        status: status(
                            user.registered ? 'Registered' : 'Not registered',
                            user.registered ? 'accent' : 'neutral',
                            user.registered
                        )
                    },
                    `/admin/users/${user.id}`
                )
            )
    },
    campaigns: {
        columns: columns(
            { id: 'ID', name: 'Name', slug: 'Slug', location: 'Location' },
            { id: { sortType: 'number' } }
        ),
        filterKeys: ['location'],
        rows: (db) =>
            db.campaigns.map((item) =>
                row(
                    item.id,
                    item.name,
                    {
                        id: text(item.id),
                        name: text(item.name, `/admin/campaigns/${item.id}`),
                        slug: text(item.slug),
                        location: text(item.location)
                    },
                    `/admin/campaigns/${item.id}`
                )
            )
    },
    sessions: {
        columns: columns(
            {
                id: 'ID',
                title: 'Title',
                date: 'Date',
                campaign: 'Campaign',
                members: 'Members',
                status: 'Status'
            },
            { id: { sortType: 'number' }, date: { sortType: 'date' } }
        ),
        filterKeys: ['status'],
        rows: (db) =>
            db.sessions.map((item) =>
                row(
                    item.id,
                    item.title,
                    {
                        id: text(item.id),
                        title: text(item.title, `/admin/sessions/${item.id}`),
                        date: text(item.date),
                        campaign: text(
                            campaignName(db, item.campaignId),
                            item.campaignId ? `/admin/campaigns/${item.campaignId}` : undefined
                        ),
                        members: text(memberNames(db, item.members)),
                        status: status(item.status ?? 'Planned', item.status === 'Played' ? 'success' : 'neutral')
                    },
                    `/admin/sessions/${item.id}`
                )
            )
    },
    polls: {
        columns: columns(
            { id: 'ID', title: 'Title', options: 'Options', responses: 'Responses', status: 'Status' },
            { id: { sortType: 'number' } }
        ),
        filterKeys: ['status'],
        rows: (db) =>
            db.polls.map((item) => {
                const names = db.pollResponses
                    .filter((response) => response.pollId === item.id)
                    .map((response) => userName(db, response.userId));
                return row(
                    item.id,
                    item.title,
                    {
                        id: text(item.id),
                        title: text(item.title, `/admin/polls/${item.id}`),
                        options: text(item.options.join(' · ')),
                        responses: { text: `${names.length} responses`, kind: 'responses', names },
                        status: status(item.status, item.status === 'Open' ? 'accent' : 'neutral')
                    },
                    `/admin/polls/${item.id}`
                );
            })
    },
    'one-shots': {
        columns: columns(
            { id: 'ID', title: 'Title', date: 'Date', location: 'Location', members: 'Members' },
            { id: { sortType: 'number' }, date: { sortType: 'date' } }
        ),
        filterKeys: ['location'],
        rows: (db) =>
            db.oneShots.map((item) =>
                row(
                    item.id,
                    item.title,
                    {
                        id: text(item.id),
                        title: text(item.title, `/admin/one-shots/${item.id}`),
                        date: text(item.date),
                        location: text(item.location),
                        members: text(memberNames(db, item.members))
                    },
                    `/admin/one-shots/${item.id}`
                )
            )
    },
    memberships: {
        columns: columns({ user: 'User', campaign: 'Campaign', relationship: 'Relationship' }),
        rows: (db) =>
            db.memberships.map((item) =>
                row(`${item.userId}-${item.campaignId}`, userName(db, item.userId), {
                    user: { text: userName(db, item.userId), href: `/admin/users/${item.userId}`, kind: 'user' },
                    campaign: text(campaignName(db, item.campaignId), `/admin/campaigns/${item.campaignId}`),
                    relationship: text('User belongs to campaign')
                })
            )
    },
    'invite-codes': {
        columns: columns({ id: 'ID', code: 'Code', user: 'User', status: 'Status' }, { id: { sortType: 'number' } }),
        filterKeys: ['status'],
        rows: (db) =>
            db.inviteCodes.map((item) =>
                row(item.id, item.code, {
                    id: text(item.id),
                    code: text(item.code),
                    user: { text: userName(db, item.userId), href: `/admin/users/${item.userId}`, kind: 'user' },
                    status: status(item.used ? 'Used' : 'Available', item.used ? 'neutral' : 'accent')
                })
            )
    },
    payments: {
        columns: columns(
            {
                id: 'ID',
                title: 'Title',
                amount: 'Amount',
                link: 'Payment request',
                paid: 'Paid',
                status: 'Status'
            },
            { id: { sortType: 'number' }, amount: { sortType: 'number' } }
        ),
        filterKeys: ['status'],
        rows: (db) =>
            db.paymentRequests.map((item) => {
                const payments = db.paymentRequestUsers.filter((payment) => payment.paymentRequestId === item.id);
                return row(
                    item.id,
                    item.title,
                    {
                        id: text(item.id),
                        title: text(item.title, `/admin/payments/${item.id}`),
                        amount: text(item.amount),
                        link: text('Open payment request ↗', item.paymentUrl),
                        paid: text(`${payments.filter((payment) => payment.paid).length} / ${payments.length}`),
                        status: status(item.status, item.status === 'paid' ? 'success' : 'neutral')
                    },
                    `/admin/payments/${item.id}`
                );
            })
    },
    activity: {
        columns: columns(
            { id: 'ID', user: 'User', activity: 'Activity', time: 'Time' },
            { id: { sortType: 'number' }, time: { sortType: 'date' } }
        ),
        rows: (db) =>
            db.activity.map((item) =>
                row(item.id, item.message, {
                    id: text(item.id),
                    user: { text: userName(db, item.userId), href: `/admin/users/${item.userId}`, kind: 'user' },
                    activity: text(item.message),
                    time: text(item.createdAt)
                })
            )
    }
};

export function filterValue(key: string, cell: Cell): string {
    if (key === 'status' && cell.text === 'Registered') return 'registered';
    if (key === 'status' && cell.text === 'Not registered') return 'unregistered';
    return cell.text.toLowerCase();
}
export function entityFilters(definition: Definition, rows: EntityRow[]): Filter[] {
    return (definition.filterKeys ?? []).map((key) => ({
        key,
        label: definition.columns.find((column) => column.key === key)?.label ?? key,
        options: [
            { value: 'all', label: `All ${key === 'status' ? 'statuses' : `${key}s`}` },
            ...[
                ...new Map(
                    rows.map((row) => [
                        filterValue(key, row.cells[key]),
                        { value: filterValue(key, row.cells[key]), label: row.cells[key].text }
                    ])
                ).values()
            ]
        ]
    }));
}
export function filterRows(rows: EntityRow[], query: string, filters: Record<string, string>): EntityRow[] {
    const needle = query.trim().toLowerCase();
    return rows.filter(
        (row) =>
            (!needle || Object.values(row.cells).some((cell) => cell.text.toLowerCase().includes(needle))) &&
            Object.entries(filters).every(
                ([key, value]) =>
                    !value || value === 'all' || (row.cells[key] && filterValue(key, row.cells[key]) === value)
            )
    );
}
