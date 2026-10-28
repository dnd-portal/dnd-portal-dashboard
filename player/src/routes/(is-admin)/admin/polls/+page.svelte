<script lang="ts">
    import { onMount } from 'svelte';
    import { api, ApiRequestError } from '#lib/api/index.js';
    let polls = $state<any[]>([]);
    let error = $state('');
    let title = $state('');
    let explanation = $state('');
    let options = $state('Thursday 29 October 2026\nFriday 30 October 2026');
    let busy = $state(false);
    async function load() {
        try {
            polls = await api.get('/api/v1/admin/global-polls');
        } catch (e) {
            error = e instanceof ApiRequestError ? e.message : 'Could not load polls.';
        }
    }
    async function create() {
        busy = true;
        error = '';
        try {
            await api.post('/api/v1/admin/global-polls', {
                title,
                explanation,
                options: options.split('\n').filter(Boolean)
            });
            title = '';
            await load();
        } catch (e) {
            error = e instanceof ApiRequestError ? e.message : 'Could not create poll.';
        } finally {
            busy = false;
        }
    }
    async function setStatus(id: number, status: 'open' | 'closed') {
        await api.patch(`/api/v1/admin/global-polls/${id}`, { status });
        await load();
    }
    onMount(() => void load());
</script>

<svelte:head><title>Global polls · D&amp;D Portal</title></svelte:head>
<section class="p-page-header">
    <h1>Global polls</h1>
    <p>Create and review the poll available to every active user.</p>
</section>
<section class="p-card admin-mvp-panel">
    <h2>Create a global poll</h2>
    <div class="admin-mvp-form">
        <label for="poll-title">Title</label><input id="poll-title" class="p-input" bind:value={title} />
        <label for="poll-explanation">Explanation</label><textarea
            id="poll-explanation"
            class="p-input"
            bind:value={explanation}></textarea>
        <label for="poll-options">Options</label><textarea id="poll-options" class="p-input" bind:value={options}
        ></textarea>
        <small>Enter one date or option per line.</small>
        <div class="admin-mvp-form__actions">
            <button class="p-button" disabled={busy || !title.trim() || !options.trim()} onclick={() => void create()}
                >{busy ? 'Creating…' : 'Create poll'}</button
            >
        </div>
    </div>
</section>
{#if error}<p class="error-message">{error}</p>{/if}
<section class="p-card admin-mvp-panel">
    <h2>Existing polls</h2>
    {#if polls.length === 0}<p class="admin-mvp-empty__text">No global polls yet.</p>{/if}
    {#each polls as poll}<div class="admin-mvp-list-row">
            <div>
                <strong>{poll.title}</strong><span class={`admin-mvp-badge admin-mvp-badge--${poll.status}`}
                    >{poll.status}</span
                ><small>{poll.options?.length ?? 0} options</small>
            </div>
            <div class="admin-mvp-actions">
                <button
                    class={`p-button p-button--compact ${poll.status === 'open' ? 'p-button--danger' : ''}`}
                    onclick={() => void setStatus(poll.id, poll.status === 'open' ? 'closed' : 'open')}
                    >{poll.status === 'open' ? 'Close' : 'Open'}</button
                >
                <a class="p-button p-button--secondary p-button--compact" href={`/admin/polls/${poll.id}/results`}
                    >Results</a
                >
            </div>
        </div>{/each}
</section>
