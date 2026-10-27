export type AdminPageKey =
    | 'overview'
    | 'users'
    | 'campaigns'
    | 'sessions'
    | 'polls'
    | 'attendance'
    | 'one-shots'
    | 'memberships'
    | 'invite-codes'
    | 'payments'
    | 'activity'
    | 'rate-limits';

export const adminPageMeta: Record<AdminPageKey, { eyebrow: string; title: string; description: string }> = {
    overview: {
        eyebrow: 'Overview - Admin - D&D Portal Player Dashboard',
        title: 'Admin overview',
        description:
            'Get an overview of your players, campaigns and sessions. Review recent activity and navigate to each administration section.'
    },
    users: {
        eyebrow: 'Users - Admin - D&D Portal Player Dashboard',
        title: 'Users',
        description:
            'View all player accounts and their registration status. Manage account details and roles across the portal.'
    },
    campaigns: {
        eyebrow: 'Campaigns - Admin - D&D Portal Player Dashboard',
        title: 'Campaigns',
        description:
            'View and manage the campaigns in your portal. Keep campaign details, locations and related information up to date.'
    },
    sessions: {
        eyebrow: 'Sessions - Admin - D&D Portal Player Dashboard',
        title: 'Sessions',
        description:
            'Review upcoming and past sessions across your campaigns. Check session dates, participants and current status.'
    },
    polls: {
        eyebrow: 'Polls - Admin - D&D Portal Player Dashboard',
        title: 'Polls',
        description:
            'Review scheduling polls and the dates proposed for each session. See how players have responded to help find a suitable date.'
    },
    attendance: {
        eyebrow: 'Attendance - Admin - D&D Portal Player Dashboard',
        title: 'Attendance',
        description:
            'See when players are unavailable in a shared calendar. Compare dates to help find a suitable moment for your next session.'
    },
    'one-shots': {
        eyebrow: 'One-shots - Admin - D&D Portal Player Dashboard',
        title: 'One-shots',
        description:
            'View and manage standalone adventures outside your regular campaigns. Review their details and participating players.'
    },
    memberships: {
        eyebrow: 'Memberships - Admin - D&D Portal Player Dashboard',
        title: 'Memberships',
        description:
            'View which players belong to each campaign. Manage memberships to keep campaign groups up to date.'
    },
    'invite-codes': {
        eyebrow: 'Invite codes - Admin - D&D Portal Player Dashboard',
        title: 'Invite codes',
        description:
            'View and manage the invitation codes used to join the portal. Check which codes are available and which have already been used.'
    },
    payments: {
        eyebrow: 'Payments - Admin - D&D Portal Player Dashboard',
        title: 'Payments',
        description:
            'Review payment records and contributions from your players. Check amounts and payment status to keep track of outstanding payments.'
    },
    activity: {
        eyebrow: 'Activity - Admin - D&D Portal Player Dashboard',
        title: 'Activity',
        description:
            'Review recent actions and changes across the portal. See who performed each recorded action and when it happened.'
    },
    'rate-limits': {
        eyebrow: 'Security - Admin - D&D Portal Player Dashboard',
        title: 'Login attempts',
        description: 'Review active temporary login blocks and reset a specific IP-based block when needed.'
    }
};
