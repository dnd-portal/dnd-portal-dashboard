<script lang="ts">
    let { items }: { items: { label: string; href?: string }[] } = $props();
</script>

<nav class="admin-breadcrumbs" aria-label="Breadcrumb">
    <ol class="admin-breadcrumbs__list">
        {#each items as item, index (`${index}-${item.label}`)}<li class="admin-breadcrumbs__item">
                {#if index > 0}<span class="admin-breadcrumbs__separator" aria-hidden="true">›</span
                    >{/if}{#if item.href && index < items.length - 1}<a href={item.href}>{item.label}</a>{:else}<span
                        aria-current={index === items.length - 1 ? 'page' : undefined}>{item.label}</span
                    >{/if}
            </li>{/each}
    </ol>
</nav>
