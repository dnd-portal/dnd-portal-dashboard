import { adminPageMeta, type AdminPageKey } from '#lib/adminPageMeta.js';
export const adminLinks: { id: AdminPageKey; group: string; href: string; label: string }[] = [
    ['overview', 'Overview'],
    ['users', 'Users'],
    ['invite-codes', 'Users'],
    ['polls', 'Polls'],
    ['activity', 'Activity']
    // TODO post-MVP: re-enable campaign administration when campaign workflows are implemented.
    // ['campaigns', 'Database'],
    // TODO post-MVP: re-enable memberships when campaign membership workflows are implemented.
    // ['memberships', 'Database'],
    // TODO post-MVP: re-enable sessions, attendance and campaign polls post-MVP.
    // ['sessions', 'Campaign'],
    // ['attendance', 'Campaign'],
    // ['one-shots', 'Campaign'],
    // TODO post-MVP: re-enable payments when payment administration is implemented.
    // ['payments', 'Finance'],
    // TODO post-MVP: re-enable rate-limit management when it is part of the admin MVP.
    // ['rate-limits', 'System']
].map(([id, group]) => ({
    id: id as AdminPageKey,
    group,
    href: id === 'overview' ? '/admin' : `/admin/${id}`,
    label: id === 'overview' ? 'Overview' : adminPageMeta[id as AdminPageKey].title
}));
export function adminSection(pathname: string): AdminPageKey {
    const section = pathname.split('/').filter(Boolean)[1] ?? 'overview';
    return section in adminPageMeta ? (section as AdminPageKey) : 'overview';
}
