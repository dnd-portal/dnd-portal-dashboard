<script lang="ts">
    import type { Snippet } from 'svelte';
    import type { Cell } from '#lib/admin/types.js';
    let { fields, value }: { fields: { label: string; value: Cell }[]; value: Snippet<[Cell]> } = $props();
</script>

<dl class="definition-list">
    {#each fields as field (field.label)}<div class="definition-list__row">
            <dt>{field.label}</dt>
            <dd>{@render value(field.value)}</dd>
        </div>{/each}
</dl>
