import { adminPageMeta, type AdminPageKey } from '#lib/adminPageMeta.js';
export const adminLinks: { id: AdminPageKey; group: string; href: string; label: string }[] = [
    ['overview', 'Overview'],
    ['users', 'Database'],
    ['campaigns', 'Database'],
    ['memberships', 'Database'],
    ['invite-codes', 'Database'],
    ['sessions', 'Campaign'],
    ['polls', 'Campaign'],
    ['attendance', 'Campaign'],
    ['one-shots', 'Campaign'],
    ['payments', 'Finance'],
    ['activity', 'System'],
    ['rate-limits', 'System']
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
