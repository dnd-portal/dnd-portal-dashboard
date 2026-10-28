<script lang="ts">
    import { page } from '$app/state';
    import Icon from '#lib/components/shared/Icon.svelte';
    import { playerLinks } from '#lib/navigation.js';

    let { close = () => {} }: { close?: () => void } = $props();
    const active = $derived(playerLinks.find((link) => page.url.pathname.startsWith(link.href))?.label ?? 'Overview');
</script>

<nav class="p-nav" aria-label="Dashboard">
    <p class="p-nav__label">Dashboard</p>
    {#each playerLinks as link}
        <a
            class="p-nav__link"
            href={link.href}
            aria-current={active === link.label ? 'page' : undefined}
            onclick={close}
        >
            <Icon name={link.icon} size={20} /> <span>{link.label}</span>
        </a>
    {/each}
</nav>
