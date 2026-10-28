<script lang="ts">
    import { page } from '$app/state';
    import { api } from '#lib/api/index.js';
    import AdminIcon from '#lib/components/admin/AdminIcon.svelte';
    import { playerBreadcrumbs, playerLinkIsActive, playerLinks, playerPollTitle } from '#lib/navigation.js';
    import type { Snippet } from 'svelte';

    type User = { display_name: string; username: string | null; roles?: string[] };
    let { user, campaignName, children }: { user: User; campaignName?: string; children: Snippet } = $props();
    let menuOpen = $state(false);
    const breadcrumbs = $derived(playerBreadcrumbs(page.url.pathname, $playerPollTitle));

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
        <div class="p-shell__header-actions">
            <nav class="p-shell__breadcrumb" aria-label="Breadcrumb">
                {#each breadcrumbs as item, index}<span class="p-shell__breadcrumb-item">
                        {#if item.href}<a href={item.href}>{item.label}</a>{:else}<strong aria-current="page"
                                >{item.label}</strong
                            >{/if}
                        {#if index < breadcrumbs.length - 1}<span aria-hidden="true">{' / '}</span>{/if}
                    </span>{/each}
            </nav>
        </div>
    </header>
    <button class="p-shell__backdrop" type="button" aria-label="Close navigation" onclick={closeMenu}></button>
    <aside id="portal-navigation" class="p-shell__sidebar">
        <!-- TODO post-MVP: re-enable the campaign picker when campaign data is available. -->
        {#if false && campaignName}<button class="p-campaign-picker" type="button">
                <span class="p-campaign-picker__mark">D&amp;D</span>
                <span class="p-campaign-picker__copy"
                    ><strong>{campaignName}</strong><small>Player campaign</small></span
                >
                <span class="p-campaign-picker__chevron" aria-hidden="true">⌄</span>
            </button>{/if}
        <nav class="p-nav" aria-label="Dashboard">
            <p class="p-nav__label">Dashboard</p>
            {#each playerLinks as link}<a
                    class="p-nav__link"
                    href={link.href}
                    aria-current={playerLinkIsActive(link.id, page.url.pathname) ? 'page' : undefined}
                    onclick={closeMenu}><AdminIcon name={link.icon} size={20} /> <span>{link.label}</span></a
                >{/each}
            <!-- TODO post-MVP: re-enable Chronicle when story management is implemented. -->
            <!-- TODO post-MVP: re-enable Map room when map data is implemented. -->
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
