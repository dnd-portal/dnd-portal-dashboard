<script lang="ts">
    import type { Cell } from '#lib/admin/types.js';
    import UserIdentity from '../shared/UserIdentity.svelte';
    import StatusLabel from '../shared/StatusLabel.svelte';
    import Popover from '../shared/Popover.svelte';
    let { cell }: { cell: Cell } = $props();
</script>

{#if cell.kind === 'user'}<UserIdentity name={cell.text} href={cell.href} />
{:else if cell.kind === 'status'}<StatusLabel text={cell.text} tone={cell.tone} />
{:else if cell.kind === 'responses' && cell.names?.length}<Popover label={cell.text}
        ><ul class="popover__list">
            {#each cell.names as name, index (`${index}-${name}`)}<li>{name}</li>{/each}
        </ul></Popover
    >
{:else if cell.href}<a
        class="entity-link"
        href={cell.href}
        target={cell.href.startsWith('https://') ? '_blank' : undefined}
        rel={cell.href.startsWith('https://') ? 'noreferrer' : undefined}>{cell.text}</a
    >
{:else}<span class="entity-text">{cell.text || '—'}</span>{/if}
