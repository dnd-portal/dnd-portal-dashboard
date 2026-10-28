export const playerLinks = [
    { id: 'overview', label: 'Overview', href: '/dashboard', icon: 'dashboard' },
    // TODO post-MVP: re-enable sessions when campaign session management is implemented.
    // { id: 'sessions', label: 'Sessions', href: '/dashboard/sessions', icon: 'sessions' },
    // TODO post-MVP: re-enable party when campaign membership data is available.
    // { id: 'party', label: 'The party', href: '/dashboard/party', icon: 'users' },
    // TODO post-MVP: re-enable treasury when payment management is implemented.
    // { id: 'payments', label: 'Treasury', href: '/dashboard/payments', icon: 'payments' },
    { id: 'profile', label: 'Profile', href: '/dashboard/profile', icon: 'users' }
] as const;
