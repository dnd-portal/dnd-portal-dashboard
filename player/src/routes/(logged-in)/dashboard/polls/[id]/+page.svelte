<script lang="ts">
    import { page } from '$app/state';
    import { onDestroy } from 'svelte';
    import { api, ApiRequestError } from '#lib/api/index.js';
    import { playerPollTitle } from '#lib/navigation.js';
    import PollResponse, { type PlayerPoll } from '#lib/components/player/PollResponse.svelte';
    let poll = $state<PlayerPoll | null>(null);
    let loading = $state(true);
    let error = $state('');
    async function load() {
        loading = true;
        error = '';
        try {
            poll = await api.get<PlayerPoll>(`/api/v1/global-polls/${page.params.id}`);
            playerPollTitle.set(poll.title);
        } catch (cause) {
            error = cause instanceof ApiRequestError ? cause.message : 'Poll unavailable.';
        } finally {
            loading = false;
        }
    }
    $effect(() => {
        if (page.params.id) void load();
    });
    onDestroy(() => playerPollTitle.set(''));
</script>

<svelte:head><title>{poll?.title ?? 'Poll'} · D&amp;D Portal</title></svelte:head>
{#if loading}<section class="p-state"><h1>Loading poll…</h1></section>
{:else if error}<section class="p-state" role="alert">
        <h1>Poll unavailable</h1>
        <p>{error}</p>
        <a class="p-button" href="/dashboard/polls">Back to polls</a>
    </section>
{:else if poll}<PollResponse {poll} />{/if}
