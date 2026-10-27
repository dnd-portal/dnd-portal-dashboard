<script lang="ts">
    let { name, href }: { name: string; href?: string } = $props();
    const initials = $derived(
        name
            .trim()
            .split(/\s+/)
            .map((part) => part[0])
            .join('')
            .slice(0, 2)
    );
</script>

{#snippet identity()}
    <span class="user-identity__avatar" aria-hidden="true">{initials}</span><span>{name}</span>
{/snippet}
{#if href}<a class="user-identity" {href}>{@render identity()}</a>{:else}<span class="user-identity"
        >{@render identity()}</span
    >{/if}
