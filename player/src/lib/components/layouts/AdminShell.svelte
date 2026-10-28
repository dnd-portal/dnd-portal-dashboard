<script lang="ts">
    import { onMount, tick } from 'svelte';
    import { page } from '$app/state';
    import type { Snippet } from 'svelte';
    import type { Database } from '#lib/admin/types.js';
    import { provideAdminDatabase } from '#lib/admin/context.js';
    import { adminLinks, adminSection } from '#lib/admin/navigation.js';
    import { detailFor, type DetailKey } from '#lib/admin/details.js';
    import Breadcrumbs from '#lib/components/shared/Breadcrumbs.svelte';
    import AdminNavigation from '#lib/components/admin/AdminNavigation.svelte';
    import { api } from '#lib/api/index.js';

    let { children, data }: { children: Snippet; data: { database: Database } } = $props();
    provideAdminDatabase(() => data.database);
    let sidebarOpen = $state(false);
    let hydrated = $state(false);
    let access = $state<'checking' | 'allowed' | 'denied'>('checking');
    let mobile = $state(false);
    let sidebar = $state<HTMLElement>();
    let menuButton = $state<HTMLButtonElement>();
    let navCounts = $state<Record<string, number>>({});
    const active = $derived(adminSection(page.url.pathname));
    const current = $derived(adminLinks.find((link) => link.id === active)!);
    const detail = $derived(
        page.params.id && ['users', 'campaigns', 'sessions', 'polls', 'one-shots', 'payments'].includes(active)
            ? detailFor(active as DetailKey, data.database, Number(page.params.id))
            : undefined
    );
    const breadcrumbItems = $derived([
        { label: 'Admin', href: '/admin' },
        ...(page.params.id
            ? [{ label: current.label, href: current.href }, { label: detail?.title ?? 'Record not found' }]
            : [{ label: current.label }])
    ]);
    const adminTitle = $derived(
        page.params.id
            ? `${detail?.title ?? 'Record not found'} | ${current.label} Admin | D&D Portal Player Dashboard`
            : `${current.label} Admin | D&D Portal Player Dashboard`
    );

    async function toggleSidebar() {
        sidebarOpen = !sidebarOpen;
        if (sidebarOpen) {
            await tick();
            if (!sidebar) return;
            sidebar.querySelector('a')?.focus();
        }
    }
    function closeSidebar(restore = false) {
        sidebarOpen = false;
        if (restore) menuButton?.focus();
    }
    function keyboard(event: KeyboardEvent) {
        if (!sidebarOpen || !mobile) return;
        if (event.key === 'Escape') {
            event.preventDefault();
            closeSidebar(true);
        }
        if (event.key === 'Tab') {
            if (!sidebar || !menuButton) return;
            const focusable = [menuButton, ...sidebar.querySelectorAll<HTMLAnchorElement>('a')];
            const first = focusable[0],
                last = focusable[focusable.length - 1];
            if (!first || !last) return;
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    }
    $effect(() => {
        page.url.pathname;
        sidebarOpen = false;
    });
    onMount(() => {
        hydrated = true;
        const query = matchMedia('(max-width: 760px)');
        const update = () => {
            mobile = query.matches;
            if (!mobile) sidebarOpen = false;
        };
        update();
        void api
            .get<{ roles: string[] }>('/api/v1/auth/me')
            .then((currentUser) => {
                if (!currentUser.roles.includes('portal_admin')) {
                    access = 'denied';
                    return;
                }
                access = 'allowed';
                return api
                    .get<{
                        admin?: { users: number; campaigns: number; sessions: number; polls: number; payments: number };
                    }>('/api/v1/dashboard')
                    .then((dashboard) => {
                        if (!dashboard.admin) return;
                        navCounts = {
                            users: dashboard.admin.users,
                            campaigns: dashboard.admin.campaigns,
                            sessions: dashboard.admin.sessions,
                            polls: dashboard.admin.polls,
                            payments: dashboard.admin.payments
                        };
                    })
                    .catch(() => {
                        // Dashboard counts are optional; access is decided only by /auth/me.
                    });
            })
            .catch(() => {
                access = 'denied';
            });
        query.addEventListener('change', update);
        return () => query.removeEventListener('change', update);
    });
</script>

<svelte:head><title>{adminTitle}</title></svelte:head>
<svelte:window onkeydown={keyboard} />
{#if access === 'checking'}
    <section class="p-state" aria-live="polite"><h1>Checking admin access…</h1></section>
{:else if access === 'denied'}
    <section class="p-state" aria-live="assertive">
        <h1>Admin access required</h1>
        <p>Sign in with an authorized portal administrator account to continue.</p>
        <a href="/login">Return to sign in</a>
    </section>
{:else}
    <div class="p-shell admin-app" class:admin-app--denied={access !== 'allowed'} data-hydrated={hydrated || undefined}>
        <header class="p-shell__header admin-topbar">
            <div class="admin-topbar__inner">
                <button
                    bind:this={menuButton}
                    class="admin-menu-toggle"
                    type="button"
                    aria-label={sidebarOpen ? 'Close admin navigation' : 'Open admin navigation'}
                    aria-expanded={sidebarOpen}
                    aria-controls="admin-sidebar"
                    onclick={toggleSidebar}
                    ><svg
                        width="20"
                        height="20"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" /></svg
                    ></button
                >
                <a class="p-shell__brand admin-brand" href="/admin"
                    ><img src="/dashboard-dnd-portal-logo-wide.svg" alt="D&D Portal" width="110" height="48" /></a
                >
                <Breadcrumbs items={breadcrumbItems} />
                <a class="admin-topbar__action" href="/dashboard">Player dashboard →</a>
            </div>
        </header>
        <aside
            bind:this={sidebar}
            id="admin-sidebar"
            class="p-shell__sidebar admin-sidebar"
            class:admin-sidebar--open={sidebarOpen}
            inert={mobile && !sidebarOpen}
            aria-label="Admin navigation"
        >
            <AdminNavigation active={active ?? 'overview'} counts={navCounts} close={() => closeSidebar()} />
        </aside>
        {#if sidebarOpen && mobile}<button
                class="admin-sidebar__backdrop"
                type="button"
                tabindex="-1"
                aria-label="Close admin navigation"
                onclick={() => closeSidebar(true)}
            ></button>{/if}
        <main class="p-shell__main p-container admin-content" inert={sidebarOpen && mobile}>
            {@render children()}
        </main>
    </div>
{/if}
