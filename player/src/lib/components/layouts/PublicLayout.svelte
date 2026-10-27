<script lang="ts">
    import type { Snippet } from 'svelte';
    import PublicNavigation from '#lib/components/shared/PublicNavigation.svelte';

    let { signedIn = false, children }: { signedIn?: boolean; children: Snippet } = $props();
</script>

<div class="p-public-layout">
    <PublicNavigation {signedIn} />
    <main class="p-public-layout__main">{@render children()}</main>
</div>
