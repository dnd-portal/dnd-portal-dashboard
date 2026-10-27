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
    let menuOpen = $state(false);
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

    function closeMenu() {
        menuOpen = false;
    }
</script>

<svelte:window onkeydown={(event) => event.key === 'Escape' && closeMenu()} />

<div class="p-shell" data-menu-open={menuOpen}>
    <header class="p-shell__header">
        <a class="p-shell__brand" href="/" aria-label="D&D Portal dashboard"
            ><img src="/dashboard-dnd-portal-logo-wide.svg" alt="D&D Portal" width="110" height="48" /></a
        >
        <button
            class="p-button p-button--secondary p-shell__menu-toggle"
            type="button"
            aria-label="Open navigation"
            aria-expanded={menuOpen}
            aria-controls="portal-navigation"
            onclick={() => (menuOpen = !menuOpen)}>Menu</button
        >
        <div class="p-shell__header-actions"><span>{active}</span></div>
    </header>
    <button class="p-shell__backdrop" type="button" aria-label="Close navigation" onclick={closeMenu}></button>
    <aside id="portal-navigation" class="p-shell__sidebar">
        <button class="p-campaign-picker" type="button"><strong>{campaignName}</strong><small>Player</small></button>
        <nav class="p-nav" aria-label="Campaign">
            <p class="p-nav__label">Campaign</p>
            <a
                class="p-nav__link"
                href="/"
                aria-current={active === 'Overview' ? 'page' : undefined}
                onclick={closeMenu}>⌗ Overview</a
            >
            <a
                class="p-nav__link"
                href="/sessions"
                aria-current={active === 'Sessions' ? 'page' : undefined}
                onclick={closeMenu}>▣ Sessions</a
            >
            <a
                class="p-nav__link"
                href="/party"
                aria-current={active === 'The party' ? 'page' : undefined}
                onclick={closeMenu}>♧ The party</a
            >
            <a
                class="p-nav__link"
                href="/payments"
                aria-current={active === 'Treasury' ? 'page' : undefined}
                onclick={closeMenu}>◈ Treasury</a
            >
            <span class="p-nav__link" aria-disabled="true" title="Chronicle is not available in this portal build"
                >▤ Chronicle</span
            >
            <span class="p-nav__link" aria-disabled="true" title="Map room is not available in this portal build"
                >⌖ Map room</span
            >
        </nav>
        <div class="p-shell__sidebar-footer">
            {#if user.roles?.includes('portal_admin')}<a class="p-button p-button--secondary" href="/admin">Admin</a
                >{/if}
            <button class="p-button p-button--secondary" type="button" onclick={logout}>Sign out</button>
        </div>
    </aside>
    <main class="p-shell__main">
        <div class="p-container">{@render children()}</div>
    </main>
</div>
