<script lang="ts">
    import { goto } from '$app/navigation';
    import { onMount } from 'svelte';
    import { api, ApiRequestError } from '#lib/api/index.js';
    let error = $state('');
    onMount(async () => {
        try {
            const polls = await api.get<{ id: number }[]>('/api/v1/global-polls');
            await goto(polls[0] ? `/dashboard/polls/${polls[0].id}` : '/dashboard/polls');
        } catch (cause) {
            error = cause instanceof ApiRequestError ? cause.message : 'Could not load polls.';
        }
    });
</script>

<svelte:head><title>Current poll · D&amp;D Portal</title></svelte:head>
{#if error}<section class="p-state" role="alert">
        <h1>Polls unavailable</h1>
        <p>{error}</p>
        <a class="p-button" href="/dashboard/polls">View polls</a>
    </section>
{:else}<section class="p-state"><h1>Loading polls…</h1></section>{/if}
