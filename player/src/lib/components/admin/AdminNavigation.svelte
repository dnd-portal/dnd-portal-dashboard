<script lang="ts">
    import { adminLinks } from '#lib/admin/navigation.js';
    import Icon from '#lib/components/shared/Icon.svelte';

    let {
        active,
        counts = {},
        close = () => {}
    }: { active: string; counts?: Record<string, number>; close?: () => void } = $props();
</script>

{#each ['Overview', 'Database', 'Campaign', 'Finance', 'System'] as group}
    <section class="admin-sidebar__group">
        <p class="admin-sidebar__label">{group}</p>
        {#each adminLinks.filter((link) => link.group === group) as link (link.id)}
            <a
                class="p-nav__link admin-sidebar__link"
                class:admin-sidebar__link--active={active === link.id}
                href={link.href}
                aria-current={active === link.id ? 'page' : undefined}
                onclick={close}
            >
                <span class="admin-sidebar__icon"><Icon name={link.id} /></span>
                <span class="admin-sidebar__text">{link.label}</span>
                {#if link.id !== 'overview'}<span class="admin-sidebar__count">{counts[link.id] ?? 0}</span>{/if}
            </a>
        {/each}
    </section>
{/each}
