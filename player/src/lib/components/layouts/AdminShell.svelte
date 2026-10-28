<script lang="ts">
    import { onMount } from 'svelte';
    import { page } from '$app/state';
    import type { Snippet } from 'svelte';
    import type { Database } from '#lib/admin/types.js';
    import { provideAdminDatabase } from '#lib/admin/context.js';
    import { adminSection } from '#lib/admin/navigation.js';
    import { detailFor, type DetailKey } from '#lib/admin/details.js';
    import { api } from '#lib/api/index.js';
    import DashboardShell from './DashboardShell.svelte';

    let { children, data }: { children: Snippet; data: { database: Database } } = $props();
    provideAdminDatabase(() => data.database);
    let access = $state<'checking' | 'allowed' | 'denied'>('checking');
    let user = $state<{ roles: string[] }>({ roles: [] });
    const active = $derived(adminSection(page.url.pathname));
    const detail = $derived(
        page.params.id && ['users', 'campaigns', 'sessions', 'polls', 'one-shots', 'payments'].includes(active)
            ? detailFor(active as DetailKey, data.database, Number(page.params.id))
            : undefined
    );
    const labels: Record<string, string> = {
        overview: 'Overview',
        users: 'Users',
        'invite-codes': 'Invite codes',
        polls: 'Polls',
        activity: 'Activity'
    };
    const navigation = [
        {
            label: 'ADMINISTRATION',
            links: [
                { id: 'overview', label: 'Overview', href: '/admin', icon: 'overview' },
                { id: 'users', label: 'Users', href: '/admin/users', icon: 'users' },
                { id: 'invite-codes', label: 'Invite codes', href: '/admin/invite-codes', icon: 'invite-codes' },
                { id: 'polls', label: 'Polls', href: '/admin/polls', icon: 'polls' },
                { id: 'activity', label: 'Activity', href: '/admin/activity', icon: 'activity' }
            ]
        }
    ];
    const breadcrumbs = $derived([
        { label: 'Admin', href: '/admin' },
        ...(page.params.id
            ? page.url.pathname.endsWith('/results')
                ? [
                      { label: labels[active] ?? active, href: `/admin/${active}` },
                      { label: detail?.title ?? 'Poll' },
                      { label: 'Results' }
                  ]
                : [{ label: labels[active] ?? active, href: `/admin/${active}` }, { label: detail?.title ?? 'Record' }]
            : [{ label: labels[active] ?? active }])
    ]);

    onMount(() => {
        api.get<{ roles: string[] }>('/api/v1/auth/me')
            .then((current) => {
                user = current;
                access = current.roles.includes('portal_admin') ? 'allowed' : 'denied';
            })
            .catch(() => (access = 'denied'));
    });
</script>

<svelte:head><title>{labels[active] ?? 'Admin'} Admin | D&amp;D Portal Player Dashboard</title></svelte:head>
{#if access === 'checking'}
    <section class="p-state" aria-live="polite"><h1>Checking admin access…</h1></section>
{:else if access === 'denied'}
    <section class="p-state" aria-live="assertive">
        <h1>Admin access required</h1>
        <p>Sign in with an authorized portal administrator account to continue.</p>
        <a href="/login">Return to sign in</a>
    </section>
{:else}
    <DashboardShell {user} {navigation} {breadcrumbs} backToDashboard>{@render children()}</DashboardShell>
{/if}
