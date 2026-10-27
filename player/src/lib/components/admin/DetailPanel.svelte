<script lang="ts">
    import type { Snippet } from 'svelte';
    let {
        eyebrow,
        title,
        children,
        headingActions
    }: { eyebrow?: string; title?: string; children: Snippet; headingActions?: Snippet } = $props();
</script>

<section class="detail-panel">
    {#if eyebrow || title}<header class="detail-panel__header">
            <div>
                {#if eyebrow}<p class="detail-panel__eyebrow">{eyebrow}</p>{/if}{#if title}<h2
                        class="detail-panel__title"
                    >
                        {title}
                    </h2>{/if}
            </div>
            {#if headingActions}<div class="detail-panel__actions">{@render headingActions()}</div>{/if}
        </header>{/if}
    {@render children()}
</section>
