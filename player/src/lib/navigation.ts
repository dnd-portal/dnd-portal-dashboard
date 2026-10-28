export const playerLinks = [
    { id: 'overview', label: 'Overview', href: '/dashboard', icon: 'dashboard' },
    { id: 'polls', label: 'Polls', href: '/dashboard/polls/current', icon: 'polls' },
    // TODO post-MVP: re-enable sessions when campaign session management is implemented.
    // { id: 'sessions', label: 'Sessions', href: '/dashboard/sessions', icon: 'sessions' },
    // TODO post-MVP: re-enable party when campaign membership data is available.
    // { id: 'party', label: 'The party', href: '/dashboard/party', icon: 'users' },
    // TODO post-MVP: re-enable treasury when payment management is implemented.
    // { id: 'payments', label: 'Treasury', href: '/dashboard/payments', icon: 'payments' },
    { id: 'profile', label: 'Profile', href: '/dashboard/profile', icon: 'users' }
] as const;

export function playerLinkIsActive(id: (typeof playerLinks)[number]['id'], pathname: string): boolean {
    if (id === 'overview') return pathname === '/dashboard' || pathname === '/dashboard/';
    return (
        pathname === playerLinks.find((link) => link.id === id)?.href ||
        pathname.startsWith(`${playerLinks.find((link) => link.id === id)?.href}/`)
    );
}

export function playerBreadcrumbs(pathname: string): { label: string; href?: string }[] {
    if (pathname.startsWith('/dashboard/polls')) {
        return [
            { label: 'Dashboard', href: '/dashboard' },
            { label: 'Polls', href: '/dashboard/polls/current' },
            { label: 'Current poll' }
        ];
    }
    if (pathname.startsWith('/dashboard/profile')) {
        return [{ label: 'Dashboard', href: '/dashboard' }, { label: 'Profile' }];
    }
    return [{ label: 'Dashboard', href: '/dashboard' }, { label: 'Overview' }];
}
