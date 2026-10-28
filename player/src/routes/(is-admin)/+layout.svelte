<script lang="ts">
    import type { LayoutProps } from './$types';
    import AdminShell from '#lib/components/layouts/AdminShell.svelte';

    let { children, data }: LayoutProps = $props();
</script>

<AdminShell {data}>{@render children()}</AdminShell>
