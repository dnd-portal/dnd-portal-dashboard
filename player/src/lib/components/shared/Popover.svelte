<script lang="ts">
    import type { Snippet } from 'svelte';
    let { label, children }: { label: string; children: Snippet } = $props();
    const id = $props.id();
    let open = $state(false);
</script>

<button class="popover__trigger" type="button" popovertarget={id} aria-expanded={open} aria-controls={id}
    >{label}</button
>
<div class="popover__content" {id} popover="auto" ontoggle={(event) => (open = event.newState === 'open')}>
    {@render children()}
</div>
