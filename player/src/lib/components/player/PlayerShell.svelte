<script lang="ts">
    import { page } from '$app/state';
    import { api } from '#lib/api/index.js';
    import type { Snippet } from 'svelte';

    type User = { display_name: string; username: string | null; roles?: string[] };
    let {
        user,
        campaignName = 'No campaign',
        children
    }: { user: User; campaignName?: string; children: Snippet } = $props();
    const active = $derived(
        page.url.pathname === '/'
            ? 'Overview'
            : page.url.pathname.startsWith('/sessions')
              ? 'Sessions'
              : page.url.pathname.startsWith('/party')
                ? 'The party'
                : page.url.pathname.startsWith('/payments')
                  ? 'Treasury'
                  : 'Overview'
    );

    async function logout() {
        await api.post('/api/v1/auth/logout', {});
        window.location.href = '/';
    }
</script>

<div class="app-shell">
    <aside class="sidebar">
        <a class="brand" href="/" aria-label="D&D Portal dashboard"
            ><img src="/dashboard-dnd-portal-logo-wide.svg" alt="D&D Portal" width="110" height="48" /></a
        >
        <div class="campaign-switcher-wrap">
            <div class="campaign-switcher">
                <div class="campaign-icon">D&amp;D</div>
                <div><strong>{campaignName}</strong><span>Player</span></div>
            </div>
        </div>
        <nav aria-label="Main navigation">
            <p class="nav-label">CAMPAIGN</p>
            <a class:active={active === 'Overview'} class="nav-item" href="/"><span class="nav-icon">⌗</span>Overview</a
            >
            <a class:active={active === 'Sessions'} class="nav-item" href="/sessions"
                ><span class="nav-icon">▣</span>Sessions</a
            >
            <a class:active={active === 'The party'} class="nav-item" href="/party"
                ><span class="nav-icon">♧</span>The party</a
            >
            <a class:active={active === 'Treasury'} class="nav-item" href="/payments"
                ><span class="nav-icon">◈</span>Treasury</a
            >
            <p class="nav-label secondary-label">WORLD</p>
            <span class="nav-item is-disabled" title="Chronicle is not available for this campaign"
                ><span class="nav-icon">◒</span>Chronicle</span
            >
            <span class="nav-item is-disabled" title="Map room is not available for this campaign"
                ><span class="nav-icon">⌁</span>Map room</span
            >
        </nav>
        <div class="sidebar-bottom">
            <div class="profile">
                <div class="avatar avatar-blue">{user.display_name.slice(0, 2).toUpperCase()}</div>
                <div><strong>{user.display_name}</strong><span>@{user.username ?? 'player'}</span></div>
            </div>
            {#if user.roles?.includes('portal_admin')}<a class="nav-item" href="/admin"
                    ><span class="nav-icon">⚙</span>Admin</a
                >{/if}<button class="nav-item" type="button" onclick={logout}>Sign out</button>
        </div>
    </aside>
    <main class="content">
        <header class="topbar">
            <div class="breadcrumb"><span>Campaign</span><b>/</b><strong>{active}</strong></div>
            <div class="avatar avatar-blue">{user.display_name.slice(0, 2).toUpperCase()}</div>
        </header>
        <div class="page-wrap">{@render children()}</div>
    </main>
</div>
