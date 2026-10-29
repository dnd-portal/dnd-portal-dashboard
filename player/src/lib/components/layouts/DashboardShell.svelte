<script lang="ts">
    import { api } from '#lib/api/index.js';
    import DashboardNavigation, { type NavigationGroup } from '#lib/components/shared/DashboardNavigation.svelte';
    import { tick } from 'svelte';
    import type { Snippet } from 'svelte';

    type User = { roles?: string[] };
    type Breadcrumb = { label: string; href?: string };
    let {
        user,
        navigation,
        breadcrumbs,
        children,
        backToDashboard = false,
        homeHref = '/dashboard'
    }: {
        user: User;
        navigation: NavigationGroup[];
        breadcrumbs: Breadcrumb[];
        children: Snippet;
        backToDashboard?: boolean;
        homeHref?: string;
    } = $props();
    let menuOpen = $state(false);
    let menuToggle: HTMLButtonElement;
    let closeButton: HTMLButtonElement;

    async function logout() {
        await api.post('/api/v1/auth/logout', {});
        window.location.href = '/';
    }
    function closeMenu() {
        menuOpen = false;
        tick().then(() => setTimeout(() => menuToggle?.focus(), 0));
    }

    $effect(() => {
        document.body.style.overflow = menuOpen ? 'hidden' : '';
        if (menuOpen) tick().then(() => setTimeout(() => closeButton?.focus(), 0));
        return () => {
            document.body.style.overflow = '';
        };
    });
</script>

<svelte:window onkeydown={(event) => event.key === 'Escape' && closeMenu()} />

<div class="p-shell" data-menu-open={menuOpen}>
    <header class="p-shell__header">
        <a
            class="p-shell__brand"
            href={homeHref}
            aria-label={homeHref === '/admin' ? 'D&D Portal admin' : 'D&D Portal dashboard'}
        >
            <img src="/dashboard-dnd-portal-logo-wide.svg" alt="D&D Portal" width="110" height="48" />
        </a>
        <button
            bind:this={menuToggle}
            class="p-button p-button--secondary p-shell__menu-toggle"
            type="button"
            aria-label={menuOpen ? 'Close navigation' : 'Open navigation'}
            aria-expanded={menuOpen}
            aria-controls="portal-navigation"
            onclick={() => (menuOpen = !menuOpen)}>Menu</button
        >
        <div class="p-shell__header-actions">
            <nav class="p-shell__breadcrumb" aria-label="Breadcrumb">
                {#each breadcrumbs as item, index}<span class="p-shell__breadcrumb-item">
                        {#if item.href && index < breadcrumbs.length - 1}<a href={item.href}>{item.label}</a
                            >{:else}<strong aria-current="page">{item.label}</strong>{/if}
                        {#if index < breadcrumbs.length - 1}<span aria-hidden="true">{' / '}</span>{/if}
                    </span>{/each}
            </nav>
        </div>
    </header>
    <button class="p-shell__backdrop" type="button" aria-label="Close navigation" onclick={closeMenu}></button>
    <aside id="portal-navigation" class="p-shell__sidebar" aria-label="Dashboard navigation">
        <div class="p-shell__mobile-drawer-header">
            <a class="p-shell__brand" href={homeHref} aria-label="D&D Portal home">
                <img src="/dashboard-dnd-portal-logo-wide.svg" alt="D&D Portal" width="110" height="48" />
            </a>
            <button
                bind:this={closeButton}
                class="p-button p-button--secondary p-shell__close-menu"
                type="button"
                aria-label="Close menu"
                onclick={closeMenu}
            >
                <span aria-hidden="true">×</span>
            </button>
        </div>
        <DashboardNavigation {navigation} close={closeMenu} />
        <div class="p-shell__sidebar-footer">
            {#if backToDashboard}<a class="p-button p-button--secondary" href="/dashboard">← Back to dashboard</a>{/if}
            {#if !backToDashboard && user.roles?.includes('portal_admin')}<a
                    class="p-button p-button--secondary"
                    href="/admin">Admin</a
                >{/if}
            <button class="p-button p-button--secondary" type="button" onclick={logout}>Sign out</button>
        </div>
    </aside>
    <main class="p-shell__main">
        <div class="p-container">{@render children()}</div>
    </main>
</div>
