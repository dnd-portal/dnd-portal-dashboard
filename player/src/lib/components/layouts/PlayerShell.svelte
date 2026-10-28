<script lang="ts">
    import { page } from '$app/state';
    import DashboardShell from './DashboardShell.svelte';
    import { playerBreadcrumbs, playerLinks, playerPollTitle } from '#lib/navigation.js';
    import type { Snippet } from 'svelte';

    type User = { display_name: string; username: string | null; roles?: string[] };
    let { user, campaignName, children }: { user: User; campaignName?: string; children: Snippet } = $props();
    const breadcrumbs = $derived(playerBreadcrumbs(page.url.pathname, $playerPollTitle));
    const navigation = [{ label: 'DASHBOARD', links: playerLinks }];
</script>

<DashboardShell {user} {navigation} {breadcrumbs}>{@render children()}</DashboardShell>
