<script lang="ts">
    import { page } from '$app/state';
    import Icon from '#lib/components/shared/Icon.svelte';
    import { playerLinkIsActive, playerLinks } from '#lib/navigation.js';

    let { close = () => {} }: { close?: () => void } = $props();
</script>

<nav class="p-nav" aria-label="Dashboard">
    <p class="p-nav__label">Dashboard</p>
    {#each playerLinks as link}
        <a
            class="p-nav__link"
            href={link.href}
            aria-current={playerLinkIsActive(link.id, page.url.pathname) ? 'page' : undefined}
            onclick={close}
        >
            <Icon name={link.icon} size={20} /> <span>{link.label}</span>
        </a>
    {/each}
</nav>
