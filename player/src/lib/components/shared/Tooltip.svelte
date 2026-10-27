<script lang="ts">
    import type { Snippet } from 'svelte';

    let { id, text, children }: { id: string; text: string; children: Snippet } = $props();
</script>

<span class="p-tooltip">
    {@render children()}
    <span {id} class="p-tooltip__content" role="tooltip">{text}</span>
</span>
