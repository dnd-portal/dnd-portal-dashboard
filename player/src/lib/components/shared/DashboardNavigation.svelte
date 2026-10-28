<script lang="ts">
    import { page } from '$app/state';
    import Icon from '#lib/components/shared/Icon.svelte';

    export type NavigationLink = { id: string; label: string; href: string; icon: string };
    export type NavigationGroup = { label: string; links: readonly NavigationLink[] };
    let { navigation, close = () => {} }: { navigation: NavigationGroup[]; close?: () => void } = $props();

    function isActive(link: NavigationLink) {
        if (link.id === 'overview') return page.url.pathname === link.href || page.url.pathname === `${link.href}/`;
        return page.url.pathname === link.href || page.url.pathname.startsWith(`${link.href}/`);
    }
</script>

{#each navigation as group (group.label)}
    <nav class="p-nav" aria-label={group.label}>
        <p class="p-nav__label">{group.label}</p>
        {#each group.links as link (link.id)}
            <a class="p-nav__link" href={link.href} aria-current={isActive(link) ? 'page' : undefined} onclick={close}>
                <Icon name={link.icon} size={20} /> <span>{link.label}</span>
            </a>
        {/each}
    </nav>
{/each}
