<script lang="ts">
    import { onMount } from 'svelte';
    import { api, ApiRequestError } from '#lib/api/index.js';
    let polls = $state<any[]>([]);
    let results = $state<{ options: any[]; total_respondents: number }>({ options: [], total_respondents: 0 });
    let error = $state('');
    let title = $state('');
    let explanation = $state('');
    let options = $state('Thursday 29 October 2026\nFriday 30 October 2026');
    async function load() {
        try {
            polls = await api.get('/api/v1/admin/global-polls');
        } catch (e) {
            error = e instanceof ApiRequestError ? e.message : 'Could not load polls.';
        }
    }
    async function create() {
        await api.post('/api/v1/admin/global-polls', {
            title,
            explanation,
            options: options.split('\n').filter(Boolean)
        });
        title = '';
        await load();
    }
    async function setStatus(id: number, status: 'open' | 'closed') {
        await api.patch(`/api/v1/admin/global-polls/${id}`, { status });
        await load();
    }
    async function showResults(id: number) {
        results = await api.get(`/api/v1/admin/global-polls/${id}/results`);
    }
    onMount(() => void load());
</script>

<svelte:head><title>Global polls · D&amp;D Portal</title></svelte:head>
<section class="p-page-header">
    <h1>Global polls</h1>
    <p>Create and review the poll available to every active user.</p>
</section>
<section class="p-card">
    <input class="p-input" placeholder="Title" bind:value={title} /><textarea
        class="p-input"
        placeholder="Explanation"
        bind:value={explanation}></textarea><textarea
        class="p-input"
        placeholder="One option per line"
        bind:value={options}></textarea><button class="p-button" onclick={() => void create()}>Create poll</button>
</section>
{#if error}<p class="error-message">{error}</p>{/if}
<section class="p-card">
    <h2>Existing polls</h2>
    {#each polls as poll}<p>
            <strong>{poll.title}</strong> · {poll.status}
            <button class="p-button" onclick={() => void setStatus(poll.id, poll.status === 'open' ? 'closed' : 'open')}
                >{poll.status === 'open' ? 'Close' : 'Open'}</button
            >
            <button class="p-button" onclick={() => void showResults(poll.id)}>Results</button>
        </p>{/each}
    {#if results.options.length}<h2>Results</h2>
        <p>{results.total_respondents} unique respondents</p>
        {#each results.options as result}<p>{result.label}: {result.responses}</p>{/each}{/if}
</section>
