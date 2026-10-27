import type { Database, Cell } from './types.js';
import { userName, campaignName, type EntityKey } from './entities.js';

export type DetailKey = Extract<EntityKey, 'users' | 'campaigns' | 'sessions' | 'polls' | 'one-shots' | 'payments'>;
export type Field = { label: string; value: Cell };
export type DetailPanelData = {
    eyebrow: string;
    title?: string;
    fields?: Field[];
    rows?: { id: string; primary: Cell; secondary?: Cell }[];
    empty?: string;
};
export type Detail = { title: string; panels: DetailPanelData[] };
const cell = (value: unknown, href?: string): Cell => ({ text: String(value ?? '—'), href });
const field = (label: string, value: unknown): Field => ({ label, value: cell(value) });
const identity = (db: Database, id: number | string): Cell => ({
    text: userName(db, id),
    kind: 'user',
    href: `/admin/users/${id}`
});
const members = (db: Database, ids: (number | string)[]): DetailPanelData => ({
    eyebrow: 'Members',
    title: `${ids.length} members`,
    rows: ids.map((id) => ({ id: String(id), primary: identity(db, id), secondary: cell(`ID ${id}`) })),
    empty: 'No members are linked to this record.'
});

const definitions: Record<DetailKey, (db: Database, id: number) => Detail | undefined> = {
    users(db, id) {
        const user = db.users.find((item) => item.id === id) ?? (db.user.id === id ? db.user : undefined);
        if (!user) return;
        const memberships = db.memberships.filter((item) => item.userId === id);
        return {
            title: user.name,
            panels: [
                {
                    eyebrow: 'User information',
                    fields: [
                        field('ID', user.id),
                        field('Name', user.name),
                        field('Email', user.email || '—'),
                        field('Username', user.username || '—'),
                        field('Role', user.role),
                        {
                            label: 'Status',
                            value: {
                                text: user.registered ? 'Registered' : 'Not registered',
                                kind: 'status',
                                tone: user.registered ? 'accent' : 'neutral'
                            }
                        }
                    ]
                },
                {
                    eyebrow: 'Campaign memberships',
                    title: `${memberships.length} campaigns`,
                    rows: memberships.map((item) => ({
                        id: String(item.campaignId),
                        primary: cell(campaignName(db, item.campaignId), `/admin/campaigns/${item.campaignId}`)
                    })),
                    empty: 'This user has no campaign memberships yet.'
                }
            ]
        };
    },
    campaigns(db, id) {
        const item = db.campaigns.find((item) => item.id === id);
        if (!item) return;
        const ids = db.memberships
            .filter((membership) => membership.campaignId === id)
            .map((membership) => membership.userId);
        return {
            title: item.name,
            panels: [
                {
                    eyebrow: 'Campaign information',
                    fields: [
                        field('ID', id),
                        field('Name', item.name),
                        field('Slug', item.slug),
                        field('Location', item.location)
                    ]
                },
                members(db, ids)
            ]
        };
    },
    sessions(db, id) {
        const item = db.sessions.find((item) => item.id === id);
        if (!item) return;
        return {
            title: item.title,
            panels: [
                {
                    eyebrow: 'Session information',
                    fields: [
                        field('ID', id),
                        field('Title', item.title),
                        field('Date', item.date),
                        field('Start', item.start),
                        {
                            label: 'Campaign',
                            value: cell(
                                campaignName(db, item.campaignId),
                                item.campaignId ? `/admin/campaigns/${item.campaignId}` : undefined
                            )
                        },
                        {
                            label: 'Status',
                            value: {
                                text: item.status ?? 'Planned',
                                kind: 'status',
                                tone: item.status === 'Played' ? 'success' : 'neutral'
                            }
                        }
                    ]
                },
                members(db, item.members)
            ]
        };
    },
    'one-shots'(db, id) {
        const item = db.oneShots.find((item) => item.id === id);
        if (!item) return;
        return {
            title: item.title,
            panels: [
                {
                    eyebrow: 'One-shot information',
                    fields: [
                        field('ID', id),
                        field('Title', item.title),
                        field('Date', item.date),
                        field('Location', item.location)
                    ]
                },
                members(db, item.members)
            ]
        };
    },
    payments(db, id) {
        const item = db.paymentRequests.find((item) => item.id === id);
        if (!item) return;
        const payments = db.paymentRequestUsers.filter((payment) => payment.paymentRequestId === id);
        return {
            title: item.title,
            panels: [
                {
                    eyebrow: 'Payment request',
                    fields: [
                        field('ID', id),
                        field('Title', item.title),
                        field('Amount', item.amount),
                        field('Status', item.status),
                        { label: 'Link', value: cell('Open payment request ↗', item.paymentUrl) }
                    ]
                },
                {
                    eyebrow: 'Payment status',
                    title: `${payments.filter((payment) => payment.paid).length} / ${payments.length} paid`,
                    rows: payments.map((payment) => ({
                        id: String(payment.userId),
                        primary: identity(db, payment.userId),
                        secondary: {
                            text: payment.paid ? `Paid ${payment.paidAt}` : 'Not paid',
                            kind: 'status',
                            tone: payment.paid ? 'success' : 'neutral'
                        }
                    })),
                    empty: 'No players are linked to this payment request.'
                }
            ]
        };
    },
    polls(db, id) {
        const item = db.polls.find((item) => item.id === id);
        if (!item) return;
        const responses = db.pollResponses.filter((response) => response.pollId === id);
        return {
            title: item.title,
            panels: [
                {
                    eyebrow: 'Options',
                    rows: item.options.map((option) => {
                        const names = responses
                            .filter((response) => response.option === option)
                            .map((response) => userName(db, response.userId));
                        return {
                            id: option,
                            primary: cell(option),
                            secondary: { text: `${names.length} responses`, kind: 'responses', names }
                        };
                    }),
                    empty: 'No options have been added.'
                },
                {
                    eyebrow: 'Responses by user',
                    rows: responses.map((response, index) => ({
                        id: String(index),
                        primary: identity(db, response.userId),
                        secondary: cell(response.option)
                    })),
                    empty: 'No responses have been recorded.'
                }
            ]
        };
    }
};
export function detailFor(entity: DetailKey, db: Database, id: number): Detail | undefined {
    return Number.isFinite(id) ? definitions[entity](db, id) : undefined;
}
