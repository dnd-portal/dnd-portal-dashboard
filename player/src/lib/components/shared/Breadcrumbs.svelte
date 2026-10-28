<script lang="ts">
    let { items = [] }: { items?: { label: string; href?: string }[] } = $props();
</script>

<nav aria-label="Breadcrumb" class="admin-breadcrumbs">
    <ol class="admin-breadcrumbs__list">
        {#each items as item, i}<li class="admin-breadcrumbs__item">
                {#if i > 0}<span class="admin-breadcrumbs__separator" aria-hidden="true">›</span>{/if}
                {#if item.href && i < items.length - 1}<a href={item.href}>{item.label}</a>{:else}<span
                        aria-current="page">{item.label}</span
                    >{/if}
            </li>{/each}
    </ol>
</nav>
