<script lang="ts">
    import { onMount } from 'svelte';
    import { goto } from '$app/navigation';
    import { page } from '$app/state';
    import { api, ApiRequestError } from '#lib/api/index.js';
    import ContentState from '#lib/components/shared/ContentState.svelte';
    import PlayerShell from '#lib/components/layouts/PlayerShell.svelte';
    import { providePlayerUser, type PlayerContext, type PlayerUser } from '#lib/player/context.js';
    import type { LayoutProps } from './$types';

    let { children }: LayoutProps = $props();
    let user = $state<PlayerUser | null>(null);
    const playerContext: PlayerContext = { user: null };
    providePlayerUser(playerContext);
    let checking = $state(true);

    onMount(async () => {
        try {
            user = await api.get<PlayerUser>('/api/v1/auth/me');
            playerContext.user = user;
        } catch (cause) {
            if (cause instanceof ApiRequestError && cause.status === 401) {
                const returnTo = `${page.url.pathname}${page.url.search}`;
                await goto(`/login?returnTo=${encodeURIComponent(returnTo)}`);
                return;
            }
        } finally {
            checking = false;
        }
    });
</script>

{#if checking}
    <ContentState title="Checking access…" />
{:else if user}
    <PlayerShell {user}>{@render children()}</PlayerShell>
{:else}
    <ContentState title="Unable to verify your session." />
{/if}
